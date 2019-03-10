<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit\Services\Documentation;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliQArts\Docweaver\Contracts\Documentation\Publisher as PublisherContract;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Contracts\Logger;
use ReliQArts\Docweaver\Contracts\Product\Maker as ProductFactory;
use ReliQArts\Docweaver\Contracts\Product\Publisher as ProductPublisher;
use ReliQArts\Docweaver\Exceptions\BadImplementation;
use ReliQArts\Docweaver\Models\Product;
use ReliQArts\Docweaver\Services\Documentation\Publisher;
use ReliQArts\Docweaver\Tests\Unit\TestCase;
use ReliQArts\Docweaver\VO\Result;
use stdClass;

/**
 * Class PublisherTest
 *
 * @coversDefaultClass \ReliQArts\Docweaver\Services\Documentation\Publisher
 */
final class PublisherTest extends TestCase
{
    /**
     * @var Logger|ObjectProphecy
     */
    private $logger;

    /**
     * @var ProductPublisher|ObjectProphecy
     */
    private $productPublisher;

    /**
     * @var string
     */
    private $documentationDirectory;

    /**
     * @var string
     */
    private $workingDirectory;

    /**
     * @var ProductFactory|ObjectProphecy
     */
    private $productFactory;

    /**
     * @var PublisherContract
     */
    private $subject;

    /**
     * @throws BadImplementation
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentationDirectory = 'docs';
        $this->workingDirectory = base_path($this->documentationDirectory);
        $this->logger = $this->prophesize(Logger::class);
        $this->productPublisher = $this->prophesize(ProductPublisher::class);
        $this->productFactory = $this->prophesize(ProductFactory::class);

        $this->configProvider->getDocumentationDirectory()->shouldBeCalled()->willReturn($this->documentationDirectory);
        $this->filesystem->isDirectory(base_path($this->documentationDirectory))->shouldBeCalled()->willReturn(true);
        $this->filesystem->isWritable(base_path($this->documentationDirectory))->shouldBeCalled()->willReturn(true);

        $this->subject = new Publisher(
            $this->filesystem->reveal(),
            $this->logger->reveal(),
            $this->configProvider->reveal(),
            $this->productPublisher->reveal(),
            $this->productFactory->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::readyResourceDirectory
     * @covers                   \ReliQArts\Docweaver\Services\Publisher::__construct
     * @small
     *
     * @expectedException \ReliQArts\Docweaver\Exceptions\BadImplementation
     * @expectedExceptionMessage Could not ready document resource directory `docs`
     */
    public function testExceptionIsThrownIfDocumentationDirectoryIsInvalid(): void
    {
        $directory = $this->workingDirectory;

        $this->filesystem->isDirectory($directory)->shouldBeCalled()->willReturn(false);
        $this->filesystem->makeDirectory($directory, Argument::type('int'), true)->shouldBeCalled();
        $this->filesystem->isWritable($directory)->shouldBeCalled()->willReturn(false);

        new Publisher(
            $this->filesystem->reveal(),
            $this->logger->reveal(),
            $this->configProvider->reveal(),
            $this->productPublisher->reveal(),
            $this->productFactory->reveal()
        );
    }

    /**
     * @covers ::publish
     * @covers ::setExecutionStartTime
     * @covers ::readyResourceDirectory
     * @covers ::tell
     * @covers ::getExecutionTime
     * @small
     *
     * @throws Exception
     */
    public function testPublish(): void
    {
        $productName = 'Product 24';
        $source = 'http://product-24.src';
        $productDirectory = sprintf('%s/%s', $this->workingDirectory, strtolower($productName));
        $product = $this->prophesize(Product::class)->reveal();
        $messages = ['Test message 1', 'Test message 2'];

        /**
         * @var Result|ObjectProphecy $productPublisherResult
         */
        $productPublisherResult = $this->prophesize(Result::class);
        $productPublisherResult->getMessages()->shouldBeCalledTimes(2)->willReturn($messages);
        $productPublisherResult->getData()->shouldBeCalledTimes(1)->willReturn(null);
        $productPublisherResult->setData(Argument::type(stdClass::class))
            ->shouldBeCalledTimes(1)
            ->willReturn($productPublisherResult->reveal());

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->productFactory->create($productDirectory)->shouldBeCalledTimes(1)->willReturn($product);
        $this->productPublisher->publish($product, $source)
            ->shouldBeCalledTimes(1)
            ->willReturn($productPublisherResult->reveal());

        $result = $this->subject->publish($productName, $source);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($messages, $result->getMessages());
    }

    /**
     * @covers ::publish
     * @covers ::setExecutionStartTime
     * @covers ::readyResourceDirectory
     * @covers ::getExecutionTime
     * @small
     * @throws Exception
     */
    public function testPublishWhenProductDirectoryIsNotWritable(): void
    {
        $productName = 'Product 24';
        $source = 'http://product-24.src';
        $productDirectory = sprintf('%s/%s', $this->workingDirectory, strtolower($productName));
        $expectedErrorMessage = sprintf('Product directory %s is not writable.', $productDirectory);

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(false);
        $this->productFactory->create($productDirectory)->shouldNotBeCalled();
        $this->productPublisher->publish(Argument::cetera())->shouldNotBeCalled();

        $result = $this->subject->publish($productName, $source);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($expectedErrorMessage, $result->getError());
    }

    /**
     * @covers ::update
     * @covers ::setExecutionStartTime
     * @covers ::readyResourceDirectory
     * @covers ::getExecutionTime
     * @covers ::tell
     * @small
     *
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $productName = 'Product 25';
        $productDirectory = sprintf('%s/%s', $this->workingDirectory, strtolower($productName));
        $product = $this->prophesize(Product::class)->reveal();
        $messages = ['Successfully updated X'];

        /**
         * @var Result|ObjectProphecy $productPublisherResult
         */
        $productPublisherResult = $this->prophesize(Result::class);
        $productPublisherResult->getMessages()->shouldBeCalledTimes(2)->willReturn($messages);
        $productPublisherResult->getData()->shouldBeCalledTimes(1)->willReturn(null);
        $productPublisherResult->setData(Argument::type(stdClass::class))
            ->shouldBeCalledTimes(1)
            ->willReturn($productPublisherResult->reveal());

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->productFactory->create($productDirectory)->shouldBeCalledTimes(1)->willReturn($product);
        $this->productPublisher->update($product)
            ->shouldBeCalledTimes(1)
            ->willReturn($productPublisherResult->reveal());

        $result = $this->subject->update($productName);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($messages, $result->getMessages());
    }

    /**
     * @covers ::update
     * @covers ::setExecutionStartTime
     * @covers ::readyResourceDirectory
     * @covers ::getExecutionTime
     * @small
     * @throws Exception
     */
    public function testUpdateWhenProductDirectoryIsNotWritable(): void
    {
        $productName = 'Product 24';
        $productDirectory = sprintf('%s/%s', $this->workingDirectory, strtolower($productName));
        $expectedErrorMessage = sprintf('Product directory %s is not writable.', $productDirectory);

        $this->filesystem->isDirectory($productDirectory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->isWritable($productDirectory)->shouldBeCalledTimes(1)->willReturn(false);
        $this->productFactory->create($productDirectory)->shouldNotBeCalled();
        $this->productPublisher->update(Argument::cetera())->shouldNotBeCalled();

        $result = $this->subject->update($productName);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($expectedErrorMessage, $result->getError());
    }

    /**
     * @covers ::updateAll
     * @covers ::update
     * @covers ::setExecutionStartTime
     * @covers ::readyResourceDirectory
     * @covers ::tell
     * @covers ::getExecutionTime
     * @dataProvider updateAllDataProvider
     * @small
     *
     * @param string[] $productDirectories
     *
     * @throws Exception
     */
    public function testUpdateAll(array $productDirectories): void
    {
        $product = $this->prophesize(Product::class)->reveal();
        $productDirectoryCount = count($productDirectories);
        $messages = ['Successfully updated X'];

        /**
         * @var Result|ObjectProphecy $productPublisherResult
         */
        $productPublisherResult = $this->prophesize(Result::class);
        $productPublisherResult->getMessages()->shouldBeCalledTimes($productDirectoryCount)->willReturn($messages);
        $productPublisherResult->getData()->shouldBeCalledTimes($productDirectoryCount)->willReturn(null);
        $productPublisherResult->isSuccess()->shouldBeCalledTimes($productDirectoryCount)->willReturn(true);
        $productPublisherResult->setData(Argument::type(stdClass::class))
            ->shouldBeCalledTimes($productDirectoryCount)
            ->willReturn($productPublisherResult->reveal());

        $this->filesystem->directories($this->workingDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn($productDirectories);
        $this->productPublisher->update($product)
            ->shouldBeCalledTimes($productDirectoryCount)
            ->willReturn($productPublisherResult->reveal());

        foreach ($productDirectories as $productDirectory) {
            $productCommonName = strtolower(basename($productDirectory));
            $fullProductDirectory = sprintf('%s/%s', $this->workingDirectory, $productCommonName);

            $this->filesystem->isDirectory($fullProductDirectory)->shouldBeCalledTimes(1)->willReturn(true);
            $this->filesystem->isWritable($fullProductDirectory)->shouldBeCalledTimes(1)->willReturn(true);
            $this->productFactory->create($fullProductDirectory)->shouldBeCalledTimes(1)->willReturn($product);
        }

        $result = $this->subject->updateAll();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($productDirectoryCount, $result->getData()->productsUpdated);
    }

    public function updateAllDataProvider(): array
    {
        return [
            'multiple products exists' => [
                ['product 1', 'product 2', 'product 3'],
            ],
            'no products exists' => [
                [],
            ],
        ];
    }
}
