<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Model;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use JsonException;
use ReliqArts\Docweaver\Contract\ConfigProvider;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\FileHelper;
use ReliqArts\Docweaver\Contract\Filesystem;
use ReliqArts\Docweaver\Contract\YamlHelper;
use ReliqArts\Docweaver\Exception\ParsingFailedException;
use ReliqArts\Docweaver\Exception\Product\AssetPublicationFailedException;
use ReliqArts\Docweaver\Exception\Product\InvalidAssetDirectoryException;
use RuntimeException;

/**
 * A documented product.
 */
class Product implements Arrayable, Jsonable
{
    public const VERSION_MAIN = 'main';
    public const VERSION_UNKNOWN = 'unknown';

    private const ASSET_URL_PLACEHOLDER_1 = '{{docs}}';
    private const ASSET_URL_PLACEHOLDER_2 = '{{doc}}';
    private const ASSET_URL_PLACEHOLDERS = [
        self::ASSET_URL_PLACEHOLDER_1,
        self::ASSET_URL_PLACEHOLDER_2,
    ];
    private const META_FILE = '.docweaver.yml';

    /**
     * Product key.
     */
    private string $key;
    private Filesystem $filesystem;
    private ConfigProvider $configProvider;
    private YamlHelper $yamlHelper;
    private FileHelper $fileHelper;

    /**
     * Last time product was modified (timestamp).
     */
    private int $lastModified = 0;

    /**
     * Product name.
     */
    private string $name;

    /**
     * Product description.
     */
    private string $description = '';

    /**
     * Product image url.
     */
    private string $imageUrl = '';

    /**
     * List of available product versions.
     */
    private array $versions = [];

    /**
     * Product resource directory.
     *
     * @var string
     */
    private string $directory;

    /**
     * Create product instance.
     */
    public function __construct(
        Filesystem $filesystem,
        ConfigProvider $configProvider,
        FileHelper $fileHelper,
        YamlHelper $yamlHelper,
        string $directory
    ) {
        $this->filesystem = $filesystem;
        $this->configProvider = $configProvider;
        $this->fileHelper = $fileHelper;
        $this->yamlHelper = $yamlHelper;
        $this->name = Str::title(basename($directory));
        $this->key = strtolower($this->name);
        $this->directory = $directory;
    }

    /**
     * Populate product.
     *
     * @throws Exception if meta file could not be parsed
     */
    public function populate(): void
    {
        $this->loadVersions();
        $this->loadMeta();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get default version for product.
     *
     * @param bool $allowWordedDefault whether a worded version should be accepted as default
     */
    public function getDefaultVersion(bool $allowWordedDefault = false): string
    {
        $versions = empty($this->versions) ? $this->getVersions() : $this->versions;
        $allowWordedDefault = $allowWordedDefault || $this->configProvider->isWordedDefaultVersionAllowed();
        $defaultVersion = self::VERSION_UNKNOWN;

        foreach (array_keys($versions) as $tag) {
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
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Get product name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get product description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get product image url.
     */
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * Get the publicly available versions of the product.
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * Get last modified time.
     */
    public function getLastModified(): Carbon
    {
        return Carbon::createFromTimestamp($this->lastModified);
    }

    /**
     * Determine if the given string is a valid version.
     */
    public function hasVersion(string $version): bool
    {
        return array_key_exists($version, $this->getVersions());
    }

    /**
     * Publish product public assets.
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
            throw InvalidAssetDirectoryException::forDirectory($imageDirectory);
        }

        if (!$this->filesystem->copyDirectory($imageDirectory, sprintf('%s/images', $storagePath))) {
            throw AssetPublicationFailedException::forProductAssetsOfType($this, 'image');
        }
    }

    /**
     * Get the instance as an array.
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
     * @throws JsonException
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR, 512);
    }

    public function getMainDirectory(): string
    {
        return sprintf('%s/%s', $this->getDirectory(), self::VERSION_MAIN);
    }

    /**
     * Convert url string to asset url relative to current product.
     */
    private function getAssetUrl(string $url, string $version): string
    {
        $url = empty($url) ? self::ASSET_URL_PLACEHOLDER_1 : $url;

        if (stripos($url, 'http') === 0) {
            return $url;
        }

        return asset(
            str_replace(
                self::ASSET_URL_PLACEHOLDERS,
                sprintf('storage/%s/%s/%s', $this->configProvider->getRoutePrefix(), $this->key, $version),
                $url
            )
        );
    }

    /**
     * Load meta onto product.
     *
     * @param string|null $version Version to load configuration from. (optional)
     *
     * @throws Exception if meta file could not be parsed
     */
    private function loadMeta(?string $version = null): void
    {
        $version = empty($version) ? $this->getDefaultVersion() : $version;
        $metaFile = $this->fileHelper->realPath(sprintf('%s/%s/%s', $this->directory, $version, self::META_FILE));

        if (empty($metaFile)) {
            return;
        }

        try {
            $meta = $this->yamlHelper->parse($this->fileHelper->getFileContents($metaFile));

            if (!empty($meta['name'])) {
                $this->name = $meta['name'];
            }
            if (!empty($meta['description'])) {
                $this->description = $meta['description'];
            }

            $this->imageUrl = $this->getAssetUrl($meta['image_url'] ?? '', $version);
        } catch (RuntimeException $exception) {
            $message = sprintf(
                'Failed to parse meta file `%s`. %s',
                $metaFile,
                $exception->getMessage()
            );

            throw ParsingFailedException::forFile($metaFile)->withMessage($message);
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
}
