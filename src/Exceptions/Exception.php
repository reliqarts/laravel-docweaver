<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exceptions;

use Exception as BaseException;
use ReliqArts\Docweaver\Contracts\Exception as ExceptionContract;

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
