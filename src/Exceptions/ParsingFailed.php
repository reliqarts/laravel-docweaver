<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Exceptions;

use ReliQArts\Docweaver\Contracts\Exception as ExceptionContract;

final class ParsingFailed extends Exception
{
    private const CODE = 1004;

    /**
     * @var null|string
     */
    private $failedFile;

    /**
     * @param string            $file
     * @param ExceptionContract $previous
     *
     * @return ExceptionContract
     */
    public static function forFile(string $file, ExceptionContract $previous = null): ExceptionContract
    {
        $message = sprintf('Failed to parse file `%s`.', $file);
        $self = new self($message, self::CODE, $previous);
        $self->failedFile = $file;

        return $self;
    }

    /**
     * Get the file which parsing failed for.
     *
     * @return null|string
     */
    public function getFailedFile(): ?string
    {
        return $this->failedFile;
    }
}
