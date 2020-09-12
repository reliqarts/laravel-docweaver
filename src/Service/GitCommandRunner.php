<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service;

use ReliqArts\Docweaver\Contract\ProcessHelper;
use ReliqArts\Docweaver\Contract\VcsCommandRunner;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class GitCommandRunner implements VcsCommandRunner
{
    private const DEFAULT_REMOTE_NAME = 'origin';

    private ProcessHelper $processHelper;

    public function __construct(ProcessHelper $processHelper)
    {
        $this->processHelper = $processHelper;
    }

    public function clone(string $source, string $branch, string $workingDirectory): void
    {
        $command = ['git', 'clone', '--branch', $branch, $source, $branch];

        $clone = $this->processHelper->createProcess($command, $workingDirectory);
        $clone->mustRun();
    }

    /**
     * @throws ProcessFailedException
     */
    public function listTags(string $workingDirectory): array
    {
        $this->fetch($workingDirectory);

        $listTags = $this->processHelper->createProcess(['git', 'tag', '-l'], $workingDirectory);
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
        $pull = $this->processHelper->createProcess(['git', 'pull'], $workingDirectory);
        $pull->mustRun();
    }

    /**
     * @throws ProcessFailedException
     */
    public function getRemoteUrl(string $workingDirectory, ?string $remoteName = null): string
    {
        $remoteName ??= self::DEFAULT_REMOTE_NAME;
        $command = ['git', 'config', '--get', sprintf('remote.%s.url', $remoteName)];

        $getUrl = $this->processHelper->createProcess($command, $workingDirectory);
        $getUrl->mustRun();

        return trim($getUrl->getOutput());
    }

    private function fetch(string $workingDirectory): void
    {
        $pull = $this->processHelper->createProcess(['git', 'fetch'], $workingDirectory);
        $pull->mustRun();
    }
}
