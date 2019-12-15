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
