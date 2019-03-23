<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Services\Product;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Contracts\Exception;
use ReliqArts\Docweaver\Contracts\Logger;
use ReliqArts\Docweaver\Contracts\VCSCommandRunner;
use ReliqArts\Docweaver\Exceptions\Product\AssetPublicationFailed;
use ReliqArts\Docweaver\Exceptions\Product\InvalidAssetDirectory;
use ReliqArts\Docweaver\Models\Product;
use ReliqArts\Docweaver\Services\Product\Publisher;
use ReliqArts\Docweaver\Tests\Unit\TestCase;
use ReliqArts\Docweaver\VO\Result;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class PublisherTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Services\Product\Publisher
 *
 * @internal
 */
final class PublisherTest extends TestCase
{
    /**
     * @var Logger|ObjectProphecy
     */
    private $logger;

    /**
     * @var ObjectProphecy|Product
     */
    private $product;

    /**
     * @var Publisher
     */
    private $subject;

    /**
     * @var ObjectProphecy|VCSCommandRunner
     */
    private $vcsCommandRunner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vcsCommandRunner = $this->prophesize(VCSCommandRunner::class);
        $this->product = $this->prophesize(Product::class);
        $this->logger = $this->prophesize(Logger::class);

        $this->subject = new Publisher(
            $this->filesystem->reveal(),
            $this->logger->reveal(),
            $this->vcsCommandRunner->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getExecutionTime
     * @covers ::publish
     * @covers ::publishProductAssets
     * @covers ::publishTags
     * @covers ::publishVersion
     * @covers ::readyResourceDirectory
     * @covers ::setExecutionStartTime
     * @covers \ReliqArts\Docweaver\Services\Publisher::secondsSince
     * @medium
     *
     * @throws Exception
     */
    public function testPublish(): void
    {
        $productName = 'Product 23';
        $productDirectory = 'product';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getMasterDirectory()->willReturn($masterDirectory);
        $this->product->getName()->willReturn($productName);
        $product = $this->product->reveal();
        $tags = ['1.0', '1.1', '3.7', '3.8'];

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)->shouldBeCalledTimes(1)->willReturn(false);
        $this->vcsCommandRunner->clone($source, Product::VERSION_MASTER, $productDirectory)->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MASTER)->shouldBeCalledTimes(1);
        $this->setTagExpectations($tags, $productDirectory, $source);

        $result = $this->subject->publish($product, $source);
        $this->assertPublishSuccess($result, $productName, $tags);
        $this->assertContains(Product::VERSION_MASTER, $result->getData()->versionsPublished);
    }

    /**
     * @covers ::__construct
     * @covers ::getExecutionTime
     * @covers ::publish
     * @covers ::publishProductAssets
     * @covers ::publishTags
     * @covers ::publishVersion
     * @covers ::readyResourceDirectory
     * @covers ::setExecutionStartTime
     * @covers ::updateVersion
     * @covers \ReliqArts\Docweaver\Services\Publisher::secondsSince
     * @small
     *
     * @throws Exception
     */
    public function testPublishWhenMasterExists(): void
    {
        $productName = 'Product 26';
        $productDirectory = 'product';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getMasterDirectory()->willReturn($masterDirectory);
        $this->product->getName()->willReturn($productName);
        $product = $this->product->reveal();
        $tags = ['1.0', '1.1'];

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, Product::VERSION_MASTER))
            ->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MASTER)->shouldBeCalledTimes(1);
        $this->setTagExpectations($tags, $productDirectory, $source);

        $result = $this->subject->publish($product, $source);
        $this->assertPublishSuccess($result, $productName, $tags);
    }

    /**
     * @covers ::__construct
     * @covers ::getExecutionTime
     * @covers ::publish
     * @covers ::publishProductAssets
     * @covers ::publishTags
     * @covers ::publishVersion
     * @covers ::readyResourceDirectory
     * @covers ::setExecutionStartTime
     * @covers ::updateVersion
     * @covers \ReliqArts\Docweaver\Services\Publisher::secondsSince
     * @small
     */
    public function testPublishWhenAssetDirectoryIsInvalid(): void
    {
        $productName = 'Product 26';
        $productDirectory = 'product 26';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getMasterDirectory()->willReturn($masterDirectory);
        $this->product->getName()->willReturn($productName);
        $product = $this->product->reveal();
        $tags = ['1.0', '2.0'];

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, Product::VERSION_MASTER))
            ->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MASTER)
            ->shouldBeCalledTimes(1)->willThrow(InvalidAssetDirectory::class);
        $this->logger->info(Argument::type('string'))->shouldBeCalledTimes(1);
        $this->setTagExpectations($tags, $productDirectory, $source, ['1.0' => true]);

        $result = $this->subject->publish($product, $source);
        $this->assertPublishSuccess($result, $productName, ['2.0']);
    }

    /**
     * @covers ::__construct
     * @covers ::getExecutionTime
     * @covers ::publish
     * @covers ::publishProductAssets
     * @covers ::publishTags
     * @covers ::publishVersion
     * @covers ::readyResourceDirectory
     * @covers ::setExecutionStartTime
     * @covers ::updateVersion
     * @covers \ReliqArts\Docweaver\Exceptions\Product\PublicationFailed::forProductVersion
     * @covers \ReliqArts\Docweaver\Services\Publisher::secondsSince
     * @small
     *
     * @throws Exception
     */
    public function testPublishWhenAVersionPublicationFails(): void
    {
        $productName = 'Product 31';
        $productDirectory = 'product 31';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getMasterDirectory()->willReturn($masterDirectory);
        $this->product->getName()->willReturn($productName);
        $product = $this->product->reveal();
        $tag = '1.1';
        $tagDirectory = sprintf('%s/%s', $productDirectory, $tag);
        $tags = [$tag];

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)->shouldBeCalledTimes(1)->willReturn(false);
        $this->vcsCommandRunner->clone($source, Product::VERSION_MASTER, $productDirectory)->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MASTER)->shouldBeCalledTimes(1);
        $this->vcsCommandRunner->listTags($masterDirectory)->shouldBeCalledTimes(1)->willReturn($tags);
        $this->filesystem->isDirectory($tagDirectory)->shouldBeCalledTimes(1)->willReturn(false);
        $this->vcsCommandRunner->clone($source, $tag, $productDirectory)
            ->shouldBeCalledTimes(1)->willThrow(ProcessFailedException::class);
        $this->product->publishAssets($tag)->shouldNotBeCalled();

        $result = $this->subject->publish($product, $source);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertContains('master', $result->getData()->versionsPublished);
        $this->assertNotContains($tag, $result->getData()->versionsPublished);
    }

    /**
     * @covers ::__construct
     * @covers ::getExecutionTime
     * @covers ::publish
     * @covers ::publishProductAssets
     * @covers ::publishTags
     * @covers ::publishVersion
     * @covers ::readyResourceDirectory
     * @covers ::setExecutionStartTime
     * @covers ::updateVersion
     * @covers \ReliqArts\Docweaver\Services\Publisher::secondsSince
     * @small
     *
     * @throws Exception
     */
    public function testPublishWhenAssetPublicationFails(): void
    {
        $productName = 'Product 26';
        $productDirectory = 'product 26';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getMasterDirectory()->willReturn($masterDirectory);
        $this->product->getName()->willReturn($productName);
        $product = $this->product->reveal();
        $tags = ['1.0'];

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, Product::VERSION_MASTER))
            ->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MASTER)
            ->shouldBeCalledTimes(1)->willThrow(AssetPublicationFailed::class);
        $this->logger->error(Argument::type('string'))->shouldBeCalledTimes(1);
        $this->setTagExpectations($tags, $productDirectory, $source);

        $result = $this->subject->publish($product, $source);
        $this->assertPublishSuccess($result, $productName, $tags);
    }

    /**
     * @covers ::__construct
     * @covers ::publish
     * @covers ::readyResourceDirectory
     * @covers ::setExecutionStartTime
     * @covers \ReliqArts\Docweaver\Services\Publisher::secondsSince
     * @small
     */
    public function testPublishFailsIfProductDirectoryIsNotWritable(): void
    {
        $productDirectory = 'product';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getMasterDirectory()->willReturn($masterDirectory);
        $product = $this->product->reveal();

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(false);
        $this->filesystem->isDirectory($masterDirectory)->shouldNotBeCalled();
        $this->vcsCommandRunner->clone($source, Product::VERSION_MASTER, $productDirectory)->shouldNotBeCalled();
        $this->product->publishAssets(Product::VERSION_MASTER)->shouldNotBeCalled();
        $this->vcsCommandRunner->listTags($masterDirectory)->shouldNotBeCalled();

        $result = $this->subject->publish($product, $source);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertIsString($result->getData()->executionTime);
        $this->assertSame(sprintf('Product directory %s is not writable.', $productDirectory), $result->getError());
    }

    /**
     * @covers ::__construct
     * @covers ::publish
     * @covers ::readyResourceDirectory
     * @covers ::setExecutionStartTime
     * @covers ::updateVersion
     * @covers \ReliqArts\Docweaver\Exceptions\Product\PublicationFailed::forProductVersion
     * @covers \ReliqArts\Docweaver\Services\Publisher::secondsSince
     * @small
     */
    public function testPublishThrowsExceptionIfMasterFailsToUpdate(): void
    {
        $this->expectException(\ReliqArts\Docweaver\Exceptions\Product\PublicationFailed::class);
        $this->expectExceptionMessage('Failed to update version `master` of product `Product 24`');

        $productName = 'Product 24';
        $productDirectory = 'product';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getName()->willReturn($productName);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getMasterDirectory()->willReturn($masterDirectory);
        $product = $this->product->reveal();

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, Product::VERSION_MASTER))
            ->shouldBeCalledTimes(1)->willThrow(ProcessFailedException::class);
        $this->product->publishAssets(Product::VERSION_MASTER)->shouldNotBeCalled();
        $this->vcsCommandRunner->listTags($masterDirectory)->shouldNotBeCalled();

        $this->subject->publish($product, $source);
    }

    /**
     * @covers ::__construct
     * @covers ::getProductSource
     * @covers ::listAvailableProductTags
     * @covers ::publishTags
     * @covers ::publishVersion
     * @covers ::update
     * @covers ::updateVersion
     * @small
     */
    public function testUpdate(): void
    {
        $productName = 'Product 28';
        $productSource = 'product.src';
        $publishedVersions = ['master' => 'Master', '1.0' => '1.0'];
        $publishedVersionKeys = array_keys($publishedVersions);
        $availableTags = ['1.0', '1.1', '2.0'];
        $branches = array_diff($publishedVersionKeys, $availableTags);
        $unpublishedTags = array_diff($availableTags, $publishedVersionKeys);
        $productDirectory = 'product 28';
        $masterDirectory = sprintf('%s/master', $productDirectory);

        $this->vcsCommandRunner->listTags($masterDirectory)
            ->shouldBeCalledTimes(1)->willReturn($availableTags);
        $this->vcsCommandRunner->getRemoteUrl($masterDirectory)
            ->shouldBeCalledTimes(1)->willReturn($productSource);
        $this->product->getName()->willReturn($productName);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getMasterDirectory()->willReturn($masterDirectory);
        $this->product->getVersions()->willReturn($publishedVersions);

        foreach ($branches as $version) {
            $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, $version))->shouldBeCalledTimes(1);
            $this->product->publishAssets($version)->shouldBeCalledTimes(1);
        }

        foreach ($unpublishedTags as $tag) {
            $tagDirectory = sprintf('%s/%s', $productDirectory, $tag);
            $this->filesystem->isDirectory($tagDirectory)
                ->shouldBeCalledTimes(1)->willReturn(false);
            $this->vcsCommandRunner->clone($productSource, $tag, $productDirectory)
                ->shouldBeCalledTimes(1);
            $this->product->publishAssets($tag)->shouldBeCalledTimes(1);
        }

        $result = $this->subject->update($this->product->reveal());
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        foreach ($branches as $version) {
            $this->assertContains($version, $result->getData()->versionsUpdated);
        }

        foreach ($unpublishedTags as $version) {
            $this->assertContains($version, $result->getData()->versionsPublished);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::getProductSource
     * @covers ::listAvailableProductTags
     * @covers ::publishTags
     * @covers ::publishVersion
     * @covers ::update
     * @covers ::updateVersion
     * @small
     */
    public function testUpdateWhenAVersionUpdateFails(): void
    {
        $productName = 'Product 29';
        $productSource = 'product.src';
        $publishedVersions = ['master' => 'Master', '1.0' => '1.0'];
        $availableTags = ['1.0', '2.0'];
        $productDirectory = 'product 29';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $expectedErrorMessage = sprintf('Failed to update version `%s` of product `%s`.', 'master', $productName);

        $this->product->getName()->willReturn($productName);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getMasterDirectory()->willReturn($masterDirectory);
        $this->product->getVersions()->willReturn($publishedVersions);
        $this->vcsCommandRunner->listTags($masterDirectory)
            ->shouldBeCalledTimes(1)->willReturn($availableTags);
        $this->vcsCommandRunner->getRemoteUrl($masterDirectory)
            ->shouldBeCalledTimes(1)->willReturn($productSource);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, 'master'))
            ->shouldBeCalledTimes(1)->willThrow(ProcessFailedException::class);
        $this->product->publishAssets('master')->shouldNotBeCalled();
        $this->logger->info($expectedErrorMessage, Argument::type('array'))->shouldBeCalledTimes(1);
        $this->vcsCommandRunner->clone($productSource, '2.0', $productDirectory)->shouldBeCalledTimes(1);
        $this->product->publishAssets('2.0')->shouldBeCalledTimes(1);

        $result = $this->subject->update($this->product->reveal());
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertNotContains('master', $result->getData()->versionsUpdated);
        $this->assertContains('2.0', $result->getData()->versionsPublished);
        $this->assertNotContains('1.0', $result->getData()->versionsPublished);
    }

    /**
     * @param Result $result
     * @param string $productName
     * @param array  $tagsPublished
     */
    private function assertPublishSuccess(Result $result, string $productName, array $tagsPublished): void
    {
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertIsString($result->getData()->executionTime);
        $this->assertSame(sprintf('%s was successfully published.', $productName), $result->getMessage());
        $this->assertContains(Product::VERSION_MASTER, $result->getData()->versions);

        foreach ($tagsPublished as $tag) {
            $this->assertContains($tag, $result->getData()->versionsPublished);
        }
    }

    /** @noinspection PhpTooManyParametersInspection */

    /**
     * @param array  $tags
     * @param string $productDirectory
     * @param string $source
     * @param array  $tagExistenceMap
     *
     * @throws Exception
     */
    private function setTagExpectations(
        array $tags,
        string $productDirectory,
        string $source,
        array $tagExistenceMap = []
    ): void {
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->vcsCommandRunner->listTags($masterDirectory)->shouldBeCalledTimes(1)->willReturn($tags);

        foreach ($tags as $tag) {
            $tagExists = false;
            if (array_key_exists($tag, $tagExistenceMap)) {
                $tagExists = (bool)$tagExistenceMap[$tag];
            }
            $tagDirectory = sprintf('%s/%s', $productDirectory, $tag);
            $this->filesystem->isDirectory($tagDirectory)->shouldBeCalledTimes(1)->willReturn($tagExists);

            if (!$tagExists) {
                $this->vcsCommandRunner->clone($source, $tag, $productDirectory)->shouldBeCalledTimes(1);
                $this->product->publishAssets($tag)->shouldBeCalledTimes(1);
            }
        }
    }
}
