<?php

/**
 * @noinspection PhpParamsInspection
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpStrictTypeCheckingInspection
 */

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service\Documentation;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\SimpleCache\InvalidArgumentException;
use ReliqArts\Docweaver\Contract\Documentation\Provider as ProviderContract;
use ReliqArts\Docweaver\Contract\MarkdownParser;
use ReliqArts\Docweaver\Exception\BadImplementationException;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Service\Documentation\Provider;
use ReliqArts\Docweaver\Tests\Unit\TestCase;

/**
 * Class ProviderTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Service\Documentation\Provider
 *
 * @internal
 */
final class ProviderTest extends TestCase
{
    /**
     * @var Cache|ObjectProphecy
     */
    private ObjectProphecy $cache;

    /**
     * @var string
     */
    private string $cacheKey;

    /**
     * @var string
     */
    private string $documentationDirectory;

    /**
     * @var MarkdownParser|ObjectProphecy
     */
    private ObjectProphecy $markdownParser;

    /**
     * @var ObjectProphecy|Product
     */
    private ObjectProphecy $product;

    private ProviderContract $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->prophesize(Cache::class);
        $this->cacheKey = 'test.cache';
        $this->documentationDirectory = 'docs';
        $this->markdownParser = $this->prophesize(MarkdownParser::class);
        $this->product = $this->prophesize(Product::class);

        $this->configProvider->getCacheKey()
            ->shouldBeCalled()
            ->willReturn($this->cacheKey);
        $this->configProvider->getDocumentationDirectory()
            ->shouldBeCalled()
            ->willReturn($this->documentationDirectory);
        $this->filesystem->isDirectory(base_path($this->documentationDirectory))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->subject = new Provider(
            $this->filesystem->reveal(),
            $this->cache->reveal(),
            $this->configProvider->reveal(),
            $this->markdownParser->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @small
     */
    public function testExceptionIsThrownIfDocumentationDirectoryIsInvalid(): void
    {
        $this->expectException(BadImplementationException::class);
        $this->expectExceptionMessage('Documentation resource directory `docs` does not exist.');

        $this->filesystem->isDirectory(base_path($this->documentationDirectory))
            ->shouldBeCalled()
            ->willReturn(false);

        new Provider(
            $this->filesystem->reveal(),
            $this->cache->reveal(),
            $this->configProvider->reveal(),
            $this->markdownParser->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getFilePathForProductPage
     * @covers ::getPage
     * @covers ::getPageContent
     * @covers ::replaceLinks
     * @small
     *
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGetUncachedPage(): void
    {
        $version = '6.3';
        $page = 'information';
        $productKey = 'product';
        $productDirectory = 'product-of-the-system';
        $routePrefix = 'docs';
        $filePath = sprintf('%s/%s/%s.md', $productDirectory, $version, $page);
        $cacheKey = sprintf('%s.%s.%s.%s', $this->cacheKey, $productKey, $version, $page);
        $fileContents = 'Hi Tester! You got to foo/docs/' . urlencode('{{version}}');
        $pageContent = sprintf('Hi Tester! You got to foo/%s/%s/%s', $routePrefix, $productKey, $version);

        $this->product->getKey()
            ->shouldBeCalledTimes(2)
            ->willReturn($productKey);
        $this->cache->has($cacheKey)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->filesystem->exists($filePath)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->get($filePath)
            ->shouldBeCalledTimes(1)
            ->willReturn($fileContents);
        $this->markdownParser->parse($fileContents)
            ->shouldBeCalledTimes(1)
            ->willReturn($fileContents);
        $this->configProvider->getRoutePrefix()
            ->shouldBeCalledTimes(1)
            ->willReturn($routePrefix);
        $this->cache->put($cacheKey, $pageContent, Argument::type('int'))
            ->shouldBeCalledTimes(1);
        $this->product->getDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn($productDirectory);

        $result = $this->subject->getPage($this->product->reveal(), $version, $page);

        self::assertIsString($result);
        self::assertSame($pageContent, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::getPage
     * @small
     *
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGetCachedPage(): void
    {
        $version = '3.0';
        $page = 'about';
        $productKey = 'product';
        $productDirectory = 'product-of-the-system';
        $filePath = sprintf('%s/%s/%s.md', $productDirectory, $version, $page);
        $cacheKey = sprintf('%s.%s.%s.%s', $this->cacheKey, $productKey, $version, $page);
        $pageContent = sprintf('Hi Tester! You got there!');

        $this->product->getKey()
            ->shouldBeCalledTimes(1)
            ->willReturn($productKey);
        $this->cache->has($cacheKey)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->cache->get($cacheKey)
            ->shouldBeCalledTimes(1)
            ->willReturn($pageContent);
        $this->filesystem->exists($filePath)
            ->shouldNotBeCalled();
        $this->filesystem->get($filePath)
            ->shouldNotBeCalled();
        // @noinspection PhpStrictTypeCheckingInspection
        $this->markdownParser->parse(Argument::type('string'))
            ->shouldNotBeCalled();
        $this->configProvider->getRoutePrefix()
            ->shouldNotBeCalled();
        $this->cache->put($cacheKey, $pageContent, Argument::type('int'))
            ->shouldNotBeCalled();
        $this->product->getDirectory()
            ->shouldNotBeCalled();

        $result = $this->subject->getPage($this->product->reveal(), $version, $page);

        self::assertIsString($result);
        self::assertSame($pageContent, $result);
    }

    /**
     * @covers ::sectionExists
     * @dataProvider sectionExistenceDataProvider
     * @small
     */
    public function testSectionExists(bool $exists): void
    {
        $productDirectory = 'product';
        $version = '3.0';
        $page = 'intro';
        $filePath = sprintf('%s/%s/%s.md', $productDirectory, $version, $page);

        $this->product->getDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn($productDirectory);
        $this->filesystem->exists($filePath)
            ->shouldBeCalledTimes(1)
            ->willReturn($exists);

        $result = $this->subject->sectionExists($this->product->reveal(), $version, $page);
        self::assertIsBool($result);
        self::assertSame($exists, $result);
    }

    public function sectionExistenceDataProvider(): array
    {
        return [
            'section exists' => [true],
            'section does not exist' => [false],
        ];
    }
}
