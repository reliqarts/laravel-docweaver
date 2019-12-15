<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\View\View;
use Monolog\Handler\StreamHandler;

/**
 *  ServiceProvider.
 */
final class ServiceProvider extends BaseServiceProvider
{
    private const LOGGER_NAME = 'Docweaver';
    private const LOG_FILENAME = 'docweaver';

    /**
     * Assets location.
     */
    protected string $assetsDir = __DIR__ . '/..';

    /**
     * List of commands.
     *
     * @var array
     */
    protected array $commands = [
        Console\Command\Publish::class,
        Console\Command\Update::class,
        Console\Command\UpdateAll::class,
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
        $this->registerAliases()
            ->registerBindings();
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
        ], 'docweaver-public');

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
        $this->publishes([$docWeaverConfig => config_path('docweaver.php')], 'docweaver-config');

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
        $this->publishes([$viewsDirectory => base_path('resources/views/vendor/docweaver')], 'docweaver-views');

        return $this;
    }

    /**
     * @return self
     */
    private function registerAliases(): self
    {
        $loader = AliasLoader::getInstance();

        // Register aliases...
        $loader->alias('DocweaverProduct', Model\Product::class);
        $loader->alias('DocweaverMarkdown', Service\MarkdownParser::class);
        $loader->alias('DocweaverDocumentation', Service\Documentation\Provider::class);
        $loader->alias('DocweaverPublisher', Service\Documentation\Publisher::class);

        return $this;
    }

    /**
     * @return self
     */
    private function registerBindings(): self
    {
        $this->app->bind(
            Contract\Filesystem::class,
            Service\Filesystem::class
        );

        $this->app->bind(
            Contract\Documentation\Publisher::class,
            Service\Documentation\Publisher::class
        );

        $this->app->bind(
            Contract\Documentation\Provider::class,
            Service\Documentation\Provider::class
        );

        $this->app->bind(
            Contract\MarkdownParser::class,
            Service\MarkdownParser::class
        );

        $this->app->bind(
            Contract\VCSCommandRunner::class,
            Service\GitCommandRunner::class
        );

        $this->app->bind(
            Contract\Product\Maker::class,
            Factory\ProductMaker::class
        );

        $this->app->bind(
            Contract\Product\Finder::class,
            Service\Product\Finder::class
        );

        $this->app->bind(
            Contract\Product\Publisher::class,
            Service\Product\Publisher::class
        );

        $this->app->singleton(
            Contract\ConfigProvider::class,
            function (): Contract\ConfigProvider {
                return new Service\ConfigProvider(
                    resolve(Config::class)
                );
            }
        );

        $this->app->singleton(
            Contract\Logger::class,
            function (): Contract\Logger {
                $logger = new Service\Logger(self::LOGGER_NAME);
                $logFile = storage_path(sprintf('logs/%s.log', self::LOG_FILENAME));
                $logger->pushHandler(new StreamHandler($logFile, Service\Logger::DEBUG));

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
        $configProvider = resolve(Contract\ConfigProvider::class);
        $viewFactory = resolve(ViewFactory::class);

        $viewFactory->composer(
            '*',
            function (View $view) use ($configProvider): void {
                $view->with('docweaverConfigProvider', $configProvider);
            }
        );

        return $this;
    }
}
