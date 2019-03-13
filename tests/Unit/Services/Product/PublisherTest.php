<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit\Services\Product;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Contracts\Logger;
use ReliQArts\Docweaver\Contracts\VCSCommandRunner;
use ReliQArts\Docweaver\Exceptions\Product\AssetPublicationFailed;
use ReliQArts\Docweaver\Exceptions\Product\InvalidAssetDirectory;
use ReliQArts\Docweaver\Models\Product;
use ReliQArts\Docweaver\Services\Product\Publisher;
use ReliQArts\Docweaver\Tests\Unit\TestCase;
use ReliQArts\Docweaver\VO\Result;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class PublisherTest.
 *
 * @coversDefaultClass \ReliQArts\Docweaver\Services\Product\Publisher
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
     * @covers \ReliQArts\Docweaver\Services\Publisher::secondsSince
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
     * @covers \ReliQArts\Docweaver\Services\Publisher::secondsSince
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
     * @covers \ReliQArts\Docweaver\Services\Publisher::secondsSince
     * @small
     */
    public function testPublishWhenAssetDirectoryIsInvalid(): void
    {
        $productName = 'Product 26';
        $productDirectory = 'product 26';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getDirectory()->willReturn($productDirectory);
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
     * @covers \ReliQArts\Docweaver\Exceptions\Product\PublicationFailed::forProductVersion
     * @covers \ReliQArts\Docweaver\Services\Publisher::secondsSince
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
        $this->vcsCommandRunner->getTags($masterDirectory)->shouldBeCalledTimes(1)->willReturn($tags);
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
     * @covers \ReliQArts\Docweaver\Services\Publisher::secondsSince
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
     * @covers \ReliQArts\Docweaver\Services\Publisher::secondsSince
     * @small
     */
    public function testPublishFailsIfProductDirectoryIsNotWritable(): void
    {
        $productDirectory = 'product';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getDirectory()->willReturn($productDirectory);
        $product = $this->product->reveal();

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(false);
        $this->filesystem->isDirectory($masterDirectory)->shouldNotBeCalled();
        $this->vcsCommandRunner->clone($source, Product::VERSION_MASTER, $productDirectory)->shouldNotBeCalled();
        $this->product->publishAssets(Product::VERSION_MASTER)->shouldNotBeCalled();
        $this->vcsCommandRunner->getTags($masterDirectory)->shouldNotBeCalled();

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
     * @covers \ReliQArts\Docweaver\Exceptions\Product\PublicationFailed::forProductVersion
     * @covers \ReliQArts\Docweaver\Services\Publisher::secondsSince
     * @small
     */
    public function testPublishThrowsExceptionIfMasterFailsToUpdate(): void
    {
        $this->expectException(\ReliQArts\Docweaver\Exceptions\Product\PublicationFailed::class);
        $this->expectExceptionMessage('Failed to update version `master` of product `Product 24`');

        $productName = 'Product 24';
        $productDirectory = 'product';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $this->product->getName()->willReturn($productName);
        $this->product->getDirectory()->willReturn($productDirectory);
        $product = $this->product->reveal();

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, Product::VERSION_MASTER))
            ->shouldBeCalledTimes(1)->willThrow(ProcessFailedException::class);
        $this->product->publishAssets(Product::VERSION_MASTER)->shouldNotBeCalled();
        $this->vcsCommandRunner->getTags($masterDirectory)->shouldNotBeCalled();

        $this->subject->publish($product, $source);
    }

    /**
     * @covers ::__construct
     * @covers ::update
     * @covers ::updateVersion
     * @small
     */
    public function testUpdate(): void
    {
        $productName = 'Product 28';
        $productVersions = ['master' => 'Master', '1.0' => '1.0'];
        $productDirectory = 'product 28';
        $this->product->getName()->willReturn($productName);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getVersions()->willReturn($productVersions);
        $product = $this->product->reveal();

        foreach ($productVersions as $versionTag => $version) {
            $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, $versionTag))->shouldBeCalledTimes(1);
            $this->product->publishAssets($versionTag)->shouldBeCalledTimes(1);
        }

        $result = $this->subject->update($product);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        foreach ($productVersions as $versionTag => $version) {
            $this->assertContains($versionTag, $result->getData()->versionsUpdated);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::update
     * @covers ::updateVersion
     * @small
     */
    public function testUpdateWhenAVersionUpdateFails(): void
    {
        $productName = 'Product 29';
        $productVersions = ['master' => 'Master', '1.0' => '1.0'];
        $productDirectory = 'product 29';
        $this->product->getName()->willReturn($productName);
        $this->product->getDirectory()->willReturn($productDirectory);
        $this->product->getVersions()->willReturn($productVersions);
        $product = $this->product->reveal();

        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, 'master'))->shouldBeCalledTimes(1);
        $this->product->publishAssets('master')->shouldBeCalledTimes(1);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, '1.0'))
            ->shouldBeCalledTimes(1)->willThrow(ProcessFailedException::class);
        $this->product->publishAssets('1.0')->shouldNotBeCalled();
        $this->logger->info(
            sprintf('Failed to update version `%s` of product `%s`. It may be a tag.', '1.0', $productName)
        )->shouldBeCalledTimes(1);

        $result = $this->subject->update($product);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertContains('master', $result->getData()->versionsUpdated);

        foreach ($productVersions as $versionTag => $version) {
            $this->assertContains($version, $result->getData()->versions);
        }
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
        $this->vcsCommandRunner->getTags($masterDirectory)->shouldBeCalledTimes(1)->willReturn($tags);

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
