<?php

/**
 * @noinspection PhpTooManyParametersInspection
 */

declare(strict_types=1);

namespace ReliqArts\Docweaver\Helper;

use ReliqArts\Docweaver\Contract\ProcessHelper as ProcessHelperContract;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Process;

final class ProcessHelper implements ProcessHelperContract
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
    ): Process {
        return new Process($command, $cwd, $env, $input, $timeout);
    }
}
