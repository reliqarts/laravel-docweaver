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
     * @param Product $product
     * @param string  $source
     * @param string  $version
     *
     * @return bool
     */
    public function publishVersion(Product $product, string $source, string $version): bool;

    /**
     * Update product documentation (all versions).
     *
     * @param Product $product
     *
     * @return Result
     */
    public function update(Product $product): Result;

    /**
     * Update product version.
     *
     * @param Product $product
     * @param string  $version
     *
     * @return bool
     */
    public function updateVersion(Product $product, string $version): bool;
}
