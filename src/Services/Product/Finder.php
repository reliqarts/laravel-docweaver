<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Services\Product;

use ReliQArts\Docweaver\Contracts\Filesystem;
use ReliQArts\Docweaver\Contracts\ConfigProvider;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Contracts\Logger;
use ReliQArts\Docweaver\Contracts\Product\Finder as FinderContract;
use ReliQArts\Docweaver\Contracts\Product\Maker as ProductFactory;
use ReliQArts\Docweaver\Models\Product;

final class Finder implements FinderContract
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * ProductFinder constructor.
     *
     * @param Filesystem     $filesystem
     * @param Logger         $logger
     * @param ConfigProvider $configProvider
     * @param ProductFactory $productFactory
     */
    public function __construct(
        Filesystem $filesystem,
        Logger $logger,
        ConfigProvider $configProvider,
        ProductFactory $productFactory
    ) {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->configProvider = $configProvider;
        $this->productFactory = $productFactory;
    }

    /**
     * @param bool $includeUnknowns
     *
     * @return Product[]
     */
    public function listProducts(bool $includeUnknowns = false): array
    {
        $products = [];
        $documentationDirectory = $this->configProvider->getDocumentationDirectory();
        $productDirectories = $this->filesystem->directories(base_path($documentationDirectory));

        foreach ($productDirectories as $productDirectory) {
            try {
                $product = $this->productFactory->create($productDirectory);
                if ($includeUnknowns || $product->getDefaultVersion() !== Product::VERSION_UNKNOWN) {
                    $products[$product->getKey()] = $product;
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage(), []);
            }
        }

        return $products;
    }

    /**
     * @param string $productName
     *
     * @return null|Product
     */
    public function findProduct(string $productName): ?Product
    {
        $productKey = strtolower($productName);
        $products = $this->listProducts();

        if (!array_key_exists($productKey, $products)) {
            return null;
        }

        return $products[$productKey];
    }
}
