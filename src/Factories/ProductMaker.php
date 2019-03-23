<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Factories;

use ReliqArts\Docweaver\Contracts\ConfigProvider;
use ReliqArts\Docweaver\Contracts\Exception;
use ReliqArts\Docweaver\Contracts\Filesystem;
use ReliqArts\Docweaver\Contracts\Product\Maker;
use ReliqArts\Docweaver\Exceptions\InvalidDirectory;
use ReliqArts\Docweaver\Models\Product;

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

        $product = new Product($this->filesystem, $this->configProvider, $directory);
        $product->populate();

        return $product;
    }
}
