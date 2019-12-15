<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service\Product;

use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\Logger;
use ReliqArts\Docweaver\Contract\Product\Finder as FinderContract;
use ReliqArts\Docweaver\Contract\Product\Maker as ProductFactory;
use ReliqArts\Docweaver\Exception\InvalidDirectory;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Service\Product\Finder;
use ReliqArts\Docweaver\Tests\Unit\TestCase;

/**
 * Class FinderTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Service\Product\Finder
 *
 * @internal
 */
final class FinderTest extends TestCase
{
    /**
     * @var Logger|ObjectProphecy
     */
    private ObjectProphecy $logger;

    /**
     * @var ObjectProphecy|ProductFactory
     */
    private ObjectProphecy $productFactory;

    /**
     * @var FinderContract
     */
    private FinderContract $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->prophesize(Logger::class);
        $this->productFactory = $this->prophesize(ProductFactory::class);

        $this->subject = new Finder(
            $this->filesystem->reveal(),
            $this->logger->reveal(),
            $this->configProvider->reveal(),
            $this->productFactory->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::listProducts
     * @covers \ReliqArts\Docweaver\Exception\InvalidDirectory::forDirectory
     * @small
     */
    public function testListProducts(): void
    {
        $documentationDirectory = 'docs';
        $productDirectories = ['Product 1', 'product 2', 'invalid product', 'product that is invalid'];
        $failedCount = 0;

        $this->configProvider->getDocumentationDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn($documentationDirectory);
        $this->filesystem->directories(base_path($documentationDirectory))
            ->shouldBeCalledTimes(1)
            ->willReturn($productDirectories);

        foreach ($productDirectories as $productDirectory) {
            $key = basename($productDirectory);
            /**
             * @var ObjectProphecy|Product
             */
            $product = $this->prophesize(Product::class);
            $product->getDefaultVersion()->willReturn('1.0');
            $product->getKey()->willReturn($key);

            if (stripos($key, 'invalid') !== false) {
                /** @var \Exception|InvalidDirectory $exception */
                $exception = InvalidDirectory::forDirectory($productDirectory);
                $this->productFactory->create($productDirectory)
                    ->shouldBeCalledTimes(1)->willThrow($exception);
                $this->logger->error($exception->getMessage(), [])->shouldBeCalledTimes(1);
                ++$failedCount;
            } else {
                $this->productFactory->create($productDirectory)
                    ->shouldBeCalledTimes(1)->willReturn($product->reveal());
            }
        }

        $results = $this->subject->listProducts();

        $this->assertIsArray($results);
        $this->assertCount(count($productDirectories) - $failedCount, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(Product::class, $result);
            $this->assertContains($result->getKey(), $productDirectories);
        }
    }

    /**
     * @covers ::findProduct
     * @covers ::listProducts
     * @dataProvider findProductDataProvider
     * @small
     *
     * @param array  $productDirectories
     * @param string $productName
     * @param bool   $expectedToFindProduct
     *
     * @throws Exception
     */
    public function testFindProduct(array $productDirectories, string $productName, bool $expectedToFindProduct): void
    {
        $documentationDirectory = 'docs';

        $this->configProvider->getDocumentationDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn($documentationDirectory);
        $this->filesystem->directories(base_path($documentationDirectory))
            ->shouldBeCalledTimes(1)
            ->willReturn($productDirectories);

        foreach ($productDirectories as $productDirectory) {
            $key = strtolower(basename($productDirectory));
            /**
             * @var ObjectProphecy|Product
             */
            $product = $this->prophesize(Product::class);
            $product->getDefaultVersion()->willReturn('1.0');
            $product->getKey()->willReturn($key);

            $this->productFactory->create($productDirectory)
                ->shouldBeCalledTimes(1)->willReturn($product->reveal());
        }

        $result = $this->subject->findProduct($productName);

        if (!$expectedToFindProduct) {
            $this->assertNull($result);

            return;
        }

        $this->assertInstanceOf(Product::class, $result);
        $this->assertSame(strtolower($productName), $result->getKey());
    }

    /**
     * @return array
     */
    public function findProductDataProvider(): array
    {
        return [
            [
                ['Product 1', 'product 2'],
                'product 1',
                true,
            ],
            [
                ['Product 1', 'product 2', 'melon/product 23'],
                'Product 23',
                true,
            ],
            [
                ['Product 1', 'product 2', 'apples/product 23'],
                'Product 2343',
                false,
            ],
            [
                [],
                'Product 2',
                false,
            ],
        ];
    }
}
