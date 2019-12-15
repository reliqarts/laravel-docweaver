<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Console\Command;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ReliqArts\Docweaver\Contract\Documentation\Publisher;

class Update extends SingleProductCommand
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

            $this->displayResult($result);
        }
    }
}
