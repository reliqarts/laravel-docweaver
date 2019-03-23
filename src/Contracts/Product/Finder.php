<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contracts\Product;

use ReliqArts\Docweaver\Models\Product;

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
