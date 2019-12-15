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
     * @throws PublicationFailed
     */
    public function publish(Product $product, string $source): Result;

    /**
     * Update product documentation (all versions).
     */
    public function update(Product $product): Result;
}
