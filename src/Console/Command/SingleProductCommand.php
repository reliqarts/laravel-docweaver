<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Console\Command;

use Illuminate\Console\Command;
use ReliqArts\Docweaver\Contract\Documentation\Publisher;
use ReliqArts\Docweaver\Result;
use stdClass;

abstract class SingleProductCommand extends Command
{
    /**
     * @var Publisher
     */
    protected Publisher $publisher;

    /**
     * Create a new command instance.
     */
    public function __construct(Publisher $publisher)
    {
        parent::__construct();

        $this->publisher = $publisher;
    }

    protected function displayResult(Result $result): void
    {
        if (!$result->isSuccess()) {
            $this->line(PHP_EOL . "<error>✘</error> {$result->getError()}");

            return;
        }

        $this->info(PHP_EOL . '----------');
        $this->comment("<info>✔</info> Done. {$result->getMessage()}");

        $this->line('');
        $headers = ['Time', 'Versions', 'Published', 'Updated'];
        $data = $result->getExtra() ?? new stdClass();
        $rows = [[
            $data->executionTime ?? '?',
            count($data->versions ?? []),
            count($data->versionsPublished ?? []),
            count($data->versionsUpdated ?? []),
        ]];
        $this->table($headers, $rows);
        $this->line(PHP_EOL);
    }
}
