<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service;

use League\CommonMark\ConverterInterface;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Contract\MarkdownParser as MarkdownParserContract;
use ReliqArts\Docweaver\Service\MarkdownParser;
use ReliqArts\Docweaver\Tests\Unit\TestCase;

/**
 * Class ConfigProviderTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Service\MarkdownParser
 *
 * @internal
 */
final class MarkdownParserTest extends TestCase
{
    /**
     * @var ConverterInterface|ObjectProphecy
     */
    private $converter;

    /**
     * @var MarkdownParserContract
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = $this->prophesize(ConverterInterface::class);
        $this->subject = new MarkdownParser($this->converter->reveal());
    }

    /**
     * @covers ::__construct
     * @covers ::parse
     * @dataProvider textProvider
     */
    public function testParse(string $text, string $expectedResult): void
    {
        $this->converter->convertToHtml($text)
            ->shouldBeCalledTimes(1)
            ->willReturn($expectedResult);

        $result = $this->subject->parse($text);
        $this->assertIsString($result);
        $this->assertSame($result, $expectedResult);
    }

    public function textProvider(): array
    {
        return [
            [
                '#hello',
                '<h1>hello</h1>',
            ],
            [
                '',
                '',
            ],
        ];
    }
}
