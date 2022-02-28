<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Factory;

use ReliqArts\Docweaver\Contract\ConfigProvider;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\FileHelper;
use ReliqArts\Docweaver\Contract\Filesystem;
use ReliqArts\Docweaver\Contract\Product\Maker;
use ReliqArts\Docweaver\Contract\YamlHelper;
use ReliqArts\Docweaver\Exception\InvalidDirectoryException;
use ReliqArts\Docweaver\Model\Product;

final class ProductMaker implements Maker
{
    private ConfigProvider $configProvider;
    private Filesystem $filesystem;
    private FileHelper $fileHelper;
    private YamlHelper $yamlHelper;

    /**
     * Factory constructor.
     */
    public function __construct(
        Filesystem $filesystem,
        ConfigProvider $configProvider,
        FileHelper $fileHelper,
        YamlHelper $yamlHelper
    ) {
        $this->filesystem = $filesystem;
        $this->configProvider = $configProvider;
        $this->fileHelper = $fileHelper;
        $this->yamlHelper = $yamlHelper;
    }

    /**
     * @throws Exception if directory is invalid or product population fails
     */
    public function create(string $directory): Product
    {
        if (!$this->filesystem->isDirectory($directory)) {
            throw InvalidDirectoryException::forDirectory($directory);
        }

        $product = new Product(
            $this->filesystem,
            $this->configProvider,
            $this->fileHelper,
            $this->yamlHelper,
            $directory
        );

        $product->populate();

        return $product;
    }
}
