<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Services\Product;

use Illuminate\Filesystem\Filesystem;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Contracts\Logger;
use ReliQArts\Docweaver\Contracts\Product\Publisher as PublisherContract;
use ReliQArts\Docweaver\Contracts\VCSCommandRunner;
use ReliQArts\Docweaver\Exceptions\Product\InvalidAssetDirectory;
use ReliQArts\Docweaver\Exceptions\Product\PublicationFailed;
use ReliQArts\Docweaver\Models\Product;
use ReliQArts\Docweaver\Services\Publisher as BasePublisher;
use ReliQArts\Docweaver\VO\Result;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Publishes and updates product versions.
 */
final class Publisher extends BasePublisher implements PublisherContract
{
    /**
     * @var VCSCommandRunner
     */
    private $vcsCommandRunner;

    /**
     * Publisher constructor.
     *
     * @param Filesystem       $filesystem
     * @param Logger           $logger
     * @param VCSCommandRunner $vcsCommandRunner
     */
    public function __construct(Filesystem $filesystem, Logger $logger, VCSCommandRunner $vcsCommandRunner)
    {
        parent::__construct($filesystem, $logger);

        $this->vcsCommandRunner = $vcsCommandRunner;
    }

    /**
     * @param Product $product
     * @param string  $source
     *
     * @throws Exception
     *
     * @return Result
     */
    public function publish(Product $product, string $source): Result
    {
        $result = new Result();
        $versions = [Product::VERSION_MASTER];
        $versionsPublished = [];
        $versionsUpdated = [];
        $productDirectory = $product->getDirectory();
        $masterDirectory = sprintf('%s/master', $productDirectory);

        $this->setExecutionStartTime();

        if (!$this->readyResourceDirectory($productDirectory)) {
            return $result->setError(sprintf('Product directory %s is not writable.', $productDirectory))
                ->setData((object)['executionTime' => $this->getExecutionTime()]);
        }

        if (!$this->filesystem->isDirectory($masterDirectory)) {
            $this->publishVersion($product, $source, Product::VERSION_MASTER);
            $versionsPublished[] = Product::VERSION_MASTER;
        } elseif ($this->updateVersion($product, Product::VERSION_MASTER)) {
            $versionsUpdated[] = Product::VERSION_MASTER;
        } else {
            throw PublicationFailed::forProduct($product);
        }

        $tagResult = $this->publishTags($product, $source);
        $tagData = $tagResult->getData();
        $versions = array_merge($versions, $tagData->tags ?? []);
        $versionsPublished = array_merge($versionsPublished, $tagData->tagsPublished ?? []);

        if ($result->isSuccess()) {
            return $result->setMessage(sprintf('%s was successfully published.', $product->getName()))
                ->setData((object)[
                    'versions' => $versions,
                    'versionsPublished' => $versionsPublished,
                    'versionsUpdated' => $versionsUpdated,
                ]);
        }

        return $result;
    }

    /**
     * @param Product $product
     * @param string  $source
     * @param string  $version
     *
     * @return bool
     */
    public function publishVersion(Product $product, string $source, string $version): bool
    {
        try {
            $this->vcsCommandRunner->clone($source, $version, $product->getDirectory());
        } catch (ProcessFailedException $e) {
            return false;
        }

        try {
            $product->publishAssets($version);
        } catch (Exception $exception) {
            if ($exception instanceof InvalidAssetDirectory) {
                $this->logger->info($exception->getMessage());
            } else {
                $this->logger->error($exception->getMessage());
            }
        }

        return true;
    }

    /**
     * @param Product $product
     *
     * @return Result
     */
    public function update(Product $product): Result
    {
        $result = new Result();
        $versions = $product->getVersions();
        $versionsUpdated = [];

        foreach (array_keys($versions) as $version) {
            if ($this->updateVersion($product, $version)) {
                $versionsUpdated[] = $version;
            }
        }

        if ($result->isSuccess()) {
            return $result->setMessage(sprintf('%s was successfully updated.', $product->getName()))
                ->setData((object)[
                    'versions' => $versions,
                    'versionsUpdated' => $versionsUpdated,
                ]);
        }

        return $result;
    }

    /**
     * @param Product $product
     * @param string  $version
     *
     * @return bool
     */
    public function updateVersion(Product $product, string $version): bool
    {
        try {
            $this->vcsCommandRunner->pull(sprintf('%s/%s', $product->getDirectory(), $version));
        } catch (ProcessFailedException $e) {
            return false;
        }

        try {
            $product->publishAssets($version);
        } catch (Exception $exception) {
            if ($exception instanceof InvalidAssetDirectory) {
                $this->logger->info($exception->getMessage());
            } else {
                $this->logger->error($exception->getMessage());
            }
        }

        return true;
    }

    /**
     * @param Product $product
     * @param string  $source
     *
     * @return Result
     */
    private function publishTags(Product $product, string $source): Result
    {
        $result = new Result();
        $masterDirectory = sprintf('%s/%s', $product->getDirectory(), Product::VERSION_MASTER);
        $tagsPublished = [];

        try {
            $tags = $this->vcsCommandRunner->getTags($masterDirectory);

            foreach ($tags as $tag) {
                $tagDirectory = sprintf('%s/%s', $product->getDirectory(), $tag);

                if (!$this->filesystem->isDirectory($tagDirectory)) {
                    $this->publishVersion($product, $source, $tag);
                    $result = $result->addMessage(sprintf('Successfully published tag `%s`.', $tag));
                    $tagsPublished[] = $tag;
                } else {
                    $message = sprintf('Version `%s` already exists.', $tag);
                    $result = $result->addMessage($message);
                }
            }
        } catch (ProcessFailedException $e) {
            $errorMessage = $e->getMessage();

            return $result->setMessage($errorMessage)->setError($errorMessage);
        }

        return $result->setData((object)[
            'tags' => $tags,
            'tagsPublished' => $tagsPublished,
        ]);
    }
}
