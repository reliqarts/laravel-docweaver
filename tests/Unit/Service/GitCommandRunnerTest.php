<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service;

use AspectMock\Test;
use Exception;
use ReliqArts\Docweaver\Contract\VCSCommandRunner;
use ReliqArts\Docweaver\Service\GitCommandRunner;
use ReliqArts\Docweaver\Tests\Unit\AspectMockedTestCase;
use Symfony\Component\Process\Process;

/**
 * Class GitCommandRunnerTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Service\GitCommandRunner
 *
 * @internal
 */
final class GitCommandRunnerTest extends AspectMockedTestCase
{
    private const SPLIT_ERROR_STATE = 'error!';

    /**
     * @var VCSCommandRunner
     */
    private VCSCommandRunner $subject;

    /**
     * @var string
     */
    private string $workingDirectory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->namespace = '\ReliqArts\Docweaver\Service';
        $this->subject = new GitCommandRunner();
        $this->workingDirectory = 'dir';
    }

    /**
     * @covers ::clone
     * @small
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     *
     * @throws Exception
     */
    public function testClone(): void
    {
        $source = 'my-src';
        $branch = 'master';
        $returnedProcess = $this->prophesize(Process::class);
        $process = Test::double(Process::class, ['mustRun' => $returnedProcess->reveal()]);

        $this->subject->clone($source, $branch, $this->workingDirectory);

        $process->verifyInvokedMultipleTimes('mustRun', 1);

        $this->assertTrue(true);
    }

    /**
     * @covers ::listTags
     * @dataProvider        tagListProvider
     * @small
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     *
     * @throws Exception
     */
    public function testGetTags(string $tagList): void
    {
        $returnedProcess = $this->prophesize(Process::class);
        $process = Test::double(
            Process::class,
            [
                'mustRun' => $returnedProcess->reveal(),
                'getOutput' => $tagList,
            ]
        );
        $pregSplit = Test::func($this->namespace, 'preg_split', static function ($pattern, $text) {
            return $text === self::SPLIT_ERROR_STATE ? false : \preg_split($pattern, $text);
        });
        $expectedTags = $tagList !== self::SPLIT_ERROR_STATE
            ? array_filter(array_map('trim', preg_split("/[\n\r]/", $tagList)))
            : [];

        $results = $this->subject->listTags($this->workingDirectory);

        $process->verifyInvokedMultipleTimes('mustRun', 2);
        $process->verifyInvokedMultipleTimes('getOutput', 1);
        $pregSplit->verifyInvokedMultipleTimes(1);

        $this->assertIsArray($results);
        $this->assertSame($expectedTags, $results);
    }

    /**
     * @covers ::pull
     * @small
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     *
     * @throws Exception
     */
    public function testPull(): void
    {
        $returnedProcess = $this->prophesize(Process::class);
        $process = Test::double(Process::class, [
            'mustRun' => $returnedProcess->reveal(),
        ]);

        $this->subject->pull($this->workingDirectory);

        $process->verifyInvokedMultipleTimes('mustRun', 1);

        $this->assertTrue(true);
    }

    public function tagListProvider(): array
    {
        return [
            'simple tags' => ["1.0\n2.0.3\r2.9\n3.0\n\r4.0"],
            'precise tags' => ["1.1.0-beta\n2.0.0-alpha.2\r"],
            'spaces' => ['\n\r\r\n'],
            'no tags' => [''],
            'split error' => [self::SPLIT_ERROR_STATE],
        ];
    }
}
