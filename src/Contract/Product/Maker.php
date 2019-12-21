<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract\Product;

use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Model\Product;

interface Maker
{
    /**
     * @throws Exception if directory is invalid or product population fails
     */
    public function create(string $directory): Product;
}
