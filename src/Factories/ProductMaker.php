<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Factories;

use ReliQArts\Docweaver\Contracts\ConfigProvider;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Contracts\Filesystem;
use ReliQArts\Docweaver\Contracts\Product\Maker;
use ReliQArts\Docweaver\Exceptions\InvalidDirectory;
use ReliQArts\Docweaver\Models\Product;

final class ProductMaker implements Maker
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Factory constructor.
     *
     * @param Filesystem     $filesystem
     * @param ConfigProvider $configProvider
     */
    public function __construct(Filesystem $filesystem, ConfigProvider $configProvider)
    {
        $this->filesystem = $filesystem;
        $this->configProvider = $configProvider;
    }

    /**
     * @param string $directory
     *
     * @throws Exception if directory is invalid
     *
     * @return Product
     */
    public function create(string $directory): Product
    {
        if (!$this->filesystem->isDirectory($directory)) {
            throw InvalidDirectory::forDirectory($directory);
        }

        return new Product($this->filesystem, $this->configProvider, $directory);
    }
}
