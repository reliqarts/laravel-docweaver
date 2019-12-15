<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract\Product;

use ReliqArts\Docweaver\Model\Product;

interface Finder
{
    /**
     * List available products.
     *
     * @param bool $includeUnknowns whether to include products with unknown version
     *
     * @return Product[]
     */
    public function listProducts(bool $includeUnknowns = false): array;

    /**
     * Get product.
     *
     * @param string $productName
     *
     * @return null|Product
     */
    public function findProduct(string $productName): ?Product;
}
