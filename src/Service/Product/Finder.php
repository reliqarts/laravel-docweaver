<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service\Product;

use ReliqArts\Docweaver\Contract\ConfigProvider;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\Filesystem;
use ReliqArts\Docweaver\Contract\Logger;
use ReliqArts\Docweaver\Contract\Product\Finder as FinderContract;
use ReliqArts\Docweaver\Contract\Product\Maker as ProductFactory;
use ReliqArts\Docweaver\Model\Product;

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
