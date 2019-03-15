<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Contracts;

interface MarkdownParser
{
    /**
     * Convert markdown text to HTML text.
     *
     * @param string $text
     *
     * @return string
     */
    public function parse(string $text): string;
}
