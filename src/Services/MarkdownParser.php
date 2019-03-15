<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Services;

use ParsedownExtra;
use ReliQArts\Docweaver\Contracts\MarkdownParser as MDParser;

/**
 * Docweaver markdown helper.
 */
final class MarkdownParser implements MDParser
{
    /**
     * @var ParsedownExtra
     */
    private $interpreter;

    /**
     * MarkdownParser constructor.
     *
     * @param ParsedownExtra $interpreter
     */
    public function __construct(ParsedownExtra $interpreter)
    {
        $this->interpreter = $interpreter;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function parse(string $text): string
    {
        return $this->interpreter->text($text);
    }
}
