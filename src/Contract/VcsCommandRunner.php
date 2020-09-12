<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract;

use Symfony\Component\Process\Exception\ProcessFailedException;

interface VcsCommandRunner
{
    public function clone(string $source, string $branch, string $workingDirectory): void;

    /**
     * @throws ProcessFailedException
     */
    public function pull(string $workingDirectory): void;

    /**
     * @throws ProcessFailedException
     */
    public function listTags(string $workingDirectory): array;

    /**
     * @throws ProcessFailedException
     */
    public function getRemoteUrl(string $workingDirectory, ?string $remoteName = null): string;
}
