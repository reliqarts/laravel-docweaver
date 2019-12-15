<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service;

use Illuminate\Console\Command;
use ReliqArts\Docweaver\Contract\Filesystem as FilesystemContract;
use ReliqArts\Docweaver\Contract\Logger as LoggerContract;
use ReliqArts\Docweaver\Contract\Publisher as PublisherContract;

abstract class Publisher implements PublisherContract
{
    protected const TELL_DIRECTION_OUT = 'out';
    protected const TELL_DIRECTION_IN = 'in';
    protected const TELL_DIRECTION_FLAT = 'flat';
    protected const TELL_DIRECTION_NONE = 'none';

    private const DIRECTORY_READY_MODE = 0777;

    /**
     * Calling command if running in console.
     *
     * @var Command
     */
    protected $callingCommand;

    /**
     * @var FilesystemContract
     */
    protected $filesystem;

    /**
     * @var LoggerContract
     */
    protected $logger;

    /**
     * @var float
     */
    private $startTime;

    /**
     * Publisher constructor.
     */
    public function __construct(FilesystemContract $filesystem, LoggerContract $logger)
    {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->startTime = microtime(true);
    }

    protected function getExecutionTime(): string
    {
        return sprintf('%ss', $this->secondsSince($this->startTime));
    }

    /**
     * Print to console or screen.
     *
     * @param string $text
     * @param string $direction in|out
     *
     * @return string
     */
    protected function tell($text, $direction = self::TELL_DIRECTION_OUT)
    {
        $direction = strtolower($direction);
        $nl = app()->runningInConsole() ? "\n" : '<br/>';
        $dirSymbol = ($direction === self::TELL_DIRECTION_IN
            ? '>> '
            : ($direction === self::TELL_DIRECTION_FLAT ? '-- ' : '<< '));
        if ($direction === self::TELL_DIRECTION_NONE) {
            $dirSymbol = '';
        }

        if (app()->runningInConsole() && $this->callingCommand) {
            $line = sprintf('%s%s', $dirSymbol, $text);

            if ($direction === self::TELL_DIRECTION_OUT) {
                $line = sprintf('<info>%s</info>', $line);
            }

            $this->callingCommand->line($line);
        } else {
            echo "{$nl}{$dirSymbol}{$text}";
        }

        return $text;
    }

    /**
     * Ensure documentation resource directory exists and is writable.
     */
    protected function readyResourceDirectory(string $directory): bool
    {
        if (!$this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, self::DIRECTORY_READY_MODE, true);
        }

        return $this->filesystem->isWritable($directory);
    }

    protected function setExecutionStartTime(): void
    {
        $this->startTime = microtime(true);
    }

    /**
     * Get seconds since a micro-time start-time.
     *
     * @param float $startTime start time in microseconds
     *
     * @return string seconds since, to 2 decimal places
     */
    private function secondsSince(float $startTime): string
    {
        $duration = microtime(true) - $startTime;
        $hours = (int)($duration / 60 / 60);
        $minutes = (int)($duration / 60) - $hours * 60;
        $seconds = $duration - $hours * 60 * 60 - $minutes * 60;

        return number_format((float)$seconds, 2, '.', '');
    }
}
