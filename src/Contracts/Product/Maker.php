<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contracts\Product;

use ReliqArts\Docweaver\Contracts\Exception;
use ReliqArts\Docweaver\Models\Product;

interface Maker
{
    /**
     * @param string $directory
     *
     * @throws Exception
     *
     * @return Product
     */
    public function create(string $directory): Product;
}
