<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contracts;

/**
 * Interface Exception.
 */
interface Exception
{
    /**
     * @param string $message
     *
     * @return Exception
     */
    public function withMessage(string $message): Exception;
}
