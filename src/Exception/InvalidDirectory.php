<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exception;

use ReliqArts\Docweaver\Contract\Exception as ExceptionContract;

class InvalidDirectory extends Exception
{
    private const CODE = 4002;

    /**
     * @var null|string
     */
    protected $directory;

    /**
     * @param string            $directory Directory
     * @param ExceptionContract $previous  Previous Exception if nested exception
     *
     * @return ExceptionContract
     */
    public static function forDirectory(string $directory, ExceptionContract $previous = null): ExceptionContract
    {
        $message = sprintf('Invalid directory: `%s`.', $directory);
        $self = new self($message, self::CODE, $previous);
        $self->directory = $directory;

        return $self;
    }

    /**
     * @return null|string
     */
    final public function getDirectory(): ?string
    {
        return $this->directory;
    }
}
