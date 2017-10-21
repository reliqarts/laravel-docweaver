<?php

namespace ReliQArts\Docweaver\Contracts;

/**
 * Characteristics of a documented product.
 */
interface Product
{
    /**
     * Get the publicly available versions of the product.
     *
     * @param string $product Name of product.
     *
     * @return array
     */
    public function getVersions();

    /**
     * Get default version for product.
     *
     * @param bool $allowWordedDefault Whether a worded version should be accepted as default.
     *
     * @return string
     */
    public function getDefaultVersion($allowWordedDefault = false);

    /**
     * Get last modified time.
     *
     * @return void
     */
    public function getLastModified();

    /**
     * Populate product (initialize or update product versions).
     *
     * @param string $product Name of product.
     *
     * @return array
     */
    public function populate();
}
