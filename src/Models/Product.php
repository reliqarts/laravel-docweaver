<?php

namespace ReliQArts\Docweaver\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Filesystem\Filesystem;
use Log;
use ReliQArts\Docweaver\Exceptions\InvalidDirectory;
use ReliQArts\Docweaver\Helpers\Config;
use ReliQArts\Docweaver\Traits\HandlesFiles;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * A documented product.
 */
class Product implements Arrayable, Jsonable
{
    use HandlesFiles;

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
     * Product key.
     *
     * @var string
     */
    public $key;

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
    protected $name;

    /**
     * Product description.
     *
     * @var null|string
     */
    protected $description;

    /**
     * Product image url.
     *
     * @var null|string
     */
    protected $imageUrl;

    /**
     * Product resource directory.
     *
     * @var string
     */
    protected $dir;

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
     * Create product instance.
     *
     * @param string $dir documentation directory
     */
    public function __construct(string $dir)
    {
        $this->files = resolve(Filesystem::class);

        if (!$this->files->isDirectory($dir)) {
            throw new InvalidDirectory($dir);
        }

        // proceed with build
        $this->name = title_case(basename($dir));
        $this->key = strtolower($this->name);
        $this->dir = $this->dirPath($dir);

        // populate product
        $this->populate();
    }

    /**
     * Populate product versions and information.
     *
     * @return array
     */
    public function populate(): array
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
     * @param bool $allowWordedDefault whether a worded version should be accepted as default
     *
     * @return string
     */
    public function getDefaultVersion(bool $allowWordedDefault = false): string
    {
        $docweaverConfig = Config::getConfig();
        $versions = empty($this->versions) ? $this->getVersions() : $this->versions;
        $allowWordedDefault = $allowWordedDefault || $docweaverConfig['versions']['allow_worded_default'];
        $defaultVersion = self::UNKNOWN_VERSION;

        foreach ($versions as $tag => $ver) {
            if (!$allowWordedDefault) {
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
    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * Get product name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get product description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get product image url.
     *
     * @return string
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * Set product image url.
     *
     * @param array|string $meta    meta or straight url to use
     * @param string       $version
     */
    public function setImageUrl($meta, string $version = null): void
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
    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * Get last modified time.
     *
     * @return Carbon
     */
    public function getLastModified(): Carbon
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
    public function assetUrl(string $url = null, string $version = null): string
    {
        $url = empty($url) ? self::ASSET_URL_PLACEHOLDERS[0] : $url;
        $version = empty($version) ? $this->getDefaultVersion() : $version;

        // if url contains schema, ignore it
        if (strpos('http://', $url) === false && strpos('https://', $url) === false) {
            // build asset url
            $url = str_replace(self::ASSET_URL_PLACEHOLDERS, 'storage/' . Config::getRoutePrefix() . "/{$this->key}/${version}", $url);
            $url = asset($url);
        }

        return $url;
    }

    /**
     * Determine if the given string is a valid version.
     *
     * @param string $version
     *
     * @return bool
     */
    public function hasVersion(string $version): bool
    {
        return in_array($version, array_keys($this->getVersions()), true);
    }

    /**
     * Publish product public assets.
     *
     * @param string $version
     */
    public function publishAssets(string $version = null): void
    {
        $version = empty($version) ? $this->getDefaultVersion() : $version;
        $storagePath = storage_path('app/public/' . Config::getRoutePrefix() . "/{$this->key}/${version}");

        // publish images
        if ($this->files->isDirectory("{$this->dir}/${version}/images")) {
            if (!$this->files->copyDirectory("{$this->dir}/${version}/images", "${storagePath}/images")) {
                Log::error('Failed to publish image assets for product.', ['product' => $this]);
            }
        } else {
            Log::info('Skipped publishing image assets for product. No images directory.', ['product' => $this]);
        }
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
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
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Load meta onto product.
     *
     * @param string $version Version to load configuration from. (optional)
     */
    private function loadMeta(string $version = null): void
    {
        $version = empty($version) ? $this->getDefaultVersion() : $version;
        // load configuration file, if exists
        if ($metaFile = realpath("{$this->dir}/{$version}/" . self::META_FILE)) {
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
     */
    private function loadVersions(): void
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
