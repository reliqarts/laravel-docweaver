<?php

/**
 * @noinspection PhpTooManyParametersInspection
 */

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract;

use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Process;

interface ProcessHelper
{
    /**
     * @throws LogicException
     */
    public function createProcess(
        array $command,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60
    ): Process;
}
