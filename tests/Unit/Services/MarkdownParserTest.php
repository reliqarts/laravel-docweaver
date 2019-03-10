<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit\Services;

use ParsedownExtra;
use Prophecy\Prophecy\ObjectProphecy;
use ReliQArts\Docweaver\Contracts\MarkdownParser as MarkdownParserContract;
use ReliQArts\Docweaver\Services\MarkdownParser;
use ReliQArts\Docweaver\Tests\Unit\TestCase;

/**
 * Class ConfigProviderTest
 *
 * @coversDefaultClass \ReliQArts\Docweaver\Services\MarkdownParser
 */
final class MarkdownParserTest extends TestCase
{
    /**
     * @var ParsedownExtra|ObjectProphecy
     */
    private $interpreter;

    /**
     * @var MarkdownParserContract
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interpreter = $this->prophesize(ParsedownExtra::class);
        $this->subject = new MarkdownParser($this->interpreter->reveal());
    }

    /**
     * @covers ::parse
     * @covers ::__construct
     * @dataProvider textProvider
     * @param string $text
     * @param string $expectedResult
     */
    public function testParse(string $text, string $expectedResult): void
    {
        $this->interpreter->text($text)
            ->shouldBeCalledTimes(1)->willReturn($expectedResult);

        $result = $this->subject->parse($text);
        $this->assertIsString($result);
        $this->assertSame($result, $expectedResult);
    }

    /**
     * @return array
     */
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
