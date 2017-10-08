<?php

namespace ReliQArts\DocWeaver\Models;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Cache\Repository as Cache;
use ReliQArts\DocWeaver\Contracts\ProductDocumentor;
use ReliQArts\DocWeaver\Helpers\CoreHelper as Helper;
use ReliQArts\DocWeaver\Exceptions\ImplementationException;

class Documentation implements ProductDocumentor
{
    /**
     * The filesystem implementation.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * The cache implementation.
     *
     * @var Cache
     */
    protected $cache;

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
     * Directory separator.
     *
     * @var string
     */
    protected $sep;

    /**
     * Unknown version identifier.
     * 
     * @var UNKNOWN_VERSION
     */
    public const UNKNOWN_VERSION = 'unknown';

    /**
     * Format path correctly based on OS.
     * i.e. using DIRECTORY_SEPARATOR.
     *
     * @param string $path
     * @return string
     */
    private function dirPath($path)
    {
        return str_replace(['/', '\\'], $this->sep, $path);
    }

    /**
     * Create a new documentation instance.
     *
     * @param  Filesystem  $files
     * @param  Cache  $cache
     * @throws ReliQArts\DocWeaver\Exceptions\ImplementationException
     * 
     * @return void
     */
    public function __construct(Filesystem $files, Cache $cache)
    {
        $this->files = $files;
        $this->cache = $cache;
        $this->config = Helper::getConfig();
        $this->docsDir = Helper::getDocsDir();
        $this->sep = DIRECTORY_SEPARATOR;

        $docsDirAbsPath = base_path($this->docsDir);
        if (! $this->files->isDirectory($docsDirAbsPath)) {
            throw new ImplementationException("Documentation resource directory ({$this->docsDir}) does not exist. [{$docsDirAbsPath}]");
        }
    }

    /**
     * Get the documentation index page.
     *
     * @param  string  $product
     * @param  string  $version
     * 
     * @return string
     */
    public function getIndex($product, $version)
    {
        $this->currentProduct = $product;
        $config = $this->config;

        return $this->cache->remember("doc-weaver.docs.{$product}.{$version}.index", 5, function () use ($product, $version, $config) {
            $path = base_path("{$this->docsDir}/{$product}/{$version}/{$config['doc']['index']}.md");

            if ($this->files->exists($path)) {
                return $this->replaceLinks($version, Helper::markdown($this->files->get($path)));
            }
        });
    }

    /**
     * Get the given documentation page.
     *
     * @param  string  $product
     * @param  string  $version
     * @param  string  $page
     * 
     * @return string
     */
    public function get($product, $version, $page)
    {
        $this->currentProduct = $product;

        return $this->cache->remember("doc-weaver.docs.{$product}.{$version}.{$page}", 5, function () use ($product, $version, $page) {
            $path = base_path("{$this->docsDir}/{$product}/{$version}/{$page}.md");

            if ($this->files->exists($path)) {
                return $this->replaceLinks($version, Helper::markdown($this->files->get($path)));
            }
        });
    }

    /**
     * Replace the version place-holder in links.
     *
     * @param  string  $version
     * @param  string  $content
     * @return string
     */
    public function replaceLinks($version, $content)
    {
        $routePrefix = Helper::getRoutePrefix();
        // ensure product name exists in url
        if (! empty($this->currentProduct)) {
            $content = str_replace('docs/{{version}}', "$routePrefix/{$this->currentProduct}/$version", $content);
        }

        return str_replace('{{version}}', $version, $content);
    }

    /**
     * Check if the given section exists.
     *
     * @param  string  $product
     * @param  string  $version
     * @param  string  $page
     * 
     * @return bool
     */
    public function sectionExists($product, $version, $page)
    {
        $this->currentProduct = $product;

        return $this->files->exists(
            base_path("{$this->docsDir}/{$product}/{$version}/{$page}.md")
        );
    }

    /**
     * Get the publicly available versions of the documentation.
     *
     * @param string $product Name of product.
     * 
     * @return array
     */
    public function getDocVersions($product = null)
    {
        $versions = [];

        if ($product) {
            $this->currentProduct = $product;
            $productDirectory = base_path("{$this->docsDir}{$this->sep}{$product}");

            if ($this->files->isDirectory($productDirectory)) {
                $versionDirs = $this->files->directories($productDirectory);
                // add versions to version array
                foreach ($versionDirs as $ver) {
                    $versionTag = basename($ver);
                    $versionName = title_case($versionTag);
                    $versions[$versionTag] = $versionName;
                }
            }

            // sort versions
            krsort($versions);
        }

        return $versions;
    }

    /**
     * Get default doc version for product.
     *
     * @param string $product
     * @param bool $allowWordedDefault Whether a worded version should be accepted as default.
     * 
     * @return string
     */
    public function getDefaultVersion($product, $allowWordedDefault = false)
    {
        $versions = $this->getDocVersions($product);
        $this->currentProduct = $product;
        $allowWordedDefault = $allowWordedDefault || $this->config['versions']['allow_worded_default'];
        $defaultVersion = self::UNKNOWN_VERSION;

        foreach ($versions as $tag => $ver) {
            if (! $allowWordedDefault) {
                if (is_numeric($tag)) {
                    $defaultVersion = $tag;
                    break;
                }
            } else {
                $defaultVersion = $tag;
                break;
            }
        }

        return $defaultVersion;
    }

    /**
     * List available products.
     *
     * @param bool $includeUnkowns Whether to include products with unkown version.
     * 
     * @return void
     */
    public function listProducts($includeUnknowns = false)
    {
        $products = [];
        $productDirectories = $this->files->directories(base_path($this->docsDir));

        foreach ($productDirectories as $prod) {
            $productName = title_case(basename($prod));
            $product = [
                'key' => strtolower($productName),
                'name' => $productName,
                'directory' => $this->dirPath($prod),
                'versions' => $this->getDocVersions($productName),
                'defaultVersion' => $this->getDefaultVersion($productName),
                'lastModified' => Carbon::createFromTimestamp($this->files->lastModified($prod)),
            ];
            if ($includeUnknowns || $product['defaultVersion'] != self::UNKNOWN_VERSION) {
                $products[strtolower($productName)] = $product;
            }
        }

        return $products;
    }

    /**
     * Check whether product exists.
     *
     * @param string $product
     * 
     * @return bool
     */
    public function productExists($product)
    {
        $products = $this->listProducts();

        return array_key_exists($product, $products);
    }

    /**
     * Get product info.
     *
     * @param string $product
     * 
     * @return bool
     */
    public function getProduct($product)
    {
        $products = $this->listProducts();

        return array_key_exists($product, $products) ? $products[$product] : null;
    }
}
