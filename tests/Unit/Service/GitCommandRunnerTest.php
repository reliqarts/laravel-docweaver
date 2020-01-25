<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service;

use AspectMock\Test;
use Exception;
use Prophecy\Prophecy\ObjectProphecy;
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
    private const PROCESS_METHOD_MUST_RUN = 'mustRun';
    private const PROCESS_METHOD_GET_OUTPUT = 'getOutput';

    /**
     * @var VCSCommandRunner
     */
    private VCSCommandRunner $subject;

    /**
     * @var ObjectProphecy|Process
     */
    private ObjectProphecy $returnedProcess;

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
        $this->returnedProcess = $this->prophesize(Process::class);
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
        $process = Test::double(Process::class, [self::PROCESS_METHOD_MUST_RUN => $this->returnedProcess->reveal()]);

        $this->subject->clone($source, $branch, $this->workingDirectory);

        $process->verifyInvokedMultipleTimes(self::PROCESS_METHOD_MUST_RUN, 1);

        $this->assertTrue(true);
    }

    /**
     * @covers ::fetch
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
        $process = Test::double(
            Process::class,
            [
                self::PROCESS_METHOD_MUST_RUN => $this->returnedProcess->reveal(),
                self::PROCESS_METHOD_GET_OUTPUT => $tagList,
            ]
        );
        $pregSplit = Test::func($this->namespace, 'preg_split', static function ($pattern, $text) {
            return $text === self::SPLIT_ERROR_STATE ? false : \preg_split($pattern, $text);
        });
        $expectedTags = $tagList !== self::SPLIT_ERROR_STATE
            ? array_filter(array_map('trim', preg_split("/[\n\r]/", $tagList)))
            : [];

        $results = $this->subject->listTags($this->workingDirectory);

        $process->verifyInvokedMultipleTimes(self::PROCESS_METHOD_MUST_RUN, 2);
        $process->verifyInvokedMultipleTimes(self::PROCESS_METHOD_GET_OUTPUT, 1);
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
        $process = Test::double(Process::class, [
            self::PROCESS_METHOD_MUST_RUN => $this->returnedProcess->reveal(),
        ]);

        $this->subject->pull($this->workingDirectory);

        $process->verifyInvokedMultipleTimes(self::PROCESS_METHOD_MUST_RUN, 1);

        $this->assertTrue(true);
    }

    /**
     * @covers ::getRemoteUrl
     * @small
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     *
     * @throws Exception
     */
    public function testGetRemoteUrl(): void
    {
        $remoteUrl = 'url://remote-url';
        $process = Test::double(Process::class, [
            self::PROCESS_METHOD_MUST_RUN => $this->returnedProcess->reveal(),
            self::PROCESS_METHOD_GET_OUTPUT => $remoteUrl,
        ]);

        $result = $this->subject->getRemoteUrl($this->workingDirectory);

        $process->verifyInvokedMultipleTimes(self::PROCESS_METHOD_MUST_RUN, 1);
        $process->verifyInvokedMultipleTimes(self::PROCESS_METHOD_GET_OUTPUT, 1);

        $this->assertSame($remoteUrl, $result);
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
