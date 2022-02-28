<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exception;

use ReliqArts\Docweaver\Contract\Exception as ExceptionContract;

class InvalidDirectoryException extends Exception
{
    protected const MESSAGE_TEMPLATE = 'Invalid directory: `%s`.';

    private const CODE = 4002;

    /**
     * @var null|string
     */
    protected ?string $directory;

    /**
     * @param string            $directory Directory
     * @param ExceptionContract $previous  Previous Exception if nested exception
     */
    public static function forDirectory(string $directory, ExceptionContract $previous = null): ExceptionContract
    {
        $message = sprintf(static::MESSAGE_TEMPLATE, $directory);
        $self = new static($message, self::CODE, $previous);
        $self->directory = $directory;

        return $self;
    }

    final public function getDirectory(): ?string
    {
        return $this->directory;
    }
}
