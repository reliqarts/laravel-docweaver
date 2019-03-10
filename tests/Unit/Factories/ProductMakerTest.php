<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit\Factories;

use Illuminate\Support\Str;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Factories\ProductMaker;
use ReliQArts\Docweaver\Models\Product;
use ReliQArts\Docweaver\Tests\Unit\TestCase;

/**
 * Class ProductMakerTest
 *
 * @coversDefaultClass \ReliQArts\Docweaver\Factories\ProductMaker
 */
final class ProductMakerTest extends TestCase
{
    /**
     * @var ProductMaker
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider->isWordedDefaultVersionAllowed()->willReturn(true);

        $this->subject = new ProductMaker($this->filesystem->reveal(), $this->configProvider->reveal());
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

        $this->filesystem->isDirectory($directory)->shouldBeCalledTimes(1)->willReturn(true);
        $this->filesystem->directories($directory)->shouldBeCalledTimes(1)->willReturn([]);
        $this->filesystem->lastModified($directory)->shouldBeCalledTimes(1)->willReturn(10);

        $result = $this->subject->create($directory);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertSame($directory, $result->getDirectory());
        $this->assertSame($expectedKey, $result->getKey());
        $this->assertSame($expectedName, $result->getName());
    }

    /**
     * @covers ::__construct
     * @covers ::create
     * @small
     *
     * @expectedException \ReliQArts\Docweaver\Contracts\Exception
     * @expectedExceptionCode 4002
     * @expectedExceptionMessage Invalid directory: `docs/product 1`.
     */
    public function testCreateThrowsExceptionIfDirectoryIsInvalid(): void
    {
        $directory = 'docs/product 1';

        $this->filesystem->isDirectory($directory)->shouldBeCalledTimes(1)->willReturn(false);
        $this->filesystem->directories($directory)->shouldNotBeCalled();
        $this->filesystem->lastModified($directory)->shouldNotBeCalled();

        $this->subject->create($directory);
    }
}
