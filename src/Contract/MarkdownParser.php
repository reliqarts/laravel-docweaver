<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract;

interface MarkdownParser
{
    /**
     * Convert markdown text to HTML text.
     */
    public function parse(string $text): string;
}
