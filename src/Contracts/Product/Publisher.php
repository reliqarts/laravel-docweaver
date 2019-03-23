<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contracts\Product;

use ReliqArts\Docweaver\Contracts\Publisher as BasePublisher;
use ReliqArts\Docweaver\Exceptions\Product\PublicationFailed;
use ReliqArts\Docweaver\Models\Product;
use ReliqArts\Docweaver\VO\Result;

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
