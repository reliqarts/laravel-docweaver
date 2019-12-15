<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service;

use ParsedownExtra;
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
     * @var ObjectProphecy|ParsedownExtra
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
     * @covers ::__construct
     * @covers ::parse
     * @dataProvider textProvider
     */
    public function testParse(string $text, string $expectedResult): void
    {
        $this->interpreter->text($text)
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
