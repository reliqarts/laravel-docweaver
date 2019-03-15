<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ReliQArts\Docweaver\Contracts\Documentation\Publisher;
use ReliQArts\Docweaver\Exceptions\InvalidDirectory;

class Publish extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docweaver:publish 
                            {product? : Product name}
                            {source? : Product repository}
                            {--y|y : Whether to skip confirmation}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish documentation for product';

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * Create a new command instance.
     *
     * @param Publisher $publisher
     */
    public function __construct(Publisher $publisher)
    {
        parent::__construct();

        $this->publisher = $publisher;
    }

    /**
     * Execute the console command.
     *
     * @throws InvalidDirectory
     */
    public function handle(): void
    {
        $productName = $this->argument('product');
        $productSource = $this->argument('source');
        $skipConfirmation = $this->option('y');
        $confirmationMessage = sprintf(
            "This command will attempt to pull documentation for product (%s) from %s}. \n"
            . 'Please ensure your internet connection is stable. Ready?',
            $productName,
            $productSource
        );

        $this->comment(PHP_EOL
            . "<info>♣♣♣</info> Docweaver DocumentationPublisher \n"
            . 'Help is here, try: php artisan docweaver:publish --help');

        if ($skipConfirmation || $this->confirm($confirmationMessage)) {
            $this->info("Publishing {$productName}.\nT: " . Carbon::now()->toCookieString() . "\n----------");

            $result = $this->publisher->publish($productName, $productSource, $this);

            if ($result->isSuccess()) {
                $this->info(PHP_EOL . '----------');
                $this->comment("<info>✔</info> Done. {$result->getMessage()}");

                // Display results
                $this->line('');
                $headers = ['Time', 'Versions', 'Published', 'Updated'];
                $data = $result->getData();
                $rows = [[
                    $data->executionTime,
                    count($data->versions),
                    count($data->versionsPublished),
                    count($data->versionsUpdated),
                ]];
                $this->table($headers, $rows);
                $this->line(PHP_EOL);
            } else {
                $this->line(PHP_EOL . "<error>✘</error> {$result->getError()}");
            }
        }
    }
}
