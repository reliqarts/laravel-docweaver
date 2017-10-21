<?php

namespace ReliQArts\Docweaver;

use Illuminate\Routing\Router;
use Illuminate\Foundation\AliasLoader;
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
    protected $assetsDir = __DIR__.'/..';

    /**
     * List of commands.
     *
     * @var array
     */
    protected $commands = [
        // ...
    ];

    /**
     * Publish assets.
     *
     * @return void
     */
    protected function handleAssets()
    {
        $this->publishes([
            "$this->assetsDir/public" => public_path('vendor/docweaver'),
        ], 'docweaver:public');
    }

    /**
     * Command files.
     */
    private function handleCommands()
    {
        // Register the commands...
        if ($this->app->runningInConsole() && ! empty($this->commands)) {
            $this->commands($this->commands);
        }
    }

    /**
     * Register Configuraion.
     */
    protected function handleConfig()
    {
        $docWeaverConfig = "{$this->assetsDir}/config/config.php";

        // merge config
        $this->mergeConfigFrom($docWeaverConfig, 'docweaver');

        // allow publishing the config file, with tag: docweaver:config
        $this->publishes([$docWeaverConfig => config_path('docweaver.php')], 'docweaver:config');
    }

    /**
     * Migration files.
     */
    private function handleMigrations()
    {
        // Load the migrations...
        $this->loadMigrationsFrom("{$this->assetsDir}/database/migrations");
    }

    /**
     * Route files.
     */
    private function handleRoutes()
    {
        // Get the routes...
        require realpath("{$this->assetsDir}/routes/web.php");
    }

    /**
     * View files.
     */
    private function handleViews()
    {
        // Load the views...
        $this->loadViewsFrom("{$this->assetsDir}/resources/views", 'docweaver');

        // Allow publishing view files, with tag: views
        $this->publishes([
            "{$this->assetsDir}/resources/views" => base_path('resources/views/vendor/docweaver'),
        ], 'docweaver:views');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router)
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
     *
     * @return void
     */
    public function register()
    {
        $loader = AliasLoader::getInstance();

        // bind contract to model
        $this->app->bind(
            Contracts\Documentation::class,
            Models\Documentation::class
        );

        // Register facades...
        $loader->alias('DocweaverHelper', Helpers\CoreHelper::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Contracts\Documentation::class,
        ];
    }
}
