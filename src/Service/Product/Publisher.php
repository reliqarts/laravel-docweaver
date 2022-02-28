<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service\Product;

use LogicException;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\Filesystem;
use ReliqArts\Docweaver\Contract\Logger;
use ReliqArts\Docweaver\Contract\Product\Publisher as PublisherContract;
use ReliqArts\Docweaver\Contract\VcsCommandRunner;
use ReliqArts\Docweaver\Exception\Product\InvalidAssetDirectoryException;
use ReliqArts\Docweaver\Exception\Product\PublicationFailedException;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Result;
use ReliqArts\Docweaver\Service\Publisher as BasePublisher;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Publishes and updates product versions.
 */
final class Publisher extends BasePublisher implements PublisherContract
{
    /**
     * @var VcsCommandRunner
     */
    private VcsCommandRunner $vcsCommandRunner;

    /**
     * Publisher constructor.
     */
    public function __construct(Filesystem $filesystem, Logger $logger, VcsCommandRunner $vcsCommandRunner)
    {
        parent::__construct($filesystem, $logger);

        $this->vcsCommandRunner = $vcsCommandRunner;
    }

    /**
     * @throws Exception
     */
    public function publish(Product $product, string $source): Result
    {
        $result = new Result();
        $versions = [Product::VERSION_MAIN];
        $versionsPublished = [];
        $versionsUpdated = [];
        $productDirectory = $product->getDirectory();
        $masterDirectory = $product->getMainDirectory();

        $this->setExecutionStartTime();

        if (!$this->readyResourceDirectory($productDirectory)) {
            return $result->setError(sprintf('Product directory %s is not writable.', $productDirectory))
                ->setExtra((object)['executionTime' => $this->getExecutionTime()]);
        }

        if (!$this->filesystem->isDirectory($masterDirectory)) {
            $this->publishVersion($product, $source, Product::VERSION_MAIN);
            $versionsPublished[] = Product::VERSION_MAIN;
        } else {
            $this->updateVersion($product, Product::VERSION_MAIN);
            $versionsUpdated[] = Product::VERSION_MAIN;
        }

        $tagResult = $this->publishTags($product, $source);
        $tagData = $tagResult->getExtra();
        $versions = array_merge($versions, $tagData->tags ?? []);
        $versionsPublished = array_merge($versionsPublished, $tagData->tagsPublished ?? []);

        if ($result->isSuccess()) {
            $result = $result->setMessage(sprintf('%s was successfully published.', $product->getName()))
                ->setExtra(
                    (object)[
                        'versions' => $versions,
                        'versionsPublished' => $versionsPublished,
                        'versionsUpdated' => $versionsUpdated,
                        'executionTime' => $this->getExecutionTime(),
                    ]
                );
        }

        return $result;
    }

    /**
     * @param Product $product
     *
     * @return Result
     * @throws ProcessFailedException|LogicException
     */
    public function update(Product $product): Result
    {
        $result = new Result();
        $publishedVersions = array_keys($product->getVersions());
        $availableTags = $this->listAvailableProductTags($product);
        $source = $this->getProductSource($product);
        $branches = array_diff($publishedVersions, $availableTags);
        $unpublishedTags = array_diff($availableTags, $publishedVersions);
        $versions = array_unique(array_merge($publishedVersions, $availableTags));
        $versionsPublished = [];
        $versionsUpdated = [];

        foreach ($branches as $version) {
            try {
                $this->updateVersion($product, $version);
                $versionsUpdated[] = $version;
            } catch (Exception $exception) {
                $this->logger->info($exception->getMessage(), [$exception]);

                $result = $result->addMessage($exception->getMessage());
            }
        }

        $tagResult = $this->publishTags($product, $source, $unpublishedTags);
        $tagData = $tagResult->getExtra();
        $versionsPublished = array_merge($versionsPublished, $tagData->tagsPublished ?? []);

        if ($result->isSuccess()) {
            $result = $result->setMessage(sprintf('%s was successfully updated.', $product->getName()));
        }

        return $result->setExtra(
            (object)[
                'versions' => $versions,
                'versionsPublished' => $versionsPublished,
                'versionsUpdated' => $versionsUpdated,
            ]
        );
    }

    /**
     * @throws Exception
     */
    private function publishVersion(Product $product, string $source, string $version): void
    {
        try {
            $this->vcsCommandRunner->clone($source, $version, $product->getDirectory());
            $this->publishProductAssets($product, $version);
        } catch (ProcessFailedException $e) {
            throw PublicationFailedException::forProductVersion($product, $version, $e);
        }
    }

    /**
     * @throws Exception
     */
    private function updateVersion(Product $product, string $version): void
    {
        try {
            $this->vcsCommandRunner->pull(sprintf('%s/%s', $product->getDirectory(), $version));
            $this->publishProductAssets($product, $version);
        } catch (ProcessFailedException $e) {
            throw new PublicationFailedException(
                sprintf('Failed to update version `%s` of product `%s`.', $version, $product->getName())
            );
        }
    }

    private function publishTags(Product $product, string $source, array $tags = []): Result
    {
        $result = new Result();
        $tagsPublished = [];

        try {
            $tags = empty($tags) ? $this->listAvailableProductTags($product) : $tags;

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
        } catch (Exception | ProcessFailedException | LogicException $e) {
            $errorMessage = $e->getMessage();

            return $result->setMessage($errorMessage)->setError($errorMessage);
        }

        return $result->setExtra(
            (object)[
                'tags' => $tags,
                'tagsPublished' => $tagsPublished,
            ]
        );
    }

    private function publishProductAssets(Product $product, string $version): void
    {
        try {
            $product->publishAssets($version);
        } catch (Exception $exception) {
            if ($exception instanceof InvalidAssetDirectoryException) {
                $this->logger->info($exception->getMessage());
            } else {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * @throws ProcessFailedException|LogicException
     */
    private function listAvailableProductTags(Product $product): array
    {
        return $this->vcsCommandRunner->listTags($product->getMainDirectory());
    }

    /**
     * @throws ProcessFailedException|LogicException
     */
    private function getProductSource(Product $product): string
    {
        return $this->vcsCommandRunner->getRemoteUrl($product->getMainDirectory());
    }
}
