<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service\Documentation;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Psr\SimpleCache\InvalidArgumentException;
use ReliqArts\Docweaver\Contract\ConfigProvider;
use ReliqArts\Docweaver\Contract\Documentation\Provider as ProviderContract;
use ReliqArts\Docweaver\Contract\Filesystem;
use ReliqArts\Docweaver\Contract\MarkdownParser;
use ReliqArts\Docweaver\Exception\BadImplementation;
use ReliqArts\Docweaver\Model\Product;

final class Provider implements ProviderContract
{
    private const CACHE_TIMEOUT_SECONDS = 60 * 5;
    private const PAGE_INDEX = 'index';
    private const FILE_EXTENSION = 'md';
    private const VERSION_PLACEHOLDER = '{{version}}';

    /**
     * The cache implementation.
     *
     * @var Cache
     */
    private Cache $cache;

    /**
     * The cache key.
     *
     * @var string
     */
    private string $cacheKey;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var MarkdownParser
     */
    private MarkdownParser $markdownParser;

    /**
     * Create a new documentation instance.
     *
     * @throws BadImplementation
     */
    public function __construct(
        Filesystem $filesystem,
        Cache $cache,
        ConfigProvider $configProvider,
        MarkdownParser $markdownParser
    ) {
        $this->filesystem = $filesystem;
        $this->cache = $cache;
        $this->configProvider = $configProvider;
        $this->cacheKey = $this->configProvider->getCacheKey();
        $this->markdownParser = $markdownParser;
        $documentationDirectory = $configProvider->getDocumentationDirectory();
        $documentationDirectoryAbsolutePath = base_path($documentationDirectory);

        if (!$this->filesystem->isDirectory($documentationDirectoryAbsolutePath)) {
            throw new BadImplementation(sprintf('Documentation resource directory `%s` does not exist.', $documentationDirectory));
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws FileNotFoundException
     */
    public function getPage(Product $product, string $version, string $page = null): string
    {
        $page = $page ?? self::PAGE_INDEX;
        $cacheKey = sprintf('%s.%s.%s.%s', $this->cacheKey, $product->getKey(), $version, $page);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $pageContent = $this->getPageContent($product, $version, $page);

        $this->cache->put($cacheKey, $pageContent, self::CACHE_TIMEOUT_SECONDS);

        return $pageContent;
    }

    public function replaceLinks(Product $product, string $version, string $originalContent): string
    {
        $routePrefix = $this->configProvider->getRoutePrefix();
        $versionPlaceholder = urlencode(self::VERSION_PLACEHOLDER);
        $originalLinkPath = sprintf('docs/%s', $versionPlaceholder);
        $linkPathReplacement = sprintf('%s/%s/%s', $routePrefix, $product->getKey(), $version);

        return str_replace([$originalLinkPath, $versionPlaceholder], [$linkPathReplacement, $version], $originalContent);
    }

    public function sectionExists(Product $product, string $version, string $page): bool
    {
        $filePath = $this->getFilePathForProductPage($product, $version, $page);

        return $this->filesystem->exists($filePath);
    }

    private function getFilePathForProductPage(Product $product, string $version, string $page): string
    {
        $directory = $product->getDirectory();
        $filename = ($page === self::PAGE_INDEX) ? $this->configProvider->getContentIndexPageName() : $page;

        return sprintf('%s/%s/%s.%s', $directory, $version, $filename, self::FILE_EXTENSION);
    }

    /**
     * @throws FileNotFoundException
     */
    private function getPageContent(Product $product, string $version, string $page): string
    {
        $filePath = $this->getFilePathForProductPage($product, $version, $page);
        $pageContent = '';

        if ($this->filesystem->exists($filePath)) {
            $fileContents = $this->filesystem->get($filePath);
            $pageContent = $this->replaceLinks(
                $product,
                $version,
                $this->markdownParser->parse($fileContents)
            );
        }

        return $pageContent;
    }
}
