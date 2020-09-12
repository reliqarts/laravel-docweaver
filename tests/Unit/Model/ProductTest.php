<?php

/**
 * @noinspection PhpParamsInspection
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpStrictTypeCheckingInspection
 */

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Model;

use Carbon\Carbon;
use Illuminate\Support\Str;
use JsonException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Contract\Exception;
use ReliqArts\Docweaver\Contract\FileHelper;
use ReliqArts\Docweaver\Contract\YamlHelper;
use ReliqArts\Docweaver\Exception\ParsingFailed;
use ReliqArts\Docweaver\Exception\Product\AssetPublicationFailed;
use ReliqArts\Docweaver\Exception\Product\InvalidAssetDirectory;
use ReliqArts\Docweaver\Model\Product;
use ReliqArts\Docweaver\Tests\Unit\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class ProductTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Model\Product
 *
 * @internal
 */
final class ProductTest extends TestCase
{
    private const PRODUCT_DIRECTORY = '/foo/bar/docs/alpha';
    private const ARBITRARY_FILE_CONTENTS = 'file.contents';
    private const ARBITRARY_REAL_PATH = 'real/path';

    /**
     * @var string[]
     */
    private array $expectedVersions;

    /**
     * @var string[]
     */
    private array $versionDirectories;

    /**
     * @var FileHelper|ObjectProphecy
     */
    private ObjectProphecy $fileHelper;

    /**
     * @var YamlHelper|ObjectProphecy
     */
    private ObjectProphecy $yamlHelper;

    private int $lastModified;
    private string $productName;
    private string $productKey;
    private string $routePrefix;
    private Product $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileHelper = $this->prophesize(FileHelper::class);
        $this->yamlHelper = $this->prophesize(YamlHelper::class);
        $this->routePrefix = 'docs';
        $this->productName = Str::title(basename(self::PRODUCT_DIRECTORY));
        $this->productKey = strtolower($this->productName);
        $this->lastModified = 123456789;
        $this->versionDirectories = [
            sprintf('%s/master', self::PRODUCT_DIRECTORY),
            sprintf('%s/1.0', self::PRODUCT_DIRECTORY),
            sprintf('%s/2.0', self::PRODUCT_DIRECTORY),
        ];
        $this->expectedVersions = array_map(
            static function (string $versionDirectory) {
                return basename($versionDirectory);
            },
            $this->versionDirectories
        );

        $this->filesystem->directories(self::PRODUCT_DIRECTORY)
            ->shouldBeCalledTimes(1)
            ->willReturn($this->versionDirectories);
        $this->filesystem->lastModified(self::PRODUCT_DIRECTORY)
            ->shouldBeCalledTimes(1)
            ->willReturn($this->lastModified);

        $this->fileHelper->getFileContents(Argument::cetera())
            ->willReturn(self::ARBITRARY_FILE_CONTENTS);
        $this->fileHelper->realPath(Argument::cetera())
            ->willReturn(self::ARBITRARY_REAL_PATH);

        $this->configProvider->getRoutePrefix()
            ->shouldBeCalledTimes(1)
            ->willReturn($this->routePrefix);

        $this->subject = new Product(
            $this->filesystem->reveal(),
            $this->configProvider->reveal(),
            $this->fileHelper->reveal(),
            $this->yamlHelper->reveal(),
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
     * @throws Exception|JsonException
     */
    public function testPopulateWithNoMeta(): void
    {
        $this->fileHelper->realPath(Argument::cetera())
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->configProvider->isWordedDefaultVersionAllowed()
            ->willReturn(true);
        $this->configProvider->getRoutePrefix()
            ->shouldNotBeCalled();

        $this->subject->populate();

        $productArray = $this->subject->toArray();
        $productJson = $this->subject->toJson();
        $productVersions = $this->subject->getVersions();

        self::assertIsArray($productVersions);
        self::assertSame(
            'master',
            $this->subject->getDefaultVersion(),
            'Product default version is not as expected.'
        );
        self::assertSame(
            Carbon::createFromTimestamp($this->lastModified)->toAtomString(),
            $this->subject->getLastModified()->toAtomString(),
            'Product last modified time is not as expected.'
        );
        self::assertSame(
            self::PRODUCT_DIRECTORY,
            $this->subject->getDirectory(),
            'Product directory is not as expected.'
        );
        self::assertSame($this->productName, $this->subject->getName(), 'Product name is not as expected.');
        self::assertSame($this->productKey, $this->subject->getKey(), 'Product key is not as expected.');
        self::assertCount(count($this->expectedVersions), $productVersions);
        self::assertEmpty($this->subject->getDescription());
        self::assertEmpty($this->subject->getImageUrl());
        self::assertNotEmpty($productArray);
        self::assertContains($this->productName, $productArray);
        self::assertContains($this->productKey, $productArray);
        self::assertCount(count($productArray), json_decode($productJson, true, 512, JSON_THROW_ON_ERROR));

        foreach ($this->expectedVersions as $expectedVersion) {
            $versionName = Str::title($expectedVersion);
            self::assertArrayHasKey($expectedVersion, $productVersions);
            self::assertContains($versionName, $productVersions);
            self::assertTrue($this->subject->hasVersion($expectedVersion));
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
     * @throws Exception
     * @noinspection             DisconnectedForeachInstructionInspection
     */
    public function testPopulateWithMeta(): void
    {
        $metaProductName = 'Bravo';
        $description = 'A product';
        $expectedDefaultVersion = '2.0';
        $metaImageScenarios = $this->getMetaImageScenariosForVersion($expectedDefaultVersion);

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

            $this->yamlHelper->parse(Argument::type('string'))
                ->shouldBeCalledTimes(2)
                ->willReturn($metaInfo);

            $this->subject->populate();

            self::assertSame(
                $expectedImageUrl,
                $this->subject->getImageUrl(),
                'Product image URL is not as expected.'
            );

            self::assertSame(
                $metaProductName,
                $this->subject->getName(),
                'Product name is not as expected.'
            );

            self::assertSame(
                $description,
                $this->subject->getDescription(),
                'Product description is not as expected.'
            );

            self::assertSame(
                $expectedDefaultVersion,
                $this->subject->getDefaultVersion(),
                'Product default version is not as expected.'
            );
        }
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
        $this->expectExceptionMessage(sprintf('Failed to parse meta file `%s`. foo', self::ARBITRARY_REAL_PATH));

        $this->yamlHelper->parse(Argument::cetera())
            ->shouldBeCalled()
            ->willThrow(new ParseException('foo'));

        $this->configProvider->getRoutePrefix()
            ->shouldNotBeCalled();
        $this->configProvider->isWordedDefaultVersionAllowed()
            ->willReturn(false);

        $this->subject->populate();
    }

    /**
     * @covers ::publishAssets
     * @small
     *
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
     * @throws Exception
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
     *
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

    private function getMetaImageScenariosForVersion(string $version): array
    {
        $relativeImagePath = 'product-image.jpg';
        $absoluteImagePath = 'http://image.place/image.jpg';

        return [
            'relative image path' => [
                $relativeImagePath,
                asset(
                    sprintf(
                        'storage/%s/%s/%s/%s',
                        $this->routePrefix,
                        $this->productKey,
                        $version,
                        $relativeImagePath
                    )
                ),
            ],
            'absolute image path' => [
                $absoluteImagePath,
                $absoluteImagePath,
            ],
        ];
    }
}
