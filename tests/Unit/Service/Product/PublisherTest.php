<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service\Product;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\Logger;
use ReliqArts\Docweaver\Contract\VcsCommandRunner;
use ReliqArts\Docweaver\Exception\Product\AssetPublicationFailedException;
use ReliqArts\Docweaver\Exception\Product\InvalidAssetDirectoryException;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Result;
use ReliqArts\Docweaver\Service\Product\Publisher;
use ReliqArts\Docweaver\Tests\Unit\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class PublisherTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Service\Product\Publisher
 *
 * @internal
 */
final class PublisherTest extends TestCase
{
    /**
     * @var Logger|ObjectProphecy
     */
    private ObjectProphecy $logger;

    /**
     * @var ObjectProphecy|Product
     */
    private ObjectProphecy $product;

    /**
     * @var Publisher
     */
    private Publisher $subject;

    /**
     * @var ObjectProphecy|VcsCommandRunner
     */
    private ObjectProphecy $vcsCommandRunner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vcsCommandRunner = $this->prophesize(VcsCommandRunner::class);
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
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
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
        $product = $this->product->reveal();
        $tags = ['1.0', '1.1', '3.7', '3.8'];

        $this->product->getDirectory()
            ->willReturn($productDirectory);
        $this->product->getMainDirectory()
            ->willReturn($masterDirectory);
        $this->product->getName()
            ->willReturn($productName);

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->vcsCommandRunner->clone($source, Product::VERSION_MAIN, $productDirectory)
            ->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MAIN)
            ->shouldBeCalledTimes(1);
        $this->setTagExpectations($tags, $productDirectory, $source);

        $result = $this->subject->publish($product, $source);
        self::assertPublishSuccess($result, $productName, $tags);
        self::assertContains(Product::VERSION_MAIN, $result->getExtra()->versionsPublished);
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
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
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
        $product = $this->product->reveal();
        $tags = ['1.0', '1.1'];

        $this->product->getDirectory()
            ->willReturn($productDirectory);
        $this->product->getMainDirectory()
            ->willReturn($masterDirectory);
        $this->product->getName()
            ->willReturn($productName);

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, Product::VERSION_MAIN))
            ->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MAIN)
            ->shouldBeCalledTimes(1);
        $this->setTagExpectations($tags, $productDirectory, $source);

        $result = $this->subject->publish($product, $source);
        self::assertPublishSuccess($result, $productName, $tags);
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
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
     * @small
     */
    public function testPublishWhenAssetDirectoryIsInvalid(): void
    {
        $productName = 'Product 26';
        $productDirectory = 'product 26';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $product = $this->product->reveal();
        $tags = ['1.0', '2.0'];

        $this->product->getDirectory()
            ->willReturn($productDirectory);
        $this->product->getMainDirectory()
            ->willReturn($masterDirectory);
        $this->product->getName()
            ->willReturn($productName);

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, Product::VERSION_MAIN))
            ->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MAIN)
            ->shouldBeCalledTimes(1)->willThrow(InvalidAssetDirectoryException::class);
        $this->logger->info(Argument::type('string'))->shouldBeCalledTimes(1);
        $this->setTagExpectations($tags, $productDirectory, $source, ['1.0' => true]);

        $result = $this->subject->publish($product, $source);
        self::assertPublishSuccess($result, $productName, ['2.0']);
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
     * @covers \ReliqArts\Docweaver\Exception\Product\PublicationFailedException::forProductVersion
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
     * @small
     *
     * @throws Exception
     */
    public function testPublishWhenAVersionPublicationFails(): void
    {
        $productName = 'Product 31';
        $productDirectory = 'product 31';
        $source = 'http://product.source';
        $mainDirectory = sprintf('%s/main', $productDirectory);
        $product = $this->product->reveal();
        $tag = '1.1';
        $tagDirectory = sprintf('%s/%s', $productDirectory, $tag);
        $tags = [$tag];

        $this->product->getDirectory()
            ->willReturn($productDirectory);
        $this->product->getMainDirectory()
            ->willReturn($mainDirectory);
        $this->product->getName()
            ->willReturn($productName);

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isDirectory($mainDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->vcsCommandRunner->clone($source, Product::VERSION_MAIN, $productDirectory)
            ->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MAIN)
            ->shouldBeCalledTimes(1);
        $this->vcsCommandRunner->listTags($mainDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn($tags);
        $this->filesystem->isDirectory($tagDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->vcsCommandRunner->clone($source, $tag, $productDirectory)
            ->shouldBeCalledTimes(1)
            ->willThrow(ProcessFailedException::class);
        $this->product->publishAssets($tag)
            ->shouldNotBeCalled();

        $result = $this->subject->publish($product, $source);
        self::assertInstanceOf(Result::class, $result);
        self::assertTrue($result->isSuccess());
        self::assertContains(Product::VERSION_MAIN, $result->getExtra()->versionsPublished);
        self::assertNotContains($tag, $result->getExtra()->versionsPublished);
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
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
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
        $product = $this->product->reveal();
        $tags = ['1.0'];

        $this->product->getDirectory()
            ->willReturn($productDirectory);
        $this->product->getMainDirectory()
            ->willReturn($masterDirectory);
        $this->product->getName()
            ->willReturn($productName);

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, Product::VERSION_MAIN))
            ->shouldBeCalledTimes(1);
        $this->product->publishAssets(Product::VERSION_MAIN)
            ->shouldBeCalledTimes(1)
            ->willThrow(AssetPublicationFailedException::class);
        $this->logger->error(Argument::type('string'))
            ->shouldBeCalledTimes(1);
        $this->setTagExpectations($tags, $productDirectory, $source);

        $result = $this->subject->publish($product, $source);
        self::assertPublishSuccess($result, $productName, $tags);
    }

    /**
     * @covers ::__construct
     * @covers ::publish
     * @covers ::readyResourceDirectory
     * @covers ::setExecutionStartTime
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
     * @small
     */
    public function testPublishFailsIfProductDirectoryIsNotWritable(): void
    {
        $productDirectory = 'product';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $product = $this->product->reveal();

        $this->product->getDirectory()
            ->willReturn($productDirectory);
        $this->product->getMainDirectory()
            ->willReturn($masterDirectory);

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->filesystem->isDirectory($masterDirectory)
            ->shouldNotBeCalled();
        $this->vcsCommandRunner->clone($source, Product::VERSION_MAIN, $productDirectory)
            ->shouldNotBeCalled();
        $this->product->publishAssets(Product::VERSION_MAIN)
            ->shouldNotBeCalled();
        $this->vcsCommandRunner->listTags($masterDirectory)
            ->shouldNotBeCalled();

        $result = $this->subject->publish($product, $source);

        self::assertInstanceOf(Result::class, $result);
        self::assertFalse($result->isSuccess());
        self::assertIsString($result->getExtra()->executionTime);
        self::assertSame(sprintf('Product directory %s is not writable.', $productDirectory), $result->getError());
    }

    /**
     * @covers ::__construct
     * @covers ::publish
     * @covers ::readyResourceDirectory
     * @covers ::setExecutionStartTime
     * @covers ::updateVersion
     * @covers \ReliqArts\Docweaver\Exception\Product\PublicationFailedException::forProductVersion
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
     * @small
     */
    public function testPublishThrowsExceptionIfMasterFailsToUpdate(): void
    {
        $this->expectException(\ReliqArts\Docweaver\Exception\Product\PublicationFailedException::class);
        $this->expectExceptionMessage('Failed to update version `main` of product `Product 24`');

        $productName = 'Product 24';
        $productDirectory = 'product';
        $source = 'http://product.source';
        $masterDirectory = sprintf('%s/master', $productDirectory);
        $product = $this->product->reveal();

        $this->product->getName()
            ->willReturn($productName);
        $this->product->getDirectory()
            ->willReturn($productDirectory);
        $this->product->getMainDirectory()
            ->willReturn($masterDirectory);

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isDirectory($masterDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, Product::VERSION_MAIN))
            ->shouldBeCalledTimes(1)
            ->willThrow(ProcessFailedException::class);
        $this->product->publishAssets(Product::VERSION_MAIN)
            ->shouldNotBeCalled();
        $this->vcsCommandRunner->listTags($masterDirectory)
            ->shouldNotBeCalled();

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
            ->shouldBeCalledTimes(1)
            ->willReturn($availableTags);
        $this->vcsCommandRunner->getRemoteUrl($masterDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn($productSource);
        $this->product->getName()
            ->willReturn($productName);
        $this->product->getDirectory()
            ->willReturn($productDirectory);
        $this->product->getMainDirectory()
            ->willReturn($masterDirectory);
        $this->product->getVersions()
            ->willReturn($publishedVersions);

        foreach ($branches as $version) {
            $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, $version))
                ->shouldBeCalledTimes(1);
            $this->product->publishAssets($version)
                ->shouldBeCalledTimes(1);
        }

        foreach ($unpublishedTags as $tag) {
            $tagDirectory = sprintf('%s/%s', $productDirectory, $tag);
            $this->filesystem->isDirectory($tagDirectory)
                ->shouldBeCalledTimes(1)
                ->willReturn(false);
            $this->vcsCommandRunner->clone($productSource, $tag, $productDirectory)
                ->shouldBeCalledTimes(1);
            $this->product->publishAssets($tag)
                ->shouldBeCalledTimes(1);
        }

        $result = $this->subject->update($this->product->reveal());

        self::assertTrue($result->isSuccess());

        foreach ($branches as $version) {
            self::assertContains($version, $result->getExtra()->versionsUpdated);
        }

        foreach ($unpublishedTags as $version) {
            self::assertContains($version, $result->getExtra()->versionsPublished);
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

        $this->product->getName()
            ->willReturn($productName);
        $this->product->getDirectory()
            ->willReturn($productDirectory);
        $this->product->getMainDirectory()
            ->willReturn($masterDirectory);
        $this->product->getVersions()
            ->willReturn($publishedVersions);
        $this->vcsCommandRunner->listTags($masterDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn($availableTags);
        $this->vcsCommandRunner->getRemoteUrl($masterDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn($productSource);
        $this->vcsCommandRunner->pull(sprintf('%s/%s', $productDirectory, 'master'))
            ->shouldBeCalledTimes(1)
            ->willThrow(ProcessFailedException::class);
        $this->product->publishAssets('master')
            ->shouldNotBeCalled();
        $this->logger->info($expectedErrorMessage, Argument::type('array'))
            ->shouldBeCalledTimes(1);
        $this->vcsCommandRunner->clone($productSource, '2.0', $productDirectory)
            ->shouldBeCalledTimes(1);
        $this->product->publishAssets('2.0')
            ->shouldBeCalledTimes(1);

        $result = $this->subject->update($this->product->reveal());

        self::assertTrue($result->isSuccess());
        self::assertNotContains('master', $result->getExtra()->versionsUpdated);
        self::assertContains('2.0', $result->getExtra()->versionsPublished);
        self::assertNotContains('1.0', $result->getExtra()->versionsPublished);
    }

    private function assertPublishSuccess(Result $result, string $productName, array $tagsPublished): void
    {
        self::assertInstanceOf(Result::class, $result);
        self::assertTrue($result->isSuccess());
        self::assertIsString($result->getExtra()->executionTime);
        self::assertSame(sprintf('%s was successfully published.', $productName), $result->getMessage());
        self::assertContains(Product::VERSION_MAIN, $result->getExtra()->versions);

        foreach ($tagsPublished as $tag) {
            self::assertContains($tag, $result->getExtra()->versionsPublished);
        }
    }

    /** @noinspection PhpTooManyParametersInspection */

    /**
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
