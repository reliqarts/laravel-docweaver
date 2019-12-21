<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exception\Product;

use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Model\Product;

final class AssetPublicationFailed extends PublicationFailed
{
    private const CODE = 8002;

    /**
     * @var null|string
     */
    protected ?string $assetType;

    /**
     * AssetPublicationFailed constructor.
     */
    public static function forProductAssetsOfType(
        Product $product,
        string $assetType,
        Exception $previous = null
    ): Exception {
        $message = sprintf('Failed to publish %s assets for product `%s`.', $assetType, $product->getName());
        $self = new self($message, self::CODE, $previous);
        $self->product = $product;
        $self->assetType = $assetType;

        return $self;
    }
}
