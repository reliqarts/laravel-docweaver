<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service;

use League\CommonMark\ConverterInterface;
use ReliqArts\Docweaver\Contract\MarkdownParser as MDParser;

/**
 * Docweaver markdown helper.
 */
final class MarkdownParser implements MDParser
{
    /**
     * @var ConverterInterface
     */
    private ConverterInterface $converter;

    /**
     * CommonMarkConverter constructor.
     */
    public function __construct(ConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    public function parse(string $text): string
    {
        return $this->converter->convertToHtml($text);
    }
}
