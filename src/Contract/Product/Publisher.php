<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract\Product;

use ReliqArts\Docweaver\Contract\Publisher as BasePublisher;
use ReliqArts\Docweaver\Exception\Product\PublicationFailed;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Result;

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
