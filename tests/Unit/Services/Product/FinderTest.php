<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit\Services\Product;

use Prophecy\Prophecy\ObjectProphecy;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Contracts\Logger;
use ReliQArts\Docweaver\Contracts\Product\Finder as FinderContract;
use ReliQArts\Docweaver\Contracts\Product\Maker as ProductFactory;
use ReliQArts\Docweaver\Models\Product;
use ReliQArts\Docweaver\Services\Product\Finder;
use ReliQArts\Docweaver\Tests\Unit\TestCase;

/**
 * Class FinderTest
 *
 * @coversDefaultClass \ReliQArts\Docweaver\Services\Product\Finder
 */
final class FinderTest extends TestCase
{
    /**
     * @var Logger|ObjectProphecy
     */
    private $logger;

    /**
     * @var ProductFactory|ObjectProphecy
     */
    private $productFactory;

    /**
     * @var FinderContract
     */
    private $subject;

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
     * @covers ::listProducts
     * @covers ::__construct
     * @small
     *
     * @return void
     * @throws Exception
     */
    public function testListProducts(): void
    {
        $documentationDirectory = 'docs';
        $productDirectories = ['Product 1', 'product 2'];

        $this->configProvider->getDocumentationDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn($documentationDirectory);
        $this->filesystem->directories(base_path($documentationDirectory))
            ->shouldBeCalledTimes(1)
            ->willReturn($productDirectories);

        foreach ($productDirectories as $productDirectory) {
            $key = basename($productDirectory);
            /**
             * @var Product|ObjectProphecy $product
             */
            $product = $this->prophesize(Product::class);
            $product->getDefaultVersion()->willReturn('1.0');
            $product->getKey()->willReturn($key);

            $this->productFactory->create($productDirectory)
                ->shouldBeCalledTimes(1)->willReturn($product->reveal());
        }

        $results = $this->subject->listProducts();

        $this->assertIsArray($results);
        $this->assertCount(count($productDirectories), $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(Product::class, $result);
            $this->assertContains($result->getKey(), $productDirectories, '', true);
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
             * @var Product|ObjectProphecy $product
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
