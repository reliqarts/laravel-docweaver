<?php

namespace ReliQArts\Docweaver\Contracts;

/**
 * Characteristics of a documented product.
 */
interface Product
{
    /**
     * Get default version for product.
     *
     * @param bool $allowWordedDefault Whether a worded version should be accepted as default.
     *
     * @return string
     */
    public function getDefaultVersion($allowWordedDefault = false);

    /**
     * Get product directory.
     *
     * @return string
     */
    public function getDir();

    /**
     * Get product name.
     *
     * @return string
     */
    public function getName();

    /**
     * Get product description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Get product image url.
     *
     * @return string
     */
    public function getImageUrl();

    /**
     * Set product image url.
     *
     * @param mixed $meta Meta or straight url to use.
     * @param string $version
     *
     * @return void
     */
    public function setImageUrl($meta, $version = null);

    /**
     * Get the publicly available versions of the product.
     *
     * @param string $product Name of product.
     *
     * @return array
     */
    public function getVersions();

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

    /**
     * Publish product public assets.
     *
     * @param string $version
     *
     * @return void
     */
    public function publishAssets($version = null);
}
