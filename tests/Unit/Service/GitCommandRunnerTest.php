<?php

/**
 * @noinspection PhpParamsInspection
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpStrictTypeCheckingInspection
 */

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service;

use Exception;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Docweaver\Contract\ProcessHelper;
use ReliqArts\Docweaver\Contract\VcsCommandRunner;
use ReliqArts\Docweaver\Service\GitCommandRunner;
use ReliqArts\Docweaver\Tests\Unit\TestCase;
use Symfony\Component\Process\Process;

/**
 * Class GitCommandRunnerTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Service\GitCommandRunner
 *
 * @internal
 */
final class GitCommandRunnerTest extends TestCase
{
    /**
     * @var ObjectProphecy|Process
     */
    private ObjectProphecy $returnedProcess;

    /**
     * @var ObjectProphecy|ProcessHelper
     */
    private ObjectProphecy $processHelper;

    private string $workingDirectory;

    private VcsCommandRunner $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->returnedProcess = $this->prophesize(Process::class);
        $this->processHelper = $this->prophesize(ProcessHelper::class);

        $this->processHelper->createProcess(Argument::cetera())
            ->willReturn($this->returnedProcess->reveal());

        $this->subject = new GitCommandRunner($this->processHelper->reveal());
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

        $this->processHelper->createProcess(
            Argument::that(
                static fn (array $argument) => in_array($source, $argument, true)
                    && in_array($branch, $argument, true)
            ),
            $this->workingDirectory
        )->willReturn($this->returnedProcess->reveal());

        $this->returnedProcess
            ->mustRun()
            ->shouldBeCalledTimes(1)
            ->willReturn($this->returnedProcess->reveal());

        $this->subject->clone($source, $branch, $this->workingDirectory);

        self::assertTrue(true);
    }

    /**
     * @covers ::fetch
     * @covers ::listTags
     * @dataProvider        tagListProvider
     * @small
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     *
     * @param mixed $tagList
     *
     * @throws Exception
     */
    public function testListTags($tagList): void
    {
        $this->returnedProcess
            ->mustRun()
            ->shouldBeCalledTimes(2)
            ->willReturn($this->returnedProcess->reveal());

        $this->returnedProcess
            ->getOutput()
            ->shouldBeCalledTimes(1)
            ->willReturn($tagList);

        $expectedTags = array_filter(array_map('trim', preg_split("/[\n\r]/", $tagList)));

        $results = $this->subject->listTags($this->workingDirectory);

        self::assertIsArray($results);
        self::assertSame($expectedTags, $results);
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
        $this->returnedProcess
            ->mustRun()
            ->shouldBeCalledTimes(1)
            ->willReturn($this->returnedProcess->reveal());

        $this->subject->pull($this->workingDirectory);

        self::assertTrue(true);
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

        $this->returnedProcess
            ->mustRun()
            ->shouldBeCalledTimes(1)
            ->willReturn($this->returnedProcess->reveal());
        $this->returnedProcess
            ->getOutput()
            ->shouldBeCalledTimes(1)
            ->willReturn($remoteUrl);

        $result = $this->subject->getRemoteUrl($this->workingDirectory);

        self::assertSame($remoteUrl, $result);
    }

    public function tagListProvider(): array
    {
        return [
            'simple tags' => ["1.0\n2.0.3\r2.9\n3.0\n\r4.0"],
            'precise tags' => ["1.1.0-beta\n2.0.0-alpha.2\r"],
            'spaces' => ['\n\r\r\n'],
            'no tags' => [''],
        ];
    }
}
