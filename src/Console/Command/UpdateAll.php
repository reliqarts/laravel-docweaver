<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Console\Command;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ReliqArts\Docweaver\Contract\Documentation\Publisher;
use stdClass;

class UpdateAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docweaver:update-all
                            {--y|y : Whether to skip confirmation}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update documentation for all products';

    /**
     * @var Publisher
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
     */
    public function handle(): void
    {
        $skipConfirmation = $this->option('y');
        $confirmationMessage = 'This command will attempt to update documentation for all products.'
            . "\nPlease ensure your internet connection is stable. Ready?";

        $this->comment(PHP_EOL
            . "<info>♣♣♣</info> Docweaver DocumentationPublisher \n"
            . 'Help is here, try: php artisan docweaver:update-all --help');

        if ($skipConfirmation || $this->confirm($confirmationMessage)) {
            $this->info("Updating products.\nT: " . Carbon::now()->toCookieString() . "\n----------");

            $result = $this->publisher->updateAll($this);

            if (!$result->isSuccess()) {
                $this->line(PHP_EOL . "<error>✘</error> {$result->getError()}");

                return;
            }

            $this->info(PHP_EOL . '----------');
            $this->comment("<info>✔</info> Done. {$result->getMessage()}");

            // Display results
            $this->line('');
            $headers = ['Time', 'Products', 'Updated'];
            $data = $result->getExtra() ?? new stdClass();
            $rows = [[$data->executionTime ?? '?', count($data->products ?? []), count($data->productsUpdated ?? [])]];
            $this->table($headers, $rows);
            $this->line(PHP_EOL);
        }
    }
}
