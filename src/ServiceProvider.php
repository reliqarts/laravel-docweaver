<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\View\View;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Monolog\Handler\StreamHandler;
use ReliqArts\Docweaver\Console\Command\Publish;
use ReliqArts\Docweaver\Console\Command\Update;
use ReliqArts\Docweaver\Console\Command\UpdateAll;
use ReliqArts\Docweaver\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\Docweaver\Contract\Documentation\Provider as DocumentationProviderContract;
use ReliqArts\Docweaver\Contract\Documentation\Publisher as DocumentationPublisherContract;
use ReliqArts\Docweaver\Contract\FileHelper as FileHelperContract;
use ReliqArts\Docweaver\Contract\Filesystem as FilesystemContract;
use ReliqArts\Docweaver\Contract\Logger as LoggerContract;
use ReliqArts\Docweaver\Contract\MarkdownParser as MarkdownParserContract;
use ReliqArts\Docweaver\Contract\ProcessHelper as ProcessHelperContract;
use ReliqArts\Docweaver\Contract\Product\Finder as ProductFinderContract;
use ReliqArts\Docweaver\Contract\Product\Maker as ProductMakerContract;
use ReliqArts\Docweaver\Contract\Product\Publisher as ProductPublisherContract;
use ReliqArts\Docweaver\Contract\VcsCommandRunner;
use ReliqArts\Docweaver\Contract\YamlHelper as YamlHelperContract;
use ReliqArts\Docweaver\Factory\ProductMaker;
use ReliqArts\Docweaver\Helper\FileHelper;
use ReliqArts\Docweaver\Helper\ProcessHelper;
use ReliqArts\Docweaver\Helper\YamlHelper;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Service\ConfigProvider;
use ReliqArts\Docweaver\Service\Documentation\Provider as DocumentationProvider;
use ReliqArts\Docweaver\Service\Documentation\Publisher as DocumentationPublisher;
use ReliqArts\Docweaver\Service\Filesystem;
use ReliqArts\Docweaver\Service\GitCommandRunner;
use ReliqArts\Docweaver\Service\Logger;
use ReliqArts\Docweaver\Service\MarkdownParser;
use ReliqArts\Docweaver\Service\Product\Finder as ProductFinder;
use ReliqArts\Docweaver\Service\Product\Publisher as ProductPublisher;

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
        Publish::class,
        Update::class,
        UpdateAll::class,
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
     */
    public function provides(): array
    {
        return $this->commands;
    }

    protected function handleAssets(): self
    {
        $this->publishes(
            [
                sprintf('%s/public', $this->assetsDir) => public_path('vendor/docweaver'),
            ],
            'docweaver-public'
        );

        return $this;
    }

    protected function handleConfig(): self
    {
        $docWeaverConfig = "{$this->assetsDir}/config/config.php";

        // merge config
        $this->mergeConfigFrom($docWeaverConfig, 'docweaver');

        // allow publishing the config file, with tag: docweaver:config
        $this->publishes([$docWeaverConfig => config_path('docweaver.php')], 'docweaver-config');

        return $this;
    }

    private function handleCommands(): self
    {
        if (!empty($this->commands) && $this->app->runningInConsole()) {
            $this->commands($this->commands);
        }

        return $this;
    }

    private function handleMigrations(): self
    {
        $this->loadMigrationsFrom(sprintf('%s/database/migrations', $this->assetsDir));

        return $this;
    }

    private function handleRoutes(): self
    {
        require realpath(sprintf('%s/routes/web.php', $this->assetsDir));

        return $this;
    }

    private function handleViews(): self
    {
        $viewsDirectory = sprintf('%s/resources/views', $this->assetsDir);

        // Load the views...
        $this->loadViewsFrom($viewsDirectory, 'docweaver');

        // Allow publishing view files, with tag: views
        $this->publishes([$viewsDirectory => base_path('resources/views/vendor/docweaver')], 'docweaver-views');

        return $this;
    }

    private function registerAliases(): self
    {
        $loader = AliasLoader::getInstance();

        // Register aliases...
        $loader->alias('DocweaverProduct', Product::class);
        $loader->alias('DocweaverMarkdown', MarkdownParser::class);
        $loader->alias('DocweaverDocumentation', DocumentationProvider::class);
        $loader->alias('DocweaverPublisher', DocumentationPublisher::class);

        return $this;
    }

    private function registerBindings(): self
    {
        $this->app->singleton(FilesystemContract::class, Filesystem::class);
        $this->app->singleton(DocumentationPublisherContract::class, DocumentationPublisher::class);
        $this->app->singleton(DocumentationProviderContract::class, DocumentationProvider::class);
        $this->app->singleton(VcsCommandRunner::class, GitCommandRunner::class);
        $this->app->singleton(ProductMakerContract::class, ProductMaker::class);
        $this->app->singleton(ProductFinderContract::class, ProductFinder::class);
        $this->app->singleton(ProductPublisherContract::class, ProductPublisher::class);
        $this->app->singleton(FileHelperContract::class, FileHelper::class);
        $this->app->singleton(YamlHelperContract::class, YamlHelper::class);
        $this->app->singleton(ProcessHelperContract::class, ProcessHelper::class);
        $this->app->singleton(
            ConfigProviderContract::class,
            static function (): ConfigProviderContract {
                return new ConfigProvider(
                    resolve(Config::class)
                );
            }
        );
        $this->app->singleton(
            LoggerContract::class,
            static function (): LoggerContract {
                $logger = new Logger(self::LOGGER_NAME);
                $logFile = storage_path(sprintf('logs/%s.log', self::LOG_FILENAME));
                $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

                return $logger;
            }
        );
        $this->app->singleton(
            MarkdownParserContract::class,
            static fn (): MarkdownParserContract => new MarkdownParser(new GithubFlavoredMarkdownConverter([]))
        );

        return $this;
    }

    private function addViewComposers(): self
    {
        $configProvider = resolve(ConfigProviderContract::class);
        $viewFactory = resolve(ViewFactory::class);

        $viewFactory->composer(
            '*',
            static function (View $view) use ($configProvider): void {
                $view->with('docweaverConfigProvider', $configProvider);
            }
        );

        return $this;
    }
}
