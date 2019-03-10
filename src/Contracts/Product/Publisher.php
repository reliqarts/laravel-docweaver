<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Contracts\Product;

use ReliQArts\Docweaver\Contracts\Publisher as BasePublisher;
use ReliQArts\Docweaver\Exceptions\Product\PublicationFailed;
use ReliQArts\Docweaver\Models\Product;
use ReliQArts\Docweaver\VO\Result;

interface Publisher extends BasePublisher
{
    /**
     * Publish product documentation (all versions).
     *
     * @param Product $product
     * @param string  $source
     *
     * @throws PublicationFailed
     *
     * @return Result
     */
    public function publish(Product $product, string $source): Result;

    /**
     * Update product documentation (all versions).
     *
     * @param Product $product
     *
     * @return Result
     */
    public function update(Product $product): Result;
}
