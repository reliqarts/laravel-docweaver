<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exception\Product;

use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Exception\InvalidDirectory;

final class InvalidAssetDirectory extends InvalidDirectory
{
    private const CODE = 4003;

    /**
     * @param string         $directory
     * @param null|Exception $previous
     *
     * @return Exception
     */
    public static function forDirectory(string $directory, Exception $previous = null): Exception
    {
        $message = sprintf('Invalid asset directory: `%s`.', $directory);
        $self = new self($message, self::CODE, $previous);
        $self->directory = $directory;

        return $self;
    }
}
