<?php

namespace ReliQArts\Docweaver\Models;

use Log;
use Storage;
use Carbon\Carbon;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Filesystem\Filesystem;
use ReliQArts\Docweaver\Traits\FileHandler;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Symfony\Component\Yaml\Exception\ParseException;
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
     * Product description.
     *
     * @var string
     */
    protected $description = null;

    /**
     * Product image url.
     *
     * @var string
     */
    protected $imageUrl = null;

    /**
     * Product resource directory.
     *
     * @var string
     */
    protected $dir = null;

    /**
     * Product meta (from file).
     *
     * @var array
     */
    protected $meta = [];

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
     * Placeholders to replace in documentation asset URL.
     *
     * @var array
     */
    public const ASSET_URL_PLACEHOLDERS = ['{{docs}}', '{{doc}}'];

    /**
    * Unknown version identifier.
    *
    * @var string
    */
    public const UNKNOWN_VERSION = 'unknown';

    /**
     * Product meta file.
     *
     * @var string
     */
    public const META_FILE = '.docweaver.yml';

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
     * Populate product versions and information.
     *
     * @return array
     */
    public function populate()
    {
        // load versions
        $this->loadVersions();
        // load config
        $this->loadMeta();

        return $this->toArray();
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
        return $this->dir;
    }

    /**
     * Get product name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get product description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get product image url.
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Set product image url.
     *
     * @param mixed $meta Meta or straight url to use.
     * @param string $version
     *
     * @return void
     */
    public function setImageUrl($meta, $version = null)
    {
        $url = '';
        $version = empty($version) ? $this->getDefaultVersion() : $version;

        if (is_array($meta)) {
            if (!empty($meta['image_url'])) {
                $url = $meta['image_url'];
            } elseif (!empty($meta['imageUrl'])) {
                $url = $meta['imageUrl'];
            } elseif ((!empty($meta['image']))) {
                $url = $meta['image'];
            }
        } elseif (is_string($meta)) {
            $url = $meta;
        }
        
        $this->imageUrl = $this->assetUrl($url, $version);
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
     * Convert url string to asset url relative to current product.
     *
     * @param string $url
     * @param string $version
     *
     * @return string
     */
    public function assetUrl($url = null, $version = null)
    {
        $url = empty($url) ? self::ASSET_URL_PLACEHOLDERS[0] : $url;
        $version = empty($version) ? $this->getDefaultVersion() : $version;

        // if url contains schema, ignore it
        if (strpos('http://', $url) === false && strpos('https://', $url) === false) {
            // build asset url
            $url = str_replace(self::ASSET_URL_PLACEHOLDERS, 'storage/'.Helper::getRoutePrefix()."/$this->key/$version", $url);
            $url = asset($url);
        }
        
        return $url;
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
     * Publish product public assets.
     *
     * @param string $version
     *
     * @return void
     */
    public function publishAssets($version = null)
    {
        $version = empty($version) ? $this->getDefaultVersion() : $version;
        $storagePath = storage_path('app/public/'.Helper::getRoutePrefix()."/$this->key/$version");
        
        // publish images
        if ($this->files->isDirectory("{$this->dir}/$version/images")) {
            if (!$this->files->copyDirectory("{$this->dir}/$version/images", "$storagePath/images")) {
                Log::error("Failed to publish image assets for product.", ['product' => $this]);
            }
        } else {
            Log::info("Skipped publishing image assets for product. No images directory.", ['product' => $this]);
        }
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'imageUrl' => $this->imageUrl,
            'directory' => $this->dir,
            'versions' => $this->versions,
            'defaultVersion' => $this->getDefaultVersion(),
            'lastModified' => $this->getLastModified(),
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    /**
     * Load meta onto product.
     *
     * @param string $version Version to load configuration from. (optional)
     *
     * @return void
     */
    private function loadMeta($version = null)
    {
        $version = empty($version) ? $this->getDefaultVersion() : $version;
        // load configuration file, if exists
        if ($metaFile = realpath("{$this->dir}/{$version}/".self::META_FILE)) {
            try {
                $this->meta = $meta = Yaml::parse(file_get_contents($metaFile));

                // set params from meta file
                if (!empty($meta['name'])) {
                    $this->name = $meta['name'];
                }
                if (!empty($meta['description'])) {
                    $this->description = $meta['description'];
                }
                $this->setImageUrl($meta, $version);
            } catch (ParseException $e) {
                Log::error("Unable to parse the YAML string: {$e->getMessage()}", ['product' => $this]);
            }
        }
    }

    /**
     * Load product versions.
     *
     * @return void
     */
    private function loadVersions()
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

        $this->versions = $versions;
    }
}
