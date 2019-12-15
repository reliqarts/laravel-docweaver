<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract\Product;

use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Model\Product;

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
