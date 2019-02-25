<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Exceptions\Product;

use ReliQArts\Docweaver\Contracts\Exception as ExceptionContract;
use ReliQArts\Docweaver\Exceptions\Exception;
use ReliQArts\Docweaver\Models\Product;

class PublicationFailed extends Exception
{
    private const CODE = 8001;

    /**
     * @var Product
     */
    protected $product;

    /**
     * AssetPublicationFailed constructor.
     *
     * @param Product        $product
     * @param null|Exception $previous
     *
     * @return ExceptionContract
     */
    public static function forProduct(Product $product, Exception $previous = null): ExceptionContract
    {
        $message = sprintf('Failed to publish product (%s).', $product->getName());
        $self = new self($message, self::CODE, $previous);
        $self->product = $product;

        return $self;
    }
}
