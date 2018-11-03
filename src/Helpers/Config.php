<?php

namespace ReliQArts\Docweaver\Helpers;

use Config as BaseConfig;
use ReliQArts\Docweaver\Exceptions\BadImplementation;

/**
 * Docweaver config helper.
 */
class Config
{
    /**
     * Get config.
     *
     * @return array
     */
    public static function getConfig(): array
    {
        return BaseConfig::get('docweaver', []);
    }

    /**
     * Get directory path to where documentation are stored.
     *
     * @param bool $abs whether to return full
     *
     * @return string
     */
    public static function getDocsDir($abs = false): string
    {
        $path = BaseConfig::get('docweaver.storage.dir');
        if ($abs) {
            $path = base_path($path);
        }

        return $path;
    }

    /**
     * Get route config.
     *
     * @return array
     */
    public static function getRouteConfig(): array
    {
        return BaseConfig::get('docweaver.route', []);
    }

    /**
     * Get route prefix for docs.
     *
     * @return string
     */
    public static function getRoutePrefix(): string
    {
        return BaseConfig::get('docweaver.route.prefix', 'docs');
    }

    /**
     * Get bindings for routes.
     *
     * @param array $bindings
     *
     * @return array
     */
    public static function getRouteGroupBindings(array $bindings = []): array
    {
        $defaults = ['prefix' => self::getRoutePrefix()];
        $bindings = array_merge(BaseConfig::get('docweaver.route.bindings', []), $bindings);

        return array_merge($defaults, $bindings);
    }

    /**
     * Get directory path to where documentation are stored.
     *
     * @throws \ReliQArts\Docweaver\Exceptions\BadImplementation
     *
     * @return array
     */
    public static function getViewTemplateInfo(): array
    {
        $viewInfo = BaseConfig::get('docweaver.view');

        if (!view()->exists($viewInfo['master_template'])) {
            throw new BadImplementation("Master template view ${$viewInfo['master_template']} does not exist.");
        }

        return $viewInfo;
    }
}
