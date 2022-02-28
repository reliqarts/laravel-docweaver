<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exception\Product;

use ReliqArts\Docweaver\Contract\Exception as ExceptionContract;
use ReliqArts\Docweaver\Exception\Exception;
use ReliqArts\Docweaver\Model\Product;
use Throwable;

class PublicationFailedException extends Exception
{
    private const CODE = 8001;

    /**
     * @var Product
     */
    protected Product $product;

    public static function forProductVersion(
        Product $product,
        string $version,
        Throwable $previous = null
    ): ExceptionContract {
        $message = sprintf('Failed to publish version `%s` of product `%s`.', $version, $product->getName());
        if ($previous !== null) {
            $message .= sprintf(' %s', $previous->getMessage());
        }

        $self = new self($message, self::CODE, $previous);
        $self->product = $product;

        return $self;
    }
}
