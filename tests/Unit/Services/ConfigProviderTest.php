<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Models\TemplateConfig;
use ReliqArts\Docweaver\Services\ConfigProvider;
use ReliqArts\Docweaver\Tests\Unit\TestCase;

/**
 * Class ConfigProviderTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Services\ConfigProvider
 *
 * @internal
 */
final class ConfigProviderTest extends TestCase
{
    private const ARBITRARY_CONFIG_VALUE = 'value';
    private const ARBITRARY_CONFIG_ARRAY = ['key' => self::ARBITRARY_CONFIG_VALUE];

    /**
     * @var Config|ObjectProphecy
     */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->prophesize(Config::class);
        $this->config->get(Argument::type('string'), Argument::type('array'))->willReturn(self::ARBITRARY_CONFIG_ARRAY);
        $this->config->get(Argument::type('string'), Argument::type('bool'))->willReturn(true);
        $this->config->get(Argument::type('string'), Argument::cetera())->willReturn(self::ARBITRARY_CONFIG_VALUE);
        $this->configProvider = new ConfigProvider($this->config->reveal());
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getDocumentationDirectory
     */
    public function testGetDocumentationDirectory(): void
    {
        $result1 = $this->configProvider->getDocumentationDirectory();
        $result2 = $this->configProvider->getDocumentationDirectory(true);

        $this->assertIsString($result1);
        $this->assertIsString($result2);
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result1);
        $this->assertSame(base_path(self::ARBITRARY_CONFIG_VALUE), $result2);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getRouteConfig
     */
    public function testGetRouteConfig(): void
    {
        $result = $this->configProvider->getRouteConfig();

        $this->assertIsArray($result);
        $this->assertSame(self::ARBITRARY_CONFIG_ARRAY, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getRoutePrefix
     */
    public function testGetRoutePrefix(): void
    {
        $result = $this->configProvider->getRoutePrefix();

        $this->assertIsString($result);
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getRouteGroupBindings
     */
    public function testGetRouteGroupBindings(): void
    {
        $result = $this->configProvider->getRouteGroupBindings();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('prefix', $result);
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result['key']);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::isDebug
     */
    public function testIsDebug(): void
    {
        $result = $this->configProvider->isDebug();

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::isWordedDefaultVersionAllowed
     */
    public function testIsWordedDefaultVersionAllowed(): void
    {
        $result = $this->configProvider->isWordedDefaultVersionAllowed();

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getIndexRouteName
     */
    public function testGetIndexRouteName(): void
    {
        $result = $this->configProvider->getIndexRouteName();

        $this->assertIsString($result);
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getProductIndexRouteName
     */
    public function testGetProductIndexRouteName(): void
    {
        $result = $this->configProvider->getProductIndexRouteName();

        $this->assertIsString($result);
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getProductPageRouteName
     */
    public function testGetProductPageRouteName(): void
    {
        $result = $this->configProvider->getProductPageRouteName();

        $this->assertIsString($result);
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getCacheKey
     */
    public function testGetCacheKey(): void
    {
        $result = $this->configProvider->getCacheKey();

        $this->assertIsString($result);
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getContentIndexPageName
     */
    public function testGetContentIndexPageName(): void
    {
        $result = $this->configProvider->getContentIndexPageName();

        $this->assertIsString($result);
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getTemplateConfig
     * @covers \ReliqArts\Docweaver\Models\TemplateConfig
     */
    public function testGetTemplateConfig(): void
    {
        $result = $this->configProvider->getTemplateConfig();

        $this->assertInstanceOf(TemplateConfig::class, $result);
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result->getMasterTemplate());
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result->getMasterSection());
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result->getStyleStack());
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result->getScriptStack());
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result->getIndexTitle());
        $this->assertSame(self::ARBITRARY_CONFIG_VALUE, $result->getIndexIntro());
        $this->assertTrue($result->isShowProductLine());
        $this->assertTrue($result->isShowFootnotes());
        $this->assertTrue($result->hasStyleStack());
        $this->assertTrue($result->hasScriptStack());
    }
}
