<?php

namespace ReliQArts\Docweaver\Contracts;

/**
 * Characteristics of Docweaver publisher.
 */
interface Publisher 
{
    /**
     * Publish documentation for a particular product.
     *
     * @param string $name
     * @param string $source Git Repository
     * 
     * @return bool
     */
    public function publish($name, $source);

    /**
     * Update documentation for a particular product.
     *
     * @param string $name
     * 
     * @return bool
     */
    public function update($name);
}