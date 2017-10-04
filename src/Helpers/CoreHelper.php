<?php

namespace ReliQArts\DocWeaver\Helpers;

use Hash;
use Config;
use ParsedownExtra;
use ReliQArts\DocWeaver\Traits\RouteHelper;

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
     * 
     * @return void
     */
    public static function getViewTemplateInfo()
    {
        $viewInfo = Config::get('doc-weaver.view');
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
