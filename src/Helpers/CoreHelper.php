<?php

namespace ReliQArts\Docweaver\Helpers;

use Config;
use ParsedownExtra;
use ReliQArts\Docweaver\Traits\RouteHelper;
use ReliQArts\Docweaver\Exceptions\ImplementationException;

/**
 * Docweaver main helper class.
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
        return Config::get('docweaver', []);
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
        $path = Config::get('docweaver.storage.dir');
        if ($abs) {
            $path = base_path($path);
        }

        return $path;
    }

    /**
     * Get directory path to where documentation are stored.
     *
     * @param bool $abs Whether to return full .
     * @throws \ReliQArts\Docweaver\Exceptions\ImplementationException
     *
     * @return void
     */
    public static function getViewTemplateInfo()
    {
        $viewInfo = Config::get('docweaver.view');
        
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
