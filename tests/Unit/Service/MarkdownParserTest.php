<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service;

use Exception;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Output\RenderedContentInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Contract\Logger;
use ReliqArts\Docweaver\Contract\MarkdownParser as MarkdownParserContract;
use ReliqArts\Docweaver\Service\MarkdownParser;
use ReliqArts\Docweaver\Tests\Unit\TestCase;
use RuntimeException;

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
    private ObjectProphecy $converter;

    /**
     * @var Logger|ObjectProphecy
     */
    private ObjectProphecy $logger;

    /**
     * @var MarkdownParserContract
     */
    private $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->prophesize(Logger::class);
        $this->converter = $this->prophesize(ConverterInterface::class);
        $this->subject = new MarkdownParser($this->logger->reveal(), $this->converter->reveal());
    }

    /**
     * @covers ::__construct
     * @covers ::parse
     * @dataProvider textProvider
     *
     * @throws Exception
     */
    public function testParse(string $text, string $expectedResult): void
    {
        /** @var RenderedContentInterface|ObjectProphecy $renderedContent */
        $renderedContent = $this->prophesize(RenderedContentInterface::class);

        $this->converter->convert($text)
            ->shouldBeCalledTimes(1)
            ->willReturn($renderedContent->reveal());

        $renderedContent->getContent()
            ->shouldBeCalledTimes(1)
            ->willReturn($expectedResult);

        $result = $this->subject->parse($text);

        self::assertIsString($result);
        self::assertSame($result, $expectedResult);
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

    /**
     * @covers ::__construct
     * @covers ::parse
     *
     * @throws Exception
     */
    public function testParseWhenConversionFails(): void
    {
        $text = 'hi';
        $exceptionMessage = 'foo';

        $this->converter->convert($text)
            ->shouldBeCalledTimes(1)
            ->willThrow(new RuntimeException($exceptionMessage));

        $this->logger->error(Argument::containingString($exceptionMessage));

        $result = $this->subject->parse($text);

        self::assertIsString($result);
        self::assertSame($result, '');
    }
}
