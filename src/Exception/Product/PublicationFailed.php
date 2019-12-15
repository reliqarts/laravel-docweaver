<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exception\Product;

use ReliqArts\Docweaver\Contract\Exception as ExceptionContract;
use ReliqArts\Docweaver\Exception\Exception;
use ReliqArts\Docweaver\Model\Product;

class PublicationFailed extends Exception
{
    private const CODE = 8001;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @param Product        $product
     * @param string         $version
     * @param null|Exception $previous
     *
     * @return ExceptionContract
     */
    public static function forProductVersion(
        Product $product,
        string $version,
        Exception $previous = null
    ): ExceptionContract {
        $message = sprintf('Failed to publish version `%s` of product `%s`.', $version, $product->getName());
        $self = new self($message, self::CODE, $previous);
        $self->product = $product;

        return $self;
    }
}
