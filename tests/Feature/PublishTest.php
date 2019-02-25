<?php

namespace ReliQArts\Docweaver\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use ReliQArts\Docweaver\Helpers\Config;
use ReliQArts\Docweaver\Tests\TestCase as TestCase;

/**
 * @internal
 * @coversNothing
 */
final class PublishTest extends TestCase
{
    /**
     * Test the ability to publish documentation.
     *
     * @covers \ReliQArts\Docweaver\Console\Commands\Publish
     * @covers \ReliQArts\Docweaver\Services\DocumentationPublisher::publish
     */
    public function testPublishDoc()
    {
        $routeConfig = Config::getRouteConfig();
        $docIndex = $routeConfig['prefix'];
        $productName = 'Docweaver';

        // publish Docweaver docs
        Artisan::call('docweaver:publish', [
            'product' => $productName,
            'source' => 'https://github.com/reliqarts/docweaver-docs.git',
            '--y' => true,
        ]);

        // check existence
        $this->visit($docIndex)
            ->see($productName)
            ->see('master');

        // remove Docweaver docs directory
        $this->assertTrue(
            $this->files->deleteDirectory(
                realpath($this->app->basePath("/tests/resources/docs/${productName}"))
            )
        );
    }
}
