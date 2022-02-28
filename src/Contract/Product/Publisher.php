<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract\Product;

use Exception;
use LogicException;
use ReliqArts\Docweaver\Contract\Publisher as BasePublisher;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Result;
use Symfony\Component\Process\Exception\ProcessFailedException;

interface Publisher extends BasePublisher
{
    /**
     * Publish product documentation (all versions).
     *
     * @throws Exception
     */
    public function publish(Product $product, string $source): Result;

    /**
     * Update product documentation (all versions).
     *
     * @throws ProcessFailedException|LogicException
     */
    public function update(Product $product): Result;
}
