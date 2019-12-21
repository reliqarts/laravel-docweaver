<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service\Documentation;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Contract\Documentation\Publisher as PublisherContract;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\Logger;
use ReliqArts\Docweaver\Contract\Product\Maker as ProductFactory;
use ReliqArts\Docweaver\Contract\Product\Publisher as ProductPublisher;
use ReliqArts\Docweaver\Exception\BadImplementation;
use ReliqArts\Docweaver\Exception\DirectoryNotWritable;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Result;
use ReliqArts\Docweaver\Service\Documentation\Publisher;
use ReliqArts\Docweaver\Tests\Unit\TestCase;
use stdClass;

/**
 * Class PublisherTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Service\Documentation\Publisher
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
     * @var ObjectProphecy|ProductPublisher
     */
    private ObjectProphecy $productPublisher;

    /**
     * @var string
     */
    private string $documentationDirectory;

    /**
     * @var string
     */
    private string $workingDirectory;

    /**
     * @var ObjectProphecy|ProductFactory
     */
    private ObjectProphecy $productFactory;

    /**
     * @var PublisherContract
     */
    private PublisherContract $subject;

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

        $this->configProvider->getDocumentationDirectory()
            ->shouldBeCalled()
            ->willReturn($this->documentationDirectory);
        $this->filesystem->isDirectory(base_path($this->documentationDirectory))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesystem->isWritable(base_path($this->documentationDirectory))
            ->shouldBeCalled()
            ->willReturn(true);

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
     * @covers \ReliqArts\Docweaver\Service\Publisher::__construct
     * @covers \ReliqArts\Docweaver\Service\Publisher::readyResourceDirectory
     * @small
     */
    public function testExceptionIsThrownIfDocumentationDirectoryIsInvalid(): void
    {
        $this->expectException(BadImplementation::class);
        $this->expectExceptionMessage('Could not ready document resource directory `docs`');

        $directory = $this->workingDirectory;

        $this->filesystem->isDirectory($directory)
            ->shouldBeCalled()
            ->willReturn(false);
        $this->filesystem->makeDirectory($directory, Argument::type('int'), true)
            ->shouldBeCalled();
        $this->filesystem->isWritable($directory)
            ->shouldBeCalled()
            ->willReturn(false);

        /** @noinspection PhpUnusedLocalVariableInspection */
        $publisher = new Publisher(
            $this->filesystem->reveal(),
            $this->logger->reveal(),
            $this->configProvider->reveal(),
            $this->productPublisher->reveal(),
            $this->productFactory->reveal()
        );
    }

    /**
     * @covers ::getProductForPublishing
     * @covers ::publish
     * @covers \ReliqArts\Docweaver\Service\Publisher::getExecutionTime
     * @covers \ReliqArts\Docweaver\Service\Publisher::readyResourceDirectory
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
     * @covers \ReliqArts\Docweaver\Service\Publisher::setExecutionStartTime
     * @covers \ReliqArts\Docweaver\Service\Publisher::tell
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
         * @var ObjectProphecy|Result
         */
        $productPublisherResult = $this->prophesize(Result::class);

        $productPublisherResult->getMessages()
            ->shouldBeCalledTimes(2)
            ->willReturn($messages);
        $productPublisherResult->getExtra()
            ->shouldBeCalledTimes(1)
            ->willReturn(null);
        $productPublisherResult->setExtra(Argument::type(stdClass::class))
            ->shouldBeCalledTimes(1)
            ->willReturn($productPublisherResult->reveal());

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->productFactory->create($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn($product);
        $this->productPublisher->publish($product, $source)
            ->shouldBeCalledTimes(1)
            ->willReturn($productPublisherResult->reveal());

        $result = $this->subject->publish($productName, $source);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($messages, $result->getMessages());
    }

    /**
     * @covers ::getProductForPublishing
     * @covers ::publish
     * @covers \ReliqArts\Docweaver\Service\Publisher::getExecutionTime
     * @covers \ReliqArts\Docweaver\Service\Publisher::readyResourceDirectory
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
     * @covers \ReliqArts\Docweaver\Service\Publisher::setExecutionStartTime
     * @small
     *
     * @throws Exception
     */
    public function testPublishWhenProductDirectoryIsNotWritable(): void
    {
        $productName = 'Product 24';
        $source = 'http://product-24.src';
        $productDirectory = sprintf('%s/%s', $this->workingDirectory, strtolower($productName));
        $expectedErrorMessage = sprintf('Directory `%s` is not writable.', $productDirectory);

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->productFactory->create($productDirectory)
            ->shouldNotBeCalled();
        $this->productPublisher->publish(Argument::cetera())
            ->shouldNotBeCalled();

        $this->expectException(DirectoryNotWritable::class);
        $this->expectErrorMessage($expectedErrorMessage);

        $this->subject->publish($productName, $source);
    }

    /**
     * @covers ::getProductForPublishing
     * @covers ::update
     * @covers \ReliqArts\Docweaver\Service\Publisher::getExecutionTime
     * @covers \ReliqArts\Docweaver\Service\Publisher::readyResourceDirectory
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
     * @covers \ReliqArts\Docweaver\Service\Publisher::setExecutionStartTime
     * @covers \ReliqArts\Docweaver\Service\Publisher::tell
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
         * @var ObjectProphecy|Result
         */
        $productPublisherResult = $this->prophesize(Result::class);

        $productPublisherResult->getMessages()
            ->shouldBeCalledTimes(2)
            ->willReturn($messages);
        $productPublisherResult->getExtra()
            ->shouldBeCalledTimes(1)
            ->willReturn(null);
        $productPublisherResult->setExtra(Argument::type(stdClass::class))
            ->shouldBeCalledTimes(1)
            ->willReturn($productPublisherResult->reveal());

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->productFactory->create($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn($product);
        $this->productPublisher->update($product)
            ->shouldBeCalledTimes(1)
            ->willReturn($productPublisherResult->reveal());

        $result = $this->subject->update($productName);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($messages, $result->getMessages());
    }

    /**
     * @covers ::getProductForPublishing
     * @covers ::update
     * @covers \ReliqArts\Docweaver\Service\Publisher::getExecutionTime
     * @covers \ReliqArts\Docweaver\Service\Publisher::readyResourceDirectory
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
     * @covers \ReliqArts\Docweaver\Service\Publisher::setExecutionStartTime
     * @small
     *
     * @throws Exception
     */
    public function testUpdateWhenProductDirectoryIsNotWritable(): void
    {
        $productName = 'Product 245463547342';
        $productDirectory = sprintf('%s/%s', $this->workingDirectory, strtolower($productName));
        $expectedErrorMessage = sprintf('Directory `%s` is not writable.', $productDirectory);

        $this->filesystem->isDirectory($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->isWritable($productDirectory)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->productFactory->create($productDirectory)
            ->shouldNotBeCalled();
        $this->productPublisher->update(Argument::cetera())
            ->shouldNotBeCalled();

        $this->expectException(DirectoryNotWritable::class);
        $this->expectErrorMessage($expectedErrorMessage);

        $this->subject->update($productName);
    }

    /**
     * @covers ::getProductForPublishing
     * @covers ::update
     * @covers ::updateAll
     * @covers \ReliqArts\Docweaver\Service\Publisher::getExecutionTime
     * @covers \ReliqArts\Docweaver\Service\Publisher::readyResourceDirectory
     * @covers \ReliqArts\Docweaver\Service\Publisher::secondsSince
     * @covers \ReliqArts\Docweaver\Service\Publisher::setExecutionStartTime
     * @covers \ReliqArts\Docweaver\Service\Publisher::tell
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
         * @var ObjectProphecy|Result
         */
        $productPublisherResult = $this->prophesize(Result::class);
        $productPublisherResult->getMessages()
            ->shouldBeCalledTimes($productDirectoryCount)
            ->willReturn($messages);
        $productPublisherResult->getExtra()
            ->shouldBeCalledTimes($productDirectoryCount)
            ->willReturn(null);
        $productPublisherResult->isSuccess()
            ->shouldBeCalledTimes($productDirectoryCount)
            ->willReturn(true);
        $productPublisherResult->setExtra(Argument::type(stdClass::class))
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

            $this->filesystem->isDirectory($fullProductDirectory)
                ->shouldBeCalledTimes(1)
                ->willReturn(true);
            $this->filesystem->isWritable($fullProductDirectory)
                ->shouldBeCalledTimes(1)
                ->willReturn(true);
            $this->productFactory->create($fullProductDirectory)
                ->shouldBeCalledTimes(1)
                ->willReturn($product);
        }

        $result = $this->subject->updateAll();
        $productsUpdated = $result->getExtra()->productsUpdated;

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount($productDirectoryCount, $productsUpdated);

        foreach ($productDirectories as $productDirectory) {
            $productName = basename($productDirectory);

            $this->assertContains($productName, $productsUpdated);
        }
    }

    public function updateAllDataProvider(): array
    {
        return [
            'multiple products exists' => [
                ['product 1', 'Product 2', 'product 3'],
            ],
            'no products exists' => [
                [],
            ],
        ];
    }
}
