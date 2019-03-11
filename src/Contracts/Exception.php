<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Contracts;

interface Exception
{
    /**
     * @param string $message
     *
     * @return Exception
     */
    public function withMessage(string $message): Exception;
}
