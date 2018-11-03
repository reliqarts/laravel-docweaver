<?php

namespace ReliQArts\Docweaver\Models;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Filesystem\Filesystem;
use Log;
use ReliQArts\Docweaver\Exceptions\BadImplementation;
use ReliQArts\Docweaver\Exceptions\InvalidDirectory;
use ReliQArts\Docweaver\Helpers\Config;
use ReliQArts\Docweaver\Helpers\Markdown;
use ReliQArts\Docweaver\Traits\HandlesFiles;

class Documentation
{
    use HandlesFiles;

    /**
     * The cache implementation.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * The cache key.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Documentation configuration array.
     *
     * @var Cache
     */
    protected $config;

    /**
     * Current product.
     *
     * @var string
     */
    protected $currentProduct;

    /**
     * Documentation resource directory.
     *
     * @var string
     */
    protected $docsDir;

    /**
     * Create a new documentation instance.
     *
     * @param Filesystem $files
     * @param Cache      $cache
     *
     * @throws ReliQArts\Docweaver\Exceptions\BadImplementation
     */
    public function __construct(Filesystem $files, Cache $cache)
    {
        $this->files = $files;
        $this->cache = $cache;
        $this->config = Config::getConfig();
        $this->docsDir = Config::getDocsDir();
        $this->cacheKey = $this->config['cache']['key'];

        $docsDirAbsPath = base_path($this->docsDir);
        if (!$this->files->isDirectory($docsDirAbsPath)) {
            throw new BadImplementation("Documentation resource directory ({$this->docsDir}) does not exist. [{$docsDirAbsPath}]");
        }
    }

    /**
     * Get the documentation index page.
     *
     * @param Product $product
     * @param string  $version
     *
     * @return string
     */
    public function getIndex(Product $product, string $version): string
    {
        $this->currentProduct = $product;
        $config = $this->config;
        $indexCacheKey = "{$this->cacheKey}.{$product->key}.{$version}.index";

        return $this->cache->remember($indexCacheKey, 5, function () use ($product, $version, $config) {
            $path = "{$product->getDir()}/{$version}/{$config['doc']['index']}.md";

            if ($this->files->exists($path)) {
                return $this->replaceLinks($version, Markdown::parse($this->files->get($path)));
            }
        });
    }

    /**
     * Get the given documentation page.
     *
     * @param Product $product
     * @param string  $version
     * @param string  $page
     *
     * @return string
     */
    public function getPage(Product $product, string $version, string $page): string
    {
        $this->currentProduct = $product;
        $pageCacheKey = "{$this->cacheKey}.{$product->key}.{$version}.{$page}";

        return $this->cache->remember($pageCacheKey, 5, function () use ($product, $version, $page) {
            $path = "{$product->getDir()}/{$version}/{$page}.md";

            if ($this->files->exists($path)) {
                return $this->replaceLinks($version, Markdown::parse($this->files->get($path)));
            }
        });
    }

    /**
     * Replace the version place-holder in links.
     *
     * @param string $version
     * @param string $content
     *
     * @return string
     */
    public function replaceLinks(string $version, string $content): string
    {
        $routePrefix = Config::getRoutePrefix();
        // ensure product name exists in url
        if (!empty($this->currentProduct)) {
            $content = str_replace('docs/{{version}}', "${routePrefix}/{$this->currentProduct->key}/${version}", $content);
        }

        return str_replace('{{version}}', $version, $content);
    }

    /**
     * Check if the given section exists.
     *
     * @param Product $product
     * @param string  $version
     * @param string  $page
     *
     * @return bool
     */
    public function sectionExists(Product $product, string $version, string $page): bool
    {
        $this->currentProduct = $product;

        return $this->files->exists("{$product->getDir()}/{$version}/{$page}.md");
    }

    /**
     * List available products.
     *
     * @param bool $includeUnkowns whether to include products with unkown version
     *
     * @return array
     */
    public function listProducts(bool $includeUnknowns = false): array
    {
        $products = [];
        $productDirectories = $this->files->directories(base_path($this->docsDir));

        foreach ($productDirectories as $prod) {
            try {
                $product = new Product($prod);
                if ($includeUnknowns || $product->getDefaultVersion() !== Product::UNKNOWN_VERSION) {
                    $products[$product->key] = $product;
                }
            } catch (InvalidDirectory $e) {
                // log error
                Log::error($e->getMessage(), []);
            }
        }

        return $products;
    }

    /**
     * Check whether product exists.
     *
     * @param string $productName
     * @param bool   $returnProduct
     *
     * @return bool|Product
     */
    public function productExists(string $productName, bool $returnProduct = false)
    {
        $productKey = strtolower($productName);
        $products = $this->listProducts();
        $exists = array_key_exists($productKey, $products);

        if ($exists && $returnProduct) {
            $exists = $products[$productKey];
        }

        return $exists;
    }

    /**
     * Get product info.
     *
     * @param string $productName
     *
     * @return bool|Product
     */
    public function getProduct(string $productName)
    {
        return $this->productExists($productName, true);
    }
}
