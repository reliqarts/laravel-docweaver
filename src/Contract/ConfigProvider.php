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
     */
    public function getDocumentationDirectory($absolute = false): string;

    /**
     * Get route config.
     */
    public function getRouteConfig(): array;

    /**
     * Get route prefix for docs.
     */
    public function getRoutePrefix(): string;

    /**
     * Get bindings for routes.
     */
    public function getRouteGroupBindings(array $bindings = []): array;

    public function getIndexRouteName(): string;

    public function getProductIndexRouteName(): string;

    public function getProductPageRouteName(): string;

    public function isDebug(): bool;

    public function isWordedDefaultVersionAllowed(): bool;

    public function getCacheKey(): string;

    /**
     * Page used as content index (or Table of Contents) for product documentation.
     */
    public function getContentIndexPageName(): string;

    public function getTemplateConfig(): TemplateConfig;
}
