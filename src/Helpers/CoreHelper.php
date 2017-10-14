<?php

namespace ReliQArts\DocWeaver\Helpers;

use Config;
use ParsedownExtra;
use ReliQArts\DocWeaver\Traits\RouteHelper;
use ReliQArts\DocWeaver\Exceptions\ImplementationException;

/**
 * DocWeaver main helper class.
 */
class CoreHelper
{
    use RouteHelper;

    /**
     * Get config.
     *
     * @return array
     */
    public static function getConfig()
    {
        return Config::get('doc-weaver', []);
    }

    /**
     * Get directory path to where documentation are stored.
     *
     * @param bool $abs Whether to return full .
     *
     * @return void
     */
    public static function getDocsDir($abs = false)
    {
        $path = Config::get('doc-weaver.storage.dir');
        if ($abs) {
            $path = base_path($path);
        }

        return $path;
    }

    /**
     * Get directory path to where documentation are stored.
     *
     * @param bool $abs Whether to return full .
     * @throws \ReliQArts\DocWeaver\Exceptions\ImplementationException
     *
     * @return void
     */
    public static function getViewTemplateInfo()
    {
        $viewInfo = Config::get('doc-weaver.view');
        
        if (!view()->exists($viewInfo['master_template'])) {
            throw new ImplementationException("Master template view ${$viewInfo['master_template']} does not exist.");
        }

        return $viewInfo;
    }

    /**
     * Convert some text to markdown.
     *
     * @param string $text
     * @return string
     */
    public static function markdown($text)
    {
        return (new ParsedownExtra)->text($text);
    }
}
