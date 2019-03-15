<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Contracts\Documentation;

use ReliQArts\Docweaver\Models\Product;

interface Provider
{
    /**
     * Get documentation page for product.
     *
     * @param Product     $product
     * @param string      $version
     * @param null|string $page
     *
     * @return string
     */
    public function getPage(Product $product, string $version, string $page = null): string;

    /**
     * Replace the version place-holder in links.
     *
     * @param Product $product
     * @param string  $version
     * @param string  $content
     *
     * @return string
     */
    public function replaceLinks(Product $product, string $version, string $content): string;

    /**
     * Check if the given section exists.
     *
     * @param Product $product
     * @param string  $version
     * @param string  $page
     *
     * @return bool
     */
    public function sectionExists(Product $product, string $version, string $page): bool;
}
