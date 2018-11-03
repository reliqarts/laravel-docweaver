<?php

namespace ReliQArts\Docweaver\Helpers;

use ParsedownExtra;

/**
 * Docweaver markdown helper.
 */
class Markdown
{
    /**
     * Convert markdown text to HTML text.
     *
     * @param string $text
     *
     * @return string
     */
    public static function parse(string $text): string
    {
        return (new ParsedownExtra())->text($text);
    }
}
