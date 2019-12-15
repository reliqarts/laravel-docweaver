<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract;

use ReliqArts\Docweaver\Model\TemplateConfig;

interface ConfigProvider
{
    /**
     * Get directory path to where documentation are stored.
     *
     * @param bool $absolute whether to return full
     *
     * @return string
     */
    public function getDocumentationDirectory($absolute = false): string;

    /**
     * Get route config.
     *
     * @return array
     */
    public function getRouteConfig(): array;

    /**
     * Get route prefix for docs.
     *
     * @return string
     */
    public function getRoutePrefix(): string;

    /**
     * Get bindings for routes.
     *
     * @param array $bindings
     *
     * @return array
     */
    public function getRouteGroupBindings(array $bindings = []): array;

    /**
     * @return string
     */
    public function getIndexRouteName(): string;

    /**
     * @return string
     */
    public function getProductIndexRouteName(): string;

    /**
     * @return string
     */
    public function getProductPageRouteName(): string;

    public function isDebug(): bool;

    /**
     * @return bool
     */
    public function isWordedDefaultVersionAllowed(): bool;

    /**
     * @return string
     */
    public function getCacheKey(): string;

    /**
     * Page used as content index (or Table of Contents) for product documentation.
     *
     * @return string
     */
    public function getContentIndexPageName(): string;

    /**
     * @return TemplateConfig
     */
    public function getTemplateConfig(): TemplateConfig;
}
