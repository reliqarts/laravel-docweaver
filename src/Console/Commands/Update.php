<?php

namespace ReliQArts\Docweaver\Console\Commands;

use PDOException;
use Carbon\Carbon;
use Illuminate\Console\Command;
use ReliQArts\Docweaver\Contracts\Publisher;
use ReliQArts\Docweaver\Helpers\CoreHelper as Helper;

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
     * Publisher instance.
     */
    protected $publisher = null;

    /**
     * Create a new command instance.
     *
     * @return void
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
        $skipConfirmation = $this->option('y');

        $this->comment(PHP_EOL."<info>♣♣♣</info> Docweaver Publisher \nHelp is here, try: php artisan docweaver:update --help");

        if ($skipConfirmation || $this->confirm("This command will attempt to update documentation for product ({$productName}). \nPlease ensure your internet connection is stable. Ready?")) {

            $this->info("Updating {$productName}.\nT: ".Carbon::now()->toCookieString()."\n----------");

            // Seek
            $update = $this->publisher->update($productName, $this);

            if ($update->success) {
                $this->info(PHP_EOL.'----------');
                $this->comment("<info>✔</info> Done. {$update->message}");

                // Display results
                $this->line('');
                $headers = ['Time', 'Versions', 'Updated'];
                $data = [[$update->extra->executionTime, $update->extra->versions, $update->extra->versionsUpdated]];
                $this->table($headers, $data);
                $this->line(PHP_EOL);
            } else {
                $this->line(PHP_EOL."<error>✘</error> $update->error");
            }
        }
    }
}
