<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Factory;

use Illuminate\Support\Str;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\FileHelper;
use ReliqArts\Docweaver\Contract\YamlHelper;
use ReliqArts\Docweaver\Factory\ProductMaker;
use ReliqArts\Docweaver\Tests\Unit\TestCase;

/**
 * Class ProductMakerTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Factory\ProductMaker
 *
 * @internal
 */
final class ProductMakerTest extends TestCase
{
    /**
     * @var ObjectProphecy|FileHelper
     */
    private ObjectProphecy $fileHelper;

    /**
     * @var ObjectProphecy|YamlHelper
     */
    private ObjectProphecy $yamlHelper;

    private ProductMaker $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileHelper = $this->prophesize(FileHelper::class);
        $this->yamlHelper = $this->prophesize(YamlHelper::class);

        $this->configProvider->isWordedDefaultVersionAllowed()
            ->willReturn(true);

        $this->subject = new ProductMaker(
            $this->filesystem->reveal(),
            $this->configProvider->reveal(),
            $this->fileHelper->reveal(),
            $this->yamlHelper->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::create
     * @small
     *
     * @throws Exception
     */
    public function testCreate(): void
    {
        $directory = 'docs/product 1';
        $expectedKey = basename($directory);
        $expectedName = Str::title($expectedKey);

        $this->filesystem->isDirectory($directory)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->directories($directory)
            ->shouldBeCalledTimes(1)
            ->willReturn([]);
        $this->filesystem->lastModified($directory)
            ->shouldBeCalledTimes(1)
            ->willReturn(10);

        $result = $this->subject->create($directory);

        self::assertSame($directory, $result->getDirectory());
        self::assertSame($expectedKey, $result->getKey());
        self::assertSame($expectedName, $result->getName());
    }

    /**
     * @covers ::__construct
     * @covers ::create
     * @small
     */
    public function testCreateThrowsExceptionIfDirectoryIsInvalid(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid directory: `docs/product 1`.');
        $this->expectExceptionCode(4002);

        $directory = 'docs/product 1';

        $this->filesystem->isDirectory($directory)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->filesystem->directories($directory)
            ->shouldNotBeCalled();
        $this->filesystem->lastModified($directory)
            ->shouldNotBeCalled();

        $this->subject->create($directory);
    }
}
