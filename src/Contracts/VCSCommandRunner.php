<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contracts;

use Symfony\Component\Process\Exception\ProcessFailedException;

interface VCSCommandRunner
{
    /**
     * @param string $source
     * @param string $branch
     * @param string $workingDirectory
     */
    public function clone(string $source, string $branch, string $workingDirectory): void;

    /**
     * @param string $workingDirectory
     *
     * @throws ProcessFailedException
     */
    public function pull(string $workingDirectory): void;

    /**
     * @param string $workingDirectory
     *
     * @throws ProcessFailedException
     *
     * @return array
     */
    public function listTags(string $workingDirectory): array;

    /**
     * @param string      $workingDirectory
     * @param null|string $remoteName
     *
     * @throws ProcessFailedException
     *
     * @return string
     */
    public function getRemoteUrl(string $workingDirectory, ?string $remoteName = null): string;
}
