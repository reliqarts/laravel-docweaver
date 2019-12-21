<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Factory;

use ReliqArts\Docweaver\Contract\ConfigProvider;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\Filesystem;
use ReliqArts\Docweaver\Contract\Product\Maker;
use ReliqArts\Docweaver\Exception\InvalidDirectory;
use ReliqArts\Docweaver\Model\Product;

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
     */
    public function __construct(Filesystem $filesystem, ConfigProvider $configProvider)
    {
        $this->filesystem = $filesystem;
        $this->configProvider = $configProvider;
    }

    /**
     * @throws Exception if directory is invalid or product population fails
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
