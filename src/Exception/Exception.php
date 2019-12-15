<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exception;

use Exception as BaseException;
use ReliqArts\Docweaver\Contract\Exception as ExceptionContract;

abstract class Exception extends BaseException implements ExceptionContract
{
    final public function withMessage(string $message): ExceptionContract
    {
        $this->message = $message;

        return $this;
    }
}
