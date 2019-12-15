<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract;

/**
 * Interface Exception.
 */
interface Exception
{
    public function withMessage(string $message): Exception;
}
