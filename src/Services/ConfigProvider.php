<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Services;

use Illuminate\Contracts\Config\Repository as Config;
use ReliqArts\Docweaver\Contracts\ConfigProvider as ConfigProviderContract;
use ReliqArts\Docweaver\Models\TemplateConfig;

final class ConfigProvider implements ConfigProviderContract
{
    private const NAMESPACE = 'docweaver';
    private const KEY_CACHE_KEY = 'cache.key';
    private const KEY_DEBUG = 'debug';
    private const KEY_TABLE_OF_CONTENTS_PAGE_NAME = 'doc.index';
    private const KEY_VERSIONS_ALLOW_WORDED_DEFAULT = 'versions.allow_worded_default';
    private const KEY_VIEW_MASTER_TEMPLATE = 'view.master_template';
    private const KEY_ROUTE = 'route';
    private const KEY_STORAGE_DIRECTORY = 'storage.dir';
    private const KEY_ROUTE_BINDINGS = 'route.bindings';
    private const KEY_ROUTE_NAME_INDEX = 'route.names.index';
    private const KEY_ROUTE_NAME_PRODUCT_INDEX = 'route.names.product_index';
    private const KEY_ROUTE_NAME_PRODUCT_PAGE = 'route.names.product_page';
    private const KEY_ROUTE_PREFIX = 'route.prefix';
    private const KEY_VIEW_INDEX_TITLE = 'view.docs_title';
    private const KEY_VIEW_MASTER_SECTION = 'view.master_section';
    private const KEY_VIEW_STYLE_STACK = 'view.style_stack';
    private const KEY_VIEW_SCRIPT_STACK = 'view.script_stack';
    private const KEY_VIEW_INDEX_INTRO = 'view.docs_intro';
    private const KEY_VIEW_ACCENTS_PRODUCT_LINE = 'view.accents.product_line';
    private const KEY_VIEW_ACCENTS_FOOTNOTES = 'view.accents.footnotes';
    private const DEFAULT_INDEX_ROUTE_NAME = 'docs';
    private const DEFAULT_PRODUCT_INDEX_ROUTE_NAME = 'docs.index';
    private const DEFAULT_PRODUCT_PAGE_ROUTE_NAME = 'docs.show';
    private const DEFAULT_ROUTE_PREFIX = 'docs';
    private const DEFAULT_CACHE_KEY = 'docweaver.docs';
    private const DEFAULT_TABLE_OF_CONTENTS_PAGE_NAME = 'documentation';
    private const DEFAULT_INDEX_TITLE = 'Documentation';

    /**
     * @var Config
     */
    private $config;

    /**
     * ConfigProvider constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get directory path to where documentation are stored.
     *
     * @param bool $absolute whether to return full
     *
     * @return string
     */
    public function getDocumentationDirectory($absolute = false): string
    {
        $path = $this->get(self::KEY_STORAGE_DIRECTORY);

        if ($absolute) {
            $path = base_path($path);
        }

        return $path;
    }

    /**
     * Get route config.
     *
     * @return array
     */
    public function getRouteConfig(): array
    {
        return $this->get(self::KEY_ROUTE, []);
    }

    /**
     * Get route prefix for docs.
     *
     * @return string
     */
    public function getRoutePrefix(): string
    {
        return $this->get(self::KEY_ROUTE_PREFIX, self::DEFAULT_ROUTE_PREFIX);
    }

    /**
     * Get bindings for routes.
     *
     * @param array $bindings
     *
     * @return array
     */
    public function getRouteGroupBindings(array $bindings = []): array
    {
        $defaults = ['prefix' => $this->getRoutePrefix()];
        $bindings = array_merge($this->get(self::KEY_ROUTE_BINDINGS, []), $bindings);

        return array_merge($defaults, $bindings);
    }

    public function isDebug(): bool
    {
        return $this->get(self::KEY_DEBUG, false);
    }

    /**
     * @return bool
     */
    public function isWordedDefaultVersionAllowed(): bool
    {
        return $this->get(self::KEY_VERSIONS_ALLOW_WORDED_DEFAULT, false);
    }

    /**
     * @return string
     */
    public function getIndexRouteName(): string
    {
        return $this->get(self::KEY_ROUTE_NAME_INDEX, self::DEFAULT_INDEX_ROUTE_NAME);
    }

    /**
     * @return string
     */
    public function getProductIndexRouteName(): string
    {
        return $this->get(self::KEY_ROUTE_NAME_PRODUCT_INDEX, self::DEFAULT_PRODUCT_INDEX_ROUTE_NAME);
    }

    /**
     * @return string
     */
    public function getProductPageRouteName(): string
    {
        return $this->get(self::KEY_ROUTE_NAME_PRODUCT_PAGE, self::DEFAULT_PRODUCT_PAGE_ROUTE_NAME);
    }

    /**
     * @return string
     */
    public function getCacheKey(): string
    {
        return $this->get(self::KEY_CACHE_KEY, self::DEFAULT_CACHE_KEY);
    }

    /**
     * Page used as content index (or Table of Contents) for product documentation.
     *
     * @return string
     */
    public function getContentIndexPageName(): string
    {
        return $this->get(self::KEY_TABLE_OF_CONTENTS_PAGE_NAME, self::DEFAULT_TABLE_OF_CONTENTS_PAGE_NAME);
    }

    /**
     * @return TemplateConfig
     */
    public function getTemplateConfig(): TemplateConfig
    {
        $masterTemplate = $this->get(self::KEY_VIEW_MASTER_TEMPLATE);
        $masterSection = $this->get(self::KEY_VIEW_MASTER_SECTION);
        $styleStack = $this->get(self::KEY_VIEW_STYLE_STACK, '');
        $scriptStack = $this->get(self::KEY_VIEW_SCRIPT_STACK, '');
        $indexTitle = $this->get(self::KEY_VIEW_INDEX_TITLE, self::DEFAULT_INDEX_TITLE);
        $indexIntro = $this->get(self::KEY_VIEW_INDEX_INTRO, '');
        $showProductLine = $this->get(self::KEY_VIEW_ACCENTS_PRODUCT_LINE, true);
        $showFootnotes = $this->get(self::KEY_VIEW_ACCENTS_FOOTNOTES, true);

        return new TemplateConfig(
            $masterTemplate,
            $masterSection,
            $styleStack,
            $scriptStack,
            $indexTitle,
            $indexIntro,
            $showProductLine,
            $showFootnotes
        );
    }

    /**
     * @param null|string $key
     * @param mixed       $default
     *
     * @return mixed
     */
    private function get(?string $key, $default = null)
    {
        if (empty($key)) {
            return $this->config->get(self::NAMESPACE, []);
        }

        return $this->config->get(
            sprintf('%s.%s', self::NAMESPACE, $key),
            $default
        );
    }
}
