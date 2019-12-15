<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Console\Command;

use Carbon\Carbon;
use ReliqArts\Docweaver\Exception\InvalidDirectory;

class Publish extends SingleProductCommand
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
            $this->info("Publishing {$productName}.\nT: " . Carbon::now()->toCookieString() . '----------');

            $result = $this->publisher->publish($productName, $productSource, $this);

            $this->displayResult($result);
        }
    }
}
