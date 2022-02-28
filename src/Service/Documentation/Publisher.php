<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service\Documentation;

use Illuminate\Console\Command;
use ReliqArts\Docweaver\Contract\ConfigProvider;
use ReliqArts\Docweaver\Contract\Documentation\Publisher as PublisherContract;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\Filesystem;
use ReliqArts\Docweaver\Contract\Logger;
use ReliqArts\Docweaver\Contract\Product\Maker as ProductFactory;
use ReliqArts\Docweaver\Contract\Product\Publisher as ProductPublisher;
use ReliqArts\Docweaver\Exception\BadImplementationException;
use ReliqArts\Docweaver\Exception\DirectoryNotWritableException;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Result;
use ReliqArts\Docweaver\Service\Publisher as BasePublisher;

/**
 * Publishes and updates documentation.
 */
final class Publisher extends BasePublisher implements PublisherContract
{
    /**
     * @var ProductPublisher
     */
    private ProductPublisher $productPublisher;

    /**
     * @var ProductFactory
     */
    private ProductFactory $productFactory;

    /**
     * Working directory.
     *
     * @var string
     */
    private string $workingDirectory;

    /**
     * Create a new DocumentationPublisher.
     *
     * @throws BadImplementationException
     */
    public function __construct(
        Filesystem $filesystem,
        Logger $logger,
        ConfigProvider $configProvider,
        ProductPublisher $productPublisher,
        ProductFactory $productFactory
    ) {
        parent::__construct($filesystem, $logger);

        $this->productPublisher = $productPublisher;
        $this->productFactory = $productFactory;
        $documentationDirectory = $configProvider->getDocumentationDirectory();
        $this->workingDirectory = base_path($documentationDirectory);

        if (!$this->readyResourceDirectory($this->workingDirectory)) {
            throw new BadImplementationException(sprintf('Could not ready document resource directory `%s`. Please ensure file system is writable.', $documentationDirectory));
        }
    }

    /**
     * @throws Exception
     */
    public function publish(string $productName, string $source = '', Command &$callingCommand = null): Result
    {
        $this->callingCommand = $callingCommand;

        $this->setExecutionStartTime();

        $result = $this->productPublisher->publish($this->getProductForPublishing($productName), $source);

        foreach ($result->getMessages() as $message) {
            $this->tell($message);
        }

        $data = $result->getExtra() ?? (object)[];
        $data->executionTime = $this->getExecutionTime();

        return $result->setExtra($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $productName, Command &$callingCommand = null): Result
    {
        $this->callingCommand = $callingCommand;

        $this->setExecutionStartTime();

        $result = $this->productPublisher->update($this->getProductForPublishing($productName));

        foreach ($result->getMessages() as $message) {
            $this->tell($message);
        }

        $data = $result->getExtra() ?? (object)[];
        $data->executionTime = $this->getExecutionTime();

        return $result->setExtra($data);
    }

    /**
     * @throws Exception
     */
    public function updateAll(Command &$callingCommand = null): Result
    {
        $this->callingCommand = $callingCommand;
        $result = new Result();
        $productDirectories = $this->filesystem->directories($this->workingDirectory);
        $products = [];
        $productsUpdated = [];

        $this->setExecutionStartTime();

        foreach ($productDirectories as $productDirectory) {
            $productName = basename($productDirectory);

            $this->tell(sprintf('Updating %s...', $productName), self::TELL_DIRECTION_FLAT);

            $productResult = $this->update($productName, $callingCommand);
            $products[] = $productName;

            if ($productResult->isSuccess()) {
                $productsUpdated[] = $productName;
            }
        }

        return $result->setExtra((object)[
            'products' => $products,
            'productsUpdated' => $productsUpdated,
            'executionTime' => $this->getExecutionTime(),
        ]);
    }

    /**
     * @throws Exception
     */
    private function getProductForPublishing(string $productName): Product
    {
        $productDirectory = sprintf('%s/%s', $this->workingDirectory, strtolower($productName));

        if (!$this->readyResourceDirectory($productDirectory)) {
            throw DirectoryNotWritableException::forDirectory($productDirectory);
        }

        return $this->productFactory->create($productDirectory);
    }
}
