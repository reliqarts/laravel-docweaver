<?php

namespace ReliQArts\Docweaver\Tests;

use View;
use DocweaverHelper;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\BrowserKit\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Filesystem
     *
     * @var Filesystem
     */
    protected $files = null;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // set base path
        $app->setBasePath(__DIR__.'/..');

        // set app config
        $app['config']->set('database.default', 'testing');
        $app['config']->set('docweaver.storage.dir', 'tests/resources/docs');
        $app['config']->set('docweaver.versions.allow_worded_default', true);
        $app['config']->set('docweaver.view', [
            'accents' => [],
            'master_template' => 'test::layout',
            'master_section' => 'content',
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
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \ReliQArts\Docweaver\DocweaverServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.  In a normal app environment these would be added to
     * the 'aliases' array in the config/app.php file.  If your package exposes an
     * aliased facade, you should add the alias here, along with aliases for
     * facades upon which your package depends, e.g. Cartalyst/Sentry.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'DocweaverHelper' => 'ReliQArts\Docweaver\Helpers\CoreHelper',
        ];
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown()
    {   
        parent::tearDown();
    }

    /**
     * Set up routes for testing.
     *
     * @param  Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    private function setupRoutes($app)
    {
        // require routes
        require_once realpath($app->basePath('routes/web.php'));
    }
}
