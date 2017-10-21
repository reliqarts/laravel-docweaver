<?php

namespace ReliQArts\Docweaver\Models;

use Log;
use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use ReliQArts\Docweaver\Traits\FileHandler;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use ReliQArts\Docweaver\Helpers\CoreHelper as Helper;
use ReliQArts\Docweaver\Exceptions\BadProductException;
use ReliQArts\Docweaver\Contracts\Product as ProductContract;

/**
 * A documented product.
 */
class Product implements Arrayable, Jsonable, ProductContract
{
    use FileHandler;

    /**
     * Filesystem implementation.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Last time product was modified (timestamp).
     *
     * @var int
     */
    protected $lastModified;

    /**
     * Product name.
     *
     * @var string
     */
    protected $name = null;

    /**
     * Product resource directory.
     *
     * @var string
     */
    protected $dir = null;

    /**
     * List of available product versions.
     *
     * @var array
     */
    protected $versions = [];

    /**
     * Product key.
     *
     * @var string
     */
    public $key = null;

     /**
     * Unknown version identifier.
     *
     * @var UNKNOWN_VERSION
     */
    public const UNKNOWN_VERSION = 'unknown';

    /**
     * Create product instance.
     *
     * @param string $dir Documentation directory.
     */
    public function __construct($dir)
    {
        $this->files  = resolve(Filesystem::class);

        if (!$this->files->isDirectory($dir)) {
            $error = "Could not instantiate product, documentation directory ({$dir}) does not exist.";
            Log::error($error, []);
            throw new BadProductException($error);
        }

        // proceed with build
        $this->name = title_case(basename($dir));
        $this->key  = strtolower($this->name);
        $this->dir  = $this->dirPath($dir);
        
        // populate product
        $this->populate();
    }

    /**
     * Populate product (initialize or update product versions).
     *
     * @param string $product Name of product.
     *
     * @return array
     */
    public function populate()
    {
        $versions = [];

        if ($this->key) {
            if ($this->files->isDirectory($this->dir)) {
                $versionDirs = $this->files->directories($this->dir);
                // add versions to version array
                foreach ($versionDirs as $ver) {
                    $versionTag = basename($ver);
                    $versionName = title_case($versionTag);
                    $versions[$versionTag] = $versionName;
                }

                // update last modified
                $this->lastModified = $this->files->lastModified($this->dir);
            }

            // sort versions
            krsort($versions);
        }

        return $this->versions = $versions;
    }

    /**
     * Get default version for product.
     *
     * @param bool $allowWordedDefault Whether a worded version should be accepted as default.
     *
     * @return string
     */
    public function getDefaultVersion($allowWordedDefault = false)
    {
        $docweaverConfig = Helper::getConfig();
        $versions = empty($this->versions) ? $this->getVersions() : $this->versions;
        $allowWordedDefault = $allowWordedDefault || $docweaverConfig['versions']['allow_worded_default'];
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
     * Get product directory.
     *
     * @return string
     */
    public function getDir()
    {
        return$this->dir;
    }

    /**
     * Get product name.
     *
     * @return string
     */
    public function getName()
    {
        return$this->name;
    }

    /**
     * Get the publicly available versions of the product.
     *
     * @return array
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * Get last modified time.
     *
     * @return void
     */
    public function getLastModified()
    {
        return Carbon::createFromTimestamp($this->lastModified);
    }

    /**
     * Determine if the given string is a valid version.
     *
     * @param  string  $version
     *
     * @return bool
     */
    public function hasVersion($version)
    {
        return in_array($version, array_keys($this->getVersions()));
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'directory' => $this->dir,
            'versions' => $this->versions,
            'defaultVersion' => $this->getDefaultVersion(),
            'lastModified' => $this->getLastModified(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }
}