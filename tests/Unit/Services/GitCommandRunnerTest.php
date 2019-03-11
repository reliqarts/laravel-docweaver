<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit\Services;

use AspectMock\Test;
use Exception;
use ReliQArts\Docweaver\Contracts\VCSCommandRunner;
use ReliQArts\Docweaver\Services\GitCommandRunner;
use ReliQArts\Docweaver\Tests\Unit\AspectMockedTestCase;
use Symfony\Component\Process\Process;

/**
 * Class GitCommandRunnerTest.
 *
 * @coversDefaultClass \ReliQArts\Docweaver\Services\GitCommandRunner
 *
 * @internal
 */
final class GitCommandRunnerTest extends AspectMockedTestCase
{
    private const SPLIT_ERROR_STATE = 'error!';

    /**
     * @var VCSCommandRunner
     */
    private $subject;

    /**
     * @var string
     */
    private $workingDirectory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->namespace = '\ReliQArts\Docweaver\Services';
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
        $process = Test::double(Process::class, ['mustRun' => true]);

        $this->subject->clone($source, $branch, $this->workingDirectory);

        $process->verifyInvokedMultipleTimes('mustRun', 1);

        $this->assertTrue(true);
    }

    /**
     * @covers ::getTags
     * @dataProvider        tagListProvider
     * @small
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     *
     * @param string $tagList
     *
     * @throws Exception
     */
    public function testGetTags(string $tagList): void
    {
        $process = Test::double(Process::class, ['mustRun' => true, 'getOutput' => $tagList]);
        $pregSplit = Test::func($this->namespace, 'preg_split', function ($pattern, $text) {
            return $text === self::SPLIT_ERROR_STATE ? false : \preg_split($pattern, $text);
        });
        $expectedTags = $tagList !== self::SPLIT_ERROR_STATE
            ? array_filter(array_map('trim', preg_split("/[\n\r]/", $tagList)))
            : [];

        $results = $this->subject->getTags($this->workingDirectory);

        $process->verifyInvokedMultipleTimes('mustRun', 1);
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
        $process = Test::double(Process::class, ['mustRun' => true]);

        $this->subject->pull($this->workingDirectory);

        $process->verifyInvokedMultipleTimes('mustRun', 1);

        $this->assertTrue(true);
    }

    /**
     * @return array
     */
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
