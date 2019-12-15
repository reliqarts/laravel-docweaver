<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Model;

use AspectMock\Test;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Prophecy\Argument;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Exception\ParsingFailed;
use ReliqArts\Docweaver\Exception\Product\AssetPublicationFailed;
use ReliqArts\Docweaver\Exception\Product\InvalidAssetDirectory;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Tests\Unit\AspectMockedTestCase;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ProductTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Model\Product
 *
 * @internal
 */
final class ProductTest extends AspectMockedTestCase
{
    private const PRODUCT_DIRECTORY = '/foo/bar/docs/alpha';

    /**
     * @var Product
     */
    private Product $subject;

    /**
     * @var string[]
     */
    private array $expectedVersions;

    /**
     * @var string[]
     */
    private array $versionDirectories;

    /**
     * @var int
     */
    private int $lastModified;

    /**
     * @var string
     */
    private string $productName;

    /**
     * @var string
     */
    private string $productKey;

    /**
     * @var string
     */
    private string $routePrefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routePrefix = 'docs';
        $this->namespace = '\ReliqArts\Docweaver\Model';
        $this->productName = Str::title(basename(self::PRODUCT_DIRECTORY));
        $this->productKey = strtolower($this->productName);
        $this->lastModified = 123456789;
        $this->versionDirectories = [
            sprintf('%s/master', self::PRODUCT_DIRECTORY),
            sprintf('%s/1.0', self::PRODUCT_DIRECTORY),
            sprintf('%s/2.0', self::PRODUCT_DIRECTORY),
        ];
        $this->expectedVersions = array_map(static function (string $versionDirectory) {
            return basename($versionDirectory);
        }, $this->versionDirectories);

        $this->filesystem->directories(self::PRODUCT_DIRECTORY)
            ->shouldBeCalledTimes(1)
            ->willReturn($this->versionDirectories);
        $this->filesystem->lastModified(self::PRODUCT_DIRECTORY)
            ->shouldBeCalledTimes(1)
            ->willReturn($this->lastModified);
        $this->configProvider->getRoutePrefix()
            ->shouldBeCalledTimes(1)
            ->willReturn($this->routePrefix);

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
        $this->configProvider->isWordedDefaultVersionAllowed()
            ->willReturn(true);
        $this->configProvider->getRoutePrefix()
            ->shouldNotBeCalled();

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
            Carbon::createFromTimestamp($this->lastModified)->toAtomString(),
            $this->subject->getLastModified()->toAtomString(),
            'Product last modified time is not as expected.'
        );
        $this->assertSame(
            self::PRODUCT_DIRECTORY,
            $this->subject->getDirectory(),
            'Product directory is not as expected.'
        );
        $this->assertSame($this->productName, $this->subject->getName(), 'Product name is not as expected.');
        $this->assertSame($this->productKey, $this->subject->getKey(), 'Product key is not as expected.');
        $this->assertCount(count($this->expectedVersions), $productVersions);
        $this->assertEmpty($this->subject->getDescription());
        $this->assertEmpty($this->subject->getImageUrl());
        $this->assertNotEmpty($productArray);
        $this->assertContains($this->productName, $productArray);
        $this->assertContains($this->productKey, $productArray);
        $this->assertCount(count($productArray), json_decode($productJson, true, 512, JSON_THROW_ON_ERROR));

        foreach ($this->expectedVersions as $expectedVersion) {
            $versionName = Str::title($expectedVersion);
            $this->assertArrayHasKey($expectedVersion, $productVersions);
            $this->assertContains($versionName, $productVersions);
            $this->assertTrue($this->subject->hasVersion($expectedVersion));
        }
    }

    /**
     * @covers ::__construct
     * @covers ::getAssetUrl
     * @covers ::getDefaultVersion
     * @covers ::getDescription
     * @covers ::getImageUrl
     * @covers ::getName
     * @covers ::loadMeta
     * @covers ::loadVersions
     * @covers ::populate
     * @medium
     * @preserveGlobalState      disabled
     * @runInSeparateProcess
     *
     * @throws Exception
     */
    public function testPopulateWithMeta(): void
    {
        $metaProductName = 'Bravo';
        $description = 'A product';
        $metaFile = 'meta-file';
        $expectedDefaultVersion = '2.0';
        $metaImageScenarios = $this->getMetaImageScenariosForVersion($expectedDefaultVersion);
        $metaImageScenarioCount = count($metaImageScenarios);

        $realPath = Test::func($this->namespace, 'realpath', $metaFile);
        $fileGetContents = Test::func($this->namespace, 'file_get_contents', $metaFile);
        $yaml = null;

        $this->filesystem->directories(self::PRODUCT_DIRECTORY)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->versionDirectories);
        $this->filesystem->lastModified(self::PRODUCT_DIRECTORY)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->lastModified);
        $this->configProvider->isWordedDefaultVersionAllowed()
            ->willReturn(false);

        foreach ($metaImageScenarios as $scenario) {
            [$imageFilename, $expectedImageUrl] = $scenario;
            $metaInfo = [
                'name' => $metaProductName,
                'description' => $description,
                'image_url' => stripos($imageFilename, 'http') === 0
                    ? $imageFilename
                    : sprintf('{{docs}}/%s', $imageFilename),
            ];

            $yaml = Test::double(Yaml::class, ['parse' => $metaInfo]);

            $this->subject->populate();

            $this->assertSame(
                $metaProductName,
                $this->subject->getName(),
                'Product name is not as expected.'
            );
            $this->assertSame(
                $description,
                $this->subject->getDescription(),
                'Product description is not as expected.'
            );
            $this->assertSame(
                $expectedImageUrl,
                $this->subject->getImageUrl(),
                'Product image URL is not as expected.'
            );
            $this->assertSame(
                $expectedDefaultVersion,
                $this->subject->getDefaultVersion(),
                'Product default version is not as expected.'
            );
        }

        $realPath->verifyInvokedMultipleTimes($metaImageScenarioCount);
        $fileGetContents->verifyInvokedMultipleTimes($metaImageScenarioCount);
        $yaml->verifyInvokedMultipleTimes('parse', $metaImageScenarioCount);
    }

    /**
     * /**
     * @covers ::__construct
     * @covers ::loadMeta
     * @covers ::loadVersions
     * @covers ::populate
     * @covers                   \ReliqArts\Docweaver\Exception\Exception::withMessage
     * @covers                   \ReliqArts\Docweaver\Exception\ParsingFailed
     * @small
     * @preserveGlobalState      disabled
     * @runInSeparateProcess
     *
     * @throws Exception
     * @throws \Exception
     */
    public function testPopulateWhenMetaIsInvalid(): void
    {
        $this->expectException(ParsingFailed::class);
        $this->expectExceptionMessage('Failed to parse meta file `meta-file`. foo');

        $metaFile = 'meta-file';

        $realPath = Test::func($this->namespace, 'realpath', $metaFile);
        $fileGetContents = Test::func($this->namespace, 'file_get_contents', $metaFile);
        $yaml = Test::double(Yaml::class, ['parse' => function () {
            throw new ParseException('foo');
        }]);

        $this->configProvider->getRoutePrefix()
            ->shouldNotBeCalled();
        $this->configProvider->isWordedDefaultVersionAllowed()
            ->willReturn(false);

        $this->subject->populate();

        $realPath->verifyInvokedOnce();
        $fileGetContents->verifyInvokedOnce([$metaFile]);
        $yaml->verifyInvokedOnce('parse');
    }

    /**
     * @covers ::publishAssets
     * @small
     * @throws Exception
     */
    public function testPublishAssets(): void
    {
        $version = '1.0';

        $this->filesystem->directories(self::PRODUCT_DIRECTORY)
            ->shouldNotBeCalled();
        $this->filesystem->lastModified(self::PRODUCT_DIRECTORY)
            ->shouldNotBeCalled();
        $this->filesystem->isDirectory(sprintf('%s/%s/images', self::PRODUCT_DIRECTORY, $version))
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->copyDirectory(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledTimes(1)
            ->willReturn(true);

        $this->subject->publishAssets($version);
    }

    /**
     * @covers ::publishAssets
     * @covers                   \ReliqArts\Docweaver\Exception\Product\InvalidAssetDirectory
     * @small
     */
    public function testPublishAssetsWhenImageDirectoryIsInvalid(): void
    {
        $this->expectException(InvalidAssetDirectory::class);
        $this->expectExceptionMessage('Invalid asset directory:');

        $version = '1.0';

        $this->filesystem->directories(self::PRODUCT_DIRECTORY)
            ->shouldNotBeCalled();
        $this->filesystem->lastModified(self::PRODUCT_DIRECTORY)
            ->shouldNotBeCalled();
        $this->filesystem->isDirectory(sprintf('%s/%s/images', self::PRODUCT_DIRECTORY, $version))
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->filesystem->copyDirectory(Argument::type('string'), Argument::type('string'))
            ->shouldNotBeCalled();

        $this->subject->publishAssets($version);
    }

    /**
     * @covers ::publishAssets
     * @covers                   \ReliqArts\Docweaver\Exception\Product\AssetPublicationFailed
     * @small
     * @throws Exception
     */
    public function testPublishAssetsWhenAssetPublicationFails(): void
    {
        $this->expectException(AssetPublicationFailed::class);
        $this->expectExceptionMessage('Failed to publish image assets for product `Alpha`');

        $version = '1.0';

        $this->filesystem->directories(self::PRODUCT_DIRECTORY)
            ->shouldNotBeCalled();
        $this->filesystem->lastModified(self::PRODUCT_DIRECTORY)
            ->shouldNotBeCalled();
        $this->filesystem->isDirectory(sprintf('%s/%s/images', self::PRODUCT_DIRECTORY, $version))
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem->copyDirectory(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->subject->publishAssets($version);
    }

    /**
     * @param string $version
     *
     * @return array
     */
    private function getMetaImageScenariosForVersion(string $version): array
    {
        $relativeImagePath = 'product-image.jpg';
        $absoluteImagePath = 'http://image.place/image.jpg';

        return [
            'relative image path' => [
                $relativeImagePath,
                asset(sprintf(
                    'storage/%s/%s/%s/%s',
                    $this->routePrefix,
                    $this->productKey,
                    $version,
                    $relativeImagePath
                )),
            ],
            'absolute image path' => [
                $absoluteImagePath,
                $absoluteImagePath,
            ],
        ];
    }
}
