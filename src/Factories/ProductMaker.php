<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Factories;

use Illuminate\Filesystem\Filesystem;
use ReliQArts\Docweaver\Contracts\ConfigProvider;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Contracts\Product\Maker;
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
     * @throws Exception
     *
     * @return Product
     */
    public function create(string $directory): Product
    {
        return new Product($this->filesystem, $this->configProvider, $directory);
    }
}
