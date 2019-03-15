<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Feature;

use Illuminate\Support\Facades\Artisan;

/**
 * @coversNothing
 *
 * @internal
 */
final class PublishTest extends TestCase
{
    /**
     * @var array
     */
    private $publishedProducts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publishedProducts = [];
    }

    protected function tearDown(): void
    {
        $this->removePublishedDocs();

        parent::tearDown();
    }

    /**
     * Test the ability to publish documentation.
     *
     * @covers \ReliQArts\Docweaver\Console\Commands\Publish
     * @covers \ReliQArts\Docweaver\Services\Documentation\Publisher::publish
     * @large
     */
    public function testPublishDocumentation()
    {
        $docIndex = $this->configProvider->getRoutePrefix();
        $productName = 'Product 450';

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

        $this->publishedProducts[] = $productName;
    }

    /**
     * Remove published documentation folders.
     */
    private function removePublishedDocs(): void
    {
        $documentationDirectory = $this->configProvider->getDocumentationDirectory();

        foreach ($this->publishedProducts as $productName) {
            $directory = base_path(
                sprintf('%s/%s', $documentationDirectory, strtolower($productName))
            );
            $this->filesystem->deleteDirectory($directory);
        }
    }
}
