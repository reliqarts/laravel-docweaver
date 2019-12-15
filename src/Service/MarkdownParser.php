<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service;

use ParsedownExtra;
use ReliqArts\Docweaver\Contract\MarkdownParser as MDParser;

/**
 * Docweaver markdown helper.
 */
final class MarkdownParser implements MDParser
{
    /**
     * @var ParsedownExtra
     */
    private ParsedownExtra $interpreter;

    /**
     * MarkdownParser constructor.
     */
    public function __construct(ParsedownExtra $interpreter)
    {
        $this->interpreter = $interpreter;
    }

    public function parse(string $text): string
    {
        return $this->interpreter->text($text);
    }
}
