<?php

namespace ReliQArts\Docweaver;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 *  DocweaverServiceProvider.
 */
class DocweaverServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Assets location.
     */
    protected $assetsDir = __DIR__ . '/..';

    /**
     * List of commands.
     *
     * @var array
     */
    protected $commands = [
        Console\Commands\Publish::class,
        Console\Commands\Update::class,
        Console\Commands\UpdateAll::class,
    ];

    /**
     * Perform post-registration booting of services.
     */
    public function boot(Router $router): void
    {
        // register config
        $this->handleConfig();
        // load migrations
        $this->handleMigrations();
        // load views
        $this->handleViews();
        // load routes
        $this->handleRoutes();
        // publish assets
        $this->handleAssets();
        // publish commands
        $this->handleCommands();
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();

        // Register aliases...
        $loader->alias('DocweaverProduct', Models\Product::class);
        $loader->alias('DocweaverConfig', Helpers\Config::class);
        $loader->alias('DocweaverMarkdown', Helpers\Markdown::class);
        $loader->alias('DocweaverDocumentation', Models\Documentation::class);
        $loader->alias('DocweaverPublisher', Services\Publisher::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Publish assets.
     */
    protected function handleAssets(): void
    {
        $this->publishes([
            "{$this->assetsDir}/public" => public_path('vendor/docweaver'),
        ], 'docweaver:public');
    }

    /**
     * Register Configuraion.
     */
    protected function handleConfig(): void
    {
        $docWeaverConfig = "{$this->assetsDir}/config/config.php";

        // merge config
        $this->mergeConfigFrom($docWeaverConfig, 'docweaver');

        // allow publishing the config file, with tag: docweaver:config
        $this->publishes([$docWeaverConfig => config_path('docweaver.php')], 'docweaver:config');
    }

    /**
     * Command files.
     */
    private function handleCommands(): void
    {
        // Register the commands...
        if ($this->app->runningInConsole() && !empty($this->commands)) {
            $this->commands($this->commands);
        }
    }

    /**
     * Migration files.
     */
    private function handleMigrations(): void
    {
        // Load the migrations...
        $this->loadMigrationsFrom("{$this->assetsDir}/database/migrations");
    }

    /**
     * Route files.
     */
    private function handleRoutes(): void
    {
        // Get the routes...
        require realpath("{$this->assetsDir}/routes/web.php");
    }

    /**
     * View files.
     */
    private function handleViews(): void
    {
        // Load the views...
        $this->loadViewsFrom("{$this->assetsDir}/resources/views", 'docweaver');

        // Allow publishing view files, with tag: views
        $this->publishes([
            "{$this->assetsDir}/resources/views" => base_path('resources/views/vendor/docweaver'),
        ], 'docweaver:views');
    }
}
