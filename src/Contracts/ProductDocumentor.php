<?php

namespace ReliQArts\Docweaver\Contracts;

interface ProductDocumentor
{
    /**
     * Get the documentation index page.
     *
     * @param  string  $product
     * @param  string  $version
     * @return string
     */
    public function getIndex($product, $version);

    /**
     * Get the given documentation page.
     *
     * @param  string  $product
     * @param  string  $version
     * @param  string  $page
     * @return string
     */
    public function get($product, $version, $page);

    /**
     * Get the publicly available versions of the documentation.
     *
     * @param  string  $product Name of product
     * @return array
     */
    public function getDocVersions($product);
}
