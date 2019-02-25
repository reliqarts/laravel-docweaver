<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Exceptions;

use Exception as BaseException;
use ReliQArts\Docweaver\Contracts\Exception as ExceptionContract;

abstract class Exception extends BaseException implements ExceptionContract
{
    /**
     * @param string $message
     *
     * @return ExceptionContract
     */
    final public function withMessage(string $message): ExceptionContract
    {
        $this->message = $message;

        return $this;
    }
}
