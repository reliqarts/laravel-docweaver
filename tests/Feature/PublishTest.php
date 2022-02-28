<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use ReliqArts\Docweaver\Model\Product;

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
    private array $publishedProducts = [];

    protected function tearDown(): void
    {
        $this->removePublishedDocs();

        parent::tearDown();
    }

    /**
     * Test the ability to publish documentation.
     *
     * @covers \ReliqArts\Docweaver\Console\Command\Publish
     * @covers \ReliqArts\Docweaver\Service\Documentation\Publisher::publish
     * @large
     */
    public function testPublishDocumentation(): void
    {
        $docIndex = $this->configProvider->getRoutePrefix();
        $productName = 'Product 1450';

        // publish Docweaver docs
        Artisan::call('docweaver:publish', [
            'product' => $productName,
            'source' => 'https://github.com/reliqarts/docweaver-docs.git',
            '--y' => true,
        ]);

        // check existence
        $this->visit($docIndex)
            ->see($productName)
            ->see(Product::VERSION_MAIN);

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
