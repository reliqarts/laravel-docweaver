<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ReliQArts\Docweaver\Contracts\Documentation\Publisher;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docweaver:update 
                            {product? : Product name}
                            {--y|y : Whether to skip confirmation}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update documentation for product';

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
     */
    public function handle(): void
    {
        $productName = $this->argument('product');
        $skipConfirmation = $this->option('y');
        $confirmationMessage = sprintf(
            "This command will attempt to update documentation for product (%s). \n"
            . 'Please ensure your internet connection is stable. Ready?',
            $productName
        );

        $this->comment(PHP_EOL
            . "<info>♣♣♣</info> Docweaver DocumentationPublisher \n"
            . 'Help is here, try: php artisan docweaver:update --help');

        if ($skipConfirmation || $this->confirm($confirmationMessage)) {
            $this->info("Updating {$productName}.\nT: " . Carbon::now()->toCookieString() . "\n----------");

            $result = $this->publisher->update($productName, $this);

            if ($result->isSuccess()) {
                $this->info(PHP_EOL . '----------');
                $this->comment("<info>✔</info> Done. {$result->getMessage()}");

                // Display results
                $this->line('');
                $headers = ['Time', 'Versions', 'Updated'];
                $data = $result->getData();
                $rows = [[$data->executionTime, count($data->versions), count($data->versionsUpdated)]];
                $this->table($headers, $rows);
                $this->line(PHP_EOL);
            } else {
                $this->line(PHP_EOL . "<error>✘</error> {$result->getError()}");
            }
        }
    }
}
