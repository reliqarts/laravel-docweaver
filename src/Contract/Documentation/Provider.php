<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract\Documentation;

use ReliqArts\Docweaver\Model\Product;

interface Provider
{
    /**
     * Get documentation page for product.
     */
    public function getPage(Product $product, string $version, string $page = null): string;

    /**
     * Replace the version place-holder in links.
     */
    public function replaceLinks(Product $product, string $version, string $originalContent): string;

    /**
     * Check if the given section exists.
     */
    public function sectionExists(Product $product, string $version, string $page): bool;
}
