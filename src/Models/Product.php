<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use ReliQArts\Docweaver\Contracts\ConfigProvider;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Contracts\Filesystem;
use ReliQArts\Docweaver\Exceptions\ParsingFailed;
use ReliQArts\Docweaver\Exceptions\Product\AssetPublicationFailed;
use ReliQArts\Docweaver\Exceptions\Product\InvalidAssetDirectory;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * A documented product.
 */
class Product implements Arrayable, Jsonable
{
    public const VERSION_MASTER = 'master';
    public const VERSION_UNKNOWN = 'unknown';

    private const ASSET_URL_PLACEHOLDERS = ['{{docs}}', '{{doc}}'];
    private const META_FILE = '.docweaver.yml';

    /**
     * Product key.
     *
     * @var string
     */
    private $key;

    /**
     * Filesystem.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * Last time product was modified (timestamp).
     *
     * @var int
     */
    private $lastModified;

    /**
     * Product name.
     *
     * @var string
     */
    private $name;

    /**
     * Product description.
     *
     * @var null|string
     */
    private $description;

    /**
     * Product image url.
     *
     * @var null|string
     */
    private $imageUrl;

    /**
     * Product resource directory.
     *
     * @var string
     */
    private $directory;

    /**
     * Product meta (from file).
     *
     * @var array
     */
    private $meta;

    /**
     * List of available product versions.
     *
     * @var array
     */
    private $versions;

    /**
     * Create product instance.
     *
     * @param Filesystem     $filesystem
     * @param ConfigProvider $configProvider
     * @param string         $directory
     *
     * @throws Exception if meta file could not be parsed
     */
    public function __construct(Filesystem $filesystem, ConfigProvider $configProvider, string $directory)
    {
        $this->filesystem = $filesystem;
        $this->configProvider = $configProvider;
        $this->name = Str::title(basename($directory));
        $this->key = strtolower($this->name);
        $this->directory = $directory;
        $this->meta = [];
        $this->versions = [];

        $this->populate();
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
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
        $versions = empty($this->versions) ? $this->getVersions() : $this->versions;
        $allowWordedDefault = $allowWordedDefault || $this->configProvider->isWordedDefaultVersionAllowed();
        $defaultVersion = self::VERSION_UNKNOWN;

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
    public function getDirectory(): string
    {
        return $this->directory;
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

        $this->imageUrl = $this->getAssetUrl($url, $version);
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
     *
     * @throws Exception if products asset directory is invalid or assets could not be published
     */
    public function publishAssets(string $version): void
    {
        $version = empty($version) ? $this->getDefaultVersion() : $version;
        $storagePath = storage_path(
            sprintf('app/public/%s/%s/%s', $this->configProvider->getRoutePrefix(), $this->key, $version)
        );
        $imageDirectory = sprintf('%s/%s/images', $this->directory, $version);

        if (!$this->filesystem->isDirectory($imageDirectory)) {
            throw InvalidAssetDirectory::forDirectory($imageDirectory);
        }

        if (!$this->filesystem->copyDirectory($imageDirectory, sprintf('%s/images', $storagePath))) {
            throw AssetPublicationFailed::forProductAssetsOfType($this, 'image');
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
            'directory' => $this->directory,
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
     * Convert url string to asset url relative to current product.
     *
     * @param string $url
     * @param string $version
     *
     * @return string
     */
    private function getAssetUrl(string $url = null, string $version = null): string
    {
        $url = empty($url) ? self::ASSET_URL_PLACEHOLDERS[0] : $url;
        $version = empty($version) ? $this->getDefaultVersion() : $version;

        // if url contains schema, ignore it
        if (strpos('http://', $url) === false && strpos('https://', $url) === false) {
            // build asset url
            $url = str_replace(
                self::ASSET_URL_PLACEHOLDERS,
                sprintf('storage/%s/%s/%s', $this->configProvider->getRoutePrefix(), $this->key, $version),
                $url
            );
            $url = asset($url);
        }

        return $url;
    }

    /**
     * Load meta onto product.
     *
     * @param string $version Version to load configuration from. (optional)
     *
     * @throws Exception if meta file could not be parsed
     */
    private function loadMeta(string $version = null): void
    {
        $version = empty($version) ? $this->getDefaultVersion() : $version;
        $metaFile = realpath(sprintf('%s/%s/%s', $this->directory, $version, self::META_FILE));

        if (empty($metaFile)) {
            return;
        }

        try {
            $meta = Yaml::parse(file_get_contents($metaFile));

            if (!empty($meta['name'])) {
                $this->name = $meta['name'];
            }
            if (!empty($meta['description'])) {
                $this->description = $meta['description'];
            }

            $this->setImageUrl($meta, $version);
            $this->meta = $meta;
        } catch (ParseException $exception) {
            $message = sprintf(
                'Failed to parse meta file `%s`. %s',
                $metaFile,
                $exception->getMessage()
            );

            throw ParsingFailed::forFile($metaFile)->withMessage($message);
        }
    }

    /**
     * Load product versions.
     */
    private function loadVersions(): void
    {
        $versions = [];

        if ($this->key) {
            $versionDirs = $this->filesystem->directories($this->directory);

            // add versions to version array
            foreach ($versionDirs as $ver) {
                $versionTag = basename($ver);
                $versionName = Str::title($versionTag);
                $versions[$versionTag] = $versionName;
            }

            // update last modified
            $this->lastModified = $this->filesystem->lastModified($this->directory);

            // sort versions
            krsort($versions);
        }

        $this->versions = $versions;
    }

    /**
     * Populate product versions and information.
     *
     * @throws Exception
     */
    private function populate(): void
    {
        $this->loadVersions();
        $this->loadMeta();
    }
}
