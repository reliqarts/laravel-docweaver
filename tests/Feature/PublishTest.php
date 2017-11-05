<?php

namespace ReliQArts\Docweaver\Tests\Feature;

use Artisan;
use DocweaverHelper;
use ReliQArts\Docweaver\Tests\TestCase as TestCase;

class PublishTest extends TestCase
{
    /**
     * Test the ability to publish documentation.
     */
    public function testPublishDoc()
    {
        $routeConfig = DocweaverHelper::getRouteConfig();
        $docIndex = $routeConfig['prefix'];
        $productName = 'Docweaver';

        // publish Docweaver docs
        $exitCode = Artisan::call('docweaver:publish', [
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
                realpath($this->app->basePath("/tests/resources/docs/$productName"))
            )
        );
    }
}
