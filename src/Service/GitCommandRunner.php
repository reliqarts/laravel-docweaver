<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service;

use ReliqArts\Docweaver\Contract\VCSCommandRunner;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class GitCommandRunner implements VCSCommandRunner
{
    private const DEFAULT_REMOTE_NAME = 'origin';

    public function clone(string $source, string $branch, string $workingDirectory): void
    {
        $command = ['git', 'clone', '--branch', $branch, sprintf('%s', $source), $branch];

        $clone = new Process($command, $workingDirectory);
        $clone->mustRun();
    }

    /**
     * @throws ProcessFailedException
     */
    public function listTags(string $workingDirectory): array
    {
        $this->fetch($workingDirectory);

        $listTags = new Process(['git', 'tag', '-l'], $workingDirectory);
        $listTags->mustRun();

        if ($splitTags = preg_split("/[\n\r]/", $listTags->getOutput())) {
            return array_filter(array_map('trim', $splitTags));
        }

        return [];
    }

    /**
     * @throws ProcessFailedException
     */
    public function pull(string $workingDirectory): void
    {
        $pull = new Process(['git', 'pull'], $workingDirectory);
        $pull->mustRun();
    }

    /**
     * @throws ProcessFailedException
     */
    public function getRemoteUrl(string $workingDirectory, ?string $remoteName = null): string
    {
        $remoteName = $remoteName ?? self::DEFAULT_REMOTE_NAME;
        $command = ['git', 'config', '--get', sprintf('remote.%s.url', $remoteName)];

        $getUrl = new Process($command, $workingDirectory);
        $getUrl->mustRun();

        return trim($getUrl->getOutput());
    }

    private function fetch(string $workingDirectory): void
    {
        $pull = new Process(['git', 'fetch'], $workingDirectory);
        $pull->mustRun();
    }
}
