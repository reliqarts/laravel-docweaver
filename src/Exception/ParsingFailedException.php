<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exception;

use ReliqArts\Docweaver\Contract\Exception as ExceptionContract;

final class ParsingFailedException extends Exception
{
    private const CODE = 1004;

    /**
     * @var null|string
     */
    private ?string $failedFile;

    /**
     * @param ExceptionContract $previous
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
     */
    public function getFailedFile(): ?string
    {
        return $this->failedFile;
    }
}
