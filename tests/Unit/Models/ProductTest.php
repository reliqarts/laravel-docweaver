<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use ReliQArts\Docweaver\Contracts\Exception;
use ReliQArts\Docweaver\Models\Product;
use ReliQArts\Docweaver\Tests\Unit\TestCase;

/**
 * Class ProductTest.
 *
 * @coversDefaultClass \ReliQArts\Docweaver\Models\Product
 *
 * @internal
 */
final class ProductTest extends TestCase
{
    private const PRODUCT_DIRECTORY = '/foo/bar/docs/alpha';

    /**
     * @var Product
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider->isWordedDefaultVersionAllowed()->willReturn(true);

        $this->subject = new Product(
            $this->filesystem->reveal(),
            $this->configProvider->reveal(),
            self::PRODUCT_DIRECTORY
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getDefaultVersion
     * @covers ::getDescription
     * @covers ::getDirectory
     * @covers ::getImageUrl
     * @covers ::getKey
     * @covers ::getLastModified
     * @covers ::getName
     * @covers ::getVersions
     * @covers ::hasVersion
     * @covers ::loadMeta
     * @covers ::loadVersions
     * @covers ::populate
     * @covers ::toArray
     * @covers ::toJson
     * @small
     *
     * @throws Exception
     */
    public function testPopulateWithNoMeta(): void
    {
        $lastModified = 123456789;
        $productName = Str::title(basename(self::PRODUCT_DIRECTORY));
        $productKey = strtolower($productName);
        $versionDirectories = [
            sprintf('%s/master', self::PRODUCT_DIRECTORY),
            sprintf('%s/1.0', self::PRODUCT_DIRECTORY),
            sprintf('%s/2.0', self::PRODUCT_DIRECTORY),
        ];
        $expectedVersions = array_map(function (string $versionDirectory) {
            return basename($versionDirectory);
        }, $versionDirectories);

        $this->filesystem->directories(self::PRODUCT_DIRECTORY)
            ->shouldBeCalledTimes(1)->willReturn($versionDirectories);
        $this->filesystem->lastModified(self::PRODUCT_DIRECTORY)
            ->shouldBeCalledTimes(1)->willReturn($lastModified);
        $this->configProvider->getRoutePrefix()->shouldNotBeCalled();

        $this->subject->populate();

        $productArray = $this->subject->toArray();
        $productJson = $this->subject->toJson();
        $productVersions = $this->subject->getVersions();

        $this->assertIsArray($productVersions);
        $this->assertSame(
            'master',
            $this->subject->getDefaultVersion(),
            'Product default version is not as expected.'
        );
        $this->assertSame(
            Carbon::createFromTimestamp($lastModified)->toAtomString(),
            $this->subject->getLastModified()->toAtomString(),
            'Product last modified time is not as expected.'
        );
        $this->assertSame(
            self::PRODUCT_DIRECTORY,
            $this->subject->getDirectory(),
            'Product directory is not as expected.'
        );
        $this->assertSame($productName, $this->subject->getName(), 'Product name is not as expected.');
        $this->assertSame($productKey, $this->subject->getKey(), 'Product key is not as expected.');
        $this->assertCount(count($expectedVersions), $productVersions);
        $this->assertNull($this->subject->getDescription());
        $this->assertNull($this->subject->getImageUrl());
        $this->assertNotEmpty($productArray);
        $this->assertContains($productName, $productArray);
        $this->assertContains($productKey, $productArray);
        $this->assertCount(count($productArray), json_decode($productJson, true));

        foreach ($expectedVersions as $expectedVersion) {
            $versionName = Str::title($expectedVersion);
            $this->assertArrayHasKey($expectedVersion, $productVersions);
            $this->assertContains($versionName, $productVersions);
            $this->assertTrue($this->subject->hasVersion($expectedVersion));
        }
    }
}
