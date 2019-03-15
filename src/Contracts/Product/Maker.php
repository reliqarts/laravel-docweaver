<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Contracts\Product;

use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Models\Product;

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
