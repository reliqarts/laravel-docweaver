<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service;

use League\CommonMark\ConverterInterface;
use ReliqArts\Docweaver\Contract\Logger as LoggerContract;
use ReliqArts\Docweaver\Contract\MarkdownParser as MDParser;
use RuntimeException;

/**
 * Docweaver markdown helper.
 */
final class MarkdownParser implements MDParser
{
    private LoggerContract $logger;
    private ConverterInterface $converter;

    public function __construct(LoggerContract $logger, ConverterInterface $converter)
    {
        $this->logger = $logger;
        $this->converter = $converter;
    }

    public function parse(string $text): string
    {
        try {
            return $this->converter->convert($text)
                ->getContent();
        } catch (RuntimeException $exception) {
            $this->logger->error(
                sprintf('Failed to convert markdown to html. Exception: %s', $exception->getMessage())
            );

            return '';
        }
    }
}
