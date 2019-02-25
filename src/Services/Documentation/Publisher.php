<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Services\Documentation;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use ReliQArts\Docweaver\Contracts\ConfigProvider;
use ReliQArts\Docweaver\Contracts\Documentation\Publisher as PublisherContract;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Contracts\Logger;
use ReliQArts\Docweaver\Contracts\Product\Maker as ProductFactory;
use ReliQArts\Docweaver\Contracts\Product\Publisher as ProductPublisher;
use ReliQArts\Docweaver\Exceptions\BadImplementation;
use ReliQArts\Docweaver\Services\Publisher as BasePublisher;
use ReliQArts\Docweaver\VO\Result;

/**
 * Publishes and updates documentation.
 */
final class Publisher extends BasePublisher implements PublisherContract
{
    /**
     * Documentation resource directory.
     *
     * @var string
     */
    private $documentationDirectory;

    /**
     * @var ProductPublisher
     */
    private $productPublisher;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * Working directory.
     *
     * @var string
     */
    private $workingDirectory;

    /**
     * Create a new DocumentationPublisher.
     *
     * @param Filesystem       $filesystem
     * @param Logger           $logger
     * @param ConfigProvider   $configProvider
     * @param ProductPublisher $productPublisher
     * @param ProductFactory   $productFactory
     *
     * @throws BadImplementation
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
        $this->documentationDirectory = $configProvider->getDocumentationDirectory();
        $this->workingDirectory = base_path($this->documentationDirectory);

        if (!$this->readyResourceDirectory($this->workingDirectory)) {
            throw new BadImplementation(
                sprintf(
                    'Could not ready document resource directory (%s). Please ensure file system is writable.',
                    $this->documentationDirectory
                )
            );
        }
    }

    /**
     * @param string       $productName
     * @param string       $source
     * @param null|Command $callingCommand
     *
     * @throws Exception
     *
     * @return Result
     */
    public function publish(string $productName, string $source = '', Command &$callingCommand = null): Result
    {
        $result = new Result();
        $commonName = strtolower($productName);
        $productDirectory = sprintf('%s/%s', $this->workingDirectory, $commonName);

        $this->setExecutionStartTime();
        if (!$this->readyResourceDirectory($productDirectory)) {
            return $result->setError(sprintf('Product directory %s is not writable.', $productDirectory))
                ->setData((object)['executionTime' => $this->getExecutionTime()]);
        }

        $product = $this->productFactory->create($productDirectory);
        $result = $this->productPublisher->publish($product, $source);

        foreach ($result->getMessages() as $message) {
            $this->tell($message);
        }

        $data = $result->getData() ?? (object)[];
        $data->executionTime = $this->getExecutionTime();

        return $result->setData($data);
    }

    /**
     * @param string       $productName
     * @param null|Command $callingCommand
     *
     * @throws Exception
     *
     * @return Result
     */
    public function update(string $productName, Command &$callingCommand = null): Result
    {
        $this->callingCommand = $callingCommand;
        $result = new Result();
        $commonName = strtolower($productName);
        $productDirectory = sprintf('%s/%s', $this->workingDirectory, $commonName);

        $this->setExecutionStartTime();
        if (!$this->readyResourceDirectory($productDirectory)) {
            return $result->setError(sprintf('Product directory %s is not writable.', $productDirectory))
                ->setData((object)['executionTime' => $this->getExecutionTime()]);
        }

        $product = $this->productFactory->create($productDirectory);
        $result = $this->productPublisher->update($product);

        foreach ($result->getMessages() as $message) {
            $this->tell($message);
        }

        $data = $result->getData() ?? (object)[];
        $data->executionTime = $this->getExecutionTime();

        return $result->setData($data);
    }

    /**
     * @param null|Command $callingCommand
     *
     * @throws Exception
     *
     * @return Result
     */
    public function updateAll(Command &$callingCommand = null): Result
    {
        $this->callingCommand = $callingCommand;
        $result = new Result();
        $productDirectories = $this->filesystem->directories($this->workingDirectory);
        $productResults = [];
        $productsUpdated = 0;

        $this->setExecutionStartTime();

        foreach ($productDirectories as $productDirectory) {
            $productName = basename($productDirectory);

            $this->tell(sprintf('Updating %s...', $productName), self::TELL_DIRECTION_FLAT);

            $productResult = $this->update($productName, $callingCommand);
            $productResults[$productName] = $productResult;

            if ($productResult->isSuccess()) {
                ++$productsUpdated;
            }
        }

        return $result->setData((object)[
            'products' => $productDirectories,
            'productsUpdated' => $productsUpdated,
            'executionTime' => $this->getExecutionTime(),
        ]);
    }
}
