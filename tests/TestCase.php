<?php

namespace ReliQArts\Docweaver\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\BrowserKit\TestCase as BaseTestCase;
use ReliQArts\Docweaver\ServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * Filesystem.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Clean up the testing environment before the next test.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Define environment setup.
     *
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        // set base path
        $app->setBasePath(__DIR__ . '/..');

        // set app config
        $app['config']->set('database.default', 'testing');
        $app['config']->set('docweaver.storage.dir', 'tests/resources/docs');
        $app['config']->set('docweaver.versions.allow_worded_default', true);
        $app['config']->set('docweaver.view', [
            'accents' => [],
            'master_template' => 'test::layout',
            'master_section' => 'content',
            'docs_intro' => 'Oh my! Docs!',
        ]);
        $app['config']->set('docweaver.route', [
            'prefix' => 'tp-are-u-wi-mi',
            'names' => [
                'index' => 'docs',
                'product_index' => 'docs.prod.index',
                'product_page' => 'docs.prod.page',
            ],
        ]);

        // setup routes
        $this->setupRoutes($app);

        // grab filesystem instance
        $this->files = resolve(Filesystem::class);

        // add views
        View::addNamespace('test', realpath($app->basePath('/tests/resources/views')));
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Catalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.  In a normal app environment these would be added to
     * the 'aliases' array in the config/app.php file.  If your package exposes an
     * aliased facade, you should add the alias here, along with aliases for
     * facades upon which your package depends, e.g. Catalyst/Sentry.
     *
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [];
    }

    /**
     * Set up routes for testing.
     *
     * @param Application $app
     */
    private function setupRoutes($app)
    {
        // require routes
        require_once realpath($app->basePath('routes/web.php'));
    }
}
