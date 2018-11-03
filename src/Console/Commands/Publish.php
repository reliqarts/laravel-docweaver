<?php

namespace ReliQArts\Docweaver\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ReliQArts\Docweaver\Services\Publisher;

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
     * Publisher instance.
     */
    protected $publisher;

    /**
     * Create a new command instance.
     */
    public function __construct(Publisher $publisher)
    {
        parent::__construct();

        $this->publisher = $publisher;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $productName = $this->argument('product');
        $productSource = $this->argument('source');
        $skipConfirmation = $this->option('y');

        $this->comment(PHP_EOL . "<info>♣♣♣</info> Docweaver Publisher \nHelp is here, try: php artisan docweaver:publish --help");

        if ($skipConfirmation || $this->confirm("This command will attempt to pull documentation for product ({$productName}) from {$productSource}. \nPlease ensure your internet connection is stable. Ready?")) {
            $this->info("Publishing {$productName}.\nT: " . Carbon::now()->toCookieString() . "\n----------");

            // Seek
            $publish = $this->publisher->publish($productName, $productSource, $this);
            if ($publish->success) {
                $this->info(PHP_EOL . '----------');
                $this->comment("<info>✔</info> Done. {$publish->message}");

                // Display results
                $this->line('');
                $headers = ['Time', 'Versions', 'Published', 'Updated'];
                $data = [[
                    $publish->extra->executionTime,
                    $publish->extra->versions,
                    $publish->extra->versionsPublished,
                    $publish->extra->versionsUpdated,
                ]];
                $this->table($headers, $data);
                $this->line(PHP_EOL);
            } else {
                $this->line(PHP_EOL . "<error>✘</error> {$publish->error}");
            }
        }
    }
}
