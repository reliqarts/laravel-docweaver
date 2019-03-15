<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Services;

use ReliQArts\Docweaver\Contracts\VCSCommandRunner;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class GitCommandRunner implements VCSCommandRunner
{
    /**
     * @param string $source
     * @param string $branch
     * @param string $workingDirectory
     */
    public function clone(string $source, string $branch, string $workingDirectory): void
    {
        $clone = new Process(sprintf('git clone --branch %s "%s" %s', $branch, $source, $branch), $workingDirectory);
        $clone->mustRun();
    }

    /**
     * @param string $workingDirectory
     *
     * @throws ProcessFailedException
     *
     * @return array
     */
    public function getTags(string $workingDirectory): array
    {
        $listTags = new Process('git tag -l', $workingDirectory);
        $listTags->mustRun();

        if ($splitTags = preg_split("/[\n\r]/", $listTags->getOutput())) {
            return array_filter(array_map('trim', $splitTags));
        }

        return [];
    }

    /**
     * @param string $workingDirectory
     *
     * @throws ProcessFailedException
     */
    public function pull(string $workingDirectory): void
    {
        $pull = new Process('git pull', $workingDirectory);
        $pull->mustRun();
    }
}
