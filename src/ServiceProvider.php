<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\View\View;
use Monolog\Handler\StreamHandler;

/**
 *  ServiceProvider.
 */
class ServiceProvider extends BaseServiceProvider
{
    private const LOGGER_NAME = 'Docweaver';
    private const LOG_FILENAME = 'docweaver';

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
    public function boot(): void
    {
        $this->handleConfig()
            ->handleMigrations()
            ->handleViews()
            ->handleRoutes()
            ->handleAssets()
            ->handleCommands()
            ->addViewComposers();
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->registerAliases();
        $this->registerBindings();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return $this->commands;
    }

    /**
     * @return self
     */
    protected function handleAssets(): self
    {
        $this->publishes([
            "{$this->assetsDir}/public" => public_path('vendor/docweaver'),
        ], 'docweaver:public');

        return $this;
    }

    /**
     * @return self
     */
    protected function handleConfig(): self
    {
        $docWeaverConfig = "{$this->assetsDir}/config/config.php";

        // merge config
        $this->mergeConfigFrom($docWeaverConfig, 'docweaver');

        // allow publishing the config file, with tag: docweaver:config
        $this->publishes([$docWeaverConfig => config_path('docweaver.php')], 'docweaver:config');

        return $this;
    }

    /**
     * @return self
     */
    private function handleCommands(): self
    {
        if ($this->app->runningInConsole() && !empty($this->commands)) {
            $this->commands($this->commands);
        }

        return $this;
    }

    /**
     * @return self
     */
    private function handleMigrations(): self
    {
        $this->loadMigrationsFrom(sprintf('%s/database/migrations', $this->assetsDir));

        return $this;
    }

    /**
     * @return self
     */
    private function handleRoutes(): self
    {
        require realpath(sprintf('%s/routes/web.php', $this->assetsDir));

        return $this;
    }

    /**
     * @return self
     */
    private function handleViews(): self
    {
        $viewsDirectory = sprintf('%s/resources/views', $this->assetsDir);

        // Load the views...
        $this->loadViewsFrom($viewsDirectory, 'docweaver');

        // Allow publishing view files, with tag: views
        $this->publishes([$viewsDirectory => base_path('resources/views/vendor/docweaver')], 'docweaver:views');

        return $this;
    }

    /**
     * @return self
     */
    private function registerAliases(): self
    {
        $loader = AliasLoader::getInstance();

        // Register aliases...
        $loader->alias('DocweaverProduct', Models\Product::class);
        $loader->alias('DocweaverMarkdown', Services\MarkdownParser::class);
        $loader->alias('DocweaverDocumentation', Services\Documentation\Provider::class);
        $loader->alias('DocweaverPublisher', Services\Documentation\Publisher::class);

        return $this;
    }

    /**
     * @return self
     */
    private function registerBindings(): self
    {
        $this->app->bind(
            Contracts\Documentation\Publisher::class,
            Services\Documentation\Publisher::class
        );

        $this->app->bind(
            Contracts\Documentation\Provider::class,
            Services\Documentation\Provider::class
        );

        $this->app->bind(
            Contracts\MarkdownParser::class,
            Services\MarkdownParser::class
        );

        $this->app->bind(
            Contracts\VCSCommandRunner::class,
            Services\GitCommandRunner::class
        );

        $this->app->bind(
            Contracts\Product\Maker::class,
            Factories\ProductMaker::class
        );

        $this->app->bind(
            Contracts\Product\Finder::class,
            Services\Product\Finder::class
        );

        $this->app->bind(
            Contracts\Product\Publisher::class,
            Services\Product\Publisher::class
        );

        $this->app->singleton(
            Contracts\ConfigProvider::class,
            function (): Contracts\ConfigProvider {
                return new Services\ConfigProvider(resolve(Config::class));
            }
        );

        $this->app->singleton(
            Contracts\Logger::class,
            function (): Contracts\Logger {
                $logger = new Services\Logger(self::LOGGER_NAME);
                $logFile = storage_path(sprintf('logs/%s.log', self::LOG_FILENAME));
                $logger->pushHandler(new StreamHandler($logFile, Services\Logger::DEBUG));

                return $logger;
            }
        );

        return $this;
    }

    /**
     * @return self
     */
    private function addViewComposers(): self
    {
        $configProvider = resolve(Contracts\ConfigProvider::class);

        ViewFacade::composer(
            ['docweaver::index', 'docweaver::page'],
            function (View $view) use ($configProvider): void {
                $view->with('docweaverConfigProvider', $configProvider);
            }
        );

        return $this;
    }
}
