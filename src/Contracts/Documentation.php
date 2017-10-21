<?php

namespace ReliQArts\Docweaver\Contracts;

interface Documentation
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
    public function getPage($product, $version, $page);
}
