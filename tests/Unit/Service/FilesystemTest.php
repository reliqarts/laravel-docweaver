<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit\Service;

use AspectMock\Test;
use ReliqArts\Docweaver\Service\Filesystem;
use ReliqArts\Docweaver\Tests\Unit\AspectMockedTestCase;

/**
 * Class FilesystemTest.
 *
 * @coversDefaultClass \ReliqArts\Docweaver\Service\Filesystem
 *
 * @internal
 */
final class FilesystemTest extends AspectMockedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->namespace = '\ReliqArts\Docweaver\Service';
        $this->parentNamespace = '\Illuminate\Filesystem';
        $this->filesystem = new Filesystem();
    }

    /**
     * @covers ::deleteDirectory
     * @small
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testDeleteDirectory(): void
    {
        $directory = 'foo';
        $fakeDirectory = 'bar';
        $files = ['file 1', 'file 2', 'dir 1', 'dir 2', 'file 3'];
        $fileCount = count($files);
        $glob = Test::func($this->namespace, 'glob', function ($pattern) use ($directory, $files) {
            return stripos($pattern, sprintf('%s/', $directory)) !== false ? $files : [];
        });
        $isDir = Test::func($this->namespace, 'is_dir', function ($file) {
            return stripos($file, 'dir') !== false;
        });
        $parentIsDir = Test::func($this->parentNamespace, 'is_dir', function ($file) use ($fakeDirectory) {
            return $file !== $fakeDirectory;
        });
        $rmDir = Test::func($this->namespace, 'rmdir', true);
        $chmod = Test::func($this->namespace, 'chmod', true);
        $unlink = Test::func($this->namespace, 'unlink', true);
        $subdirectoryCount = 0;

        $result1 = $this->filesystem->deleteDirectory($directory);
        $result2 = $this->filesystem->deleteDirectory($fakeDirectory);

        foreach ($files as $file) {
            if (stripos($file, 'dir') !== false) {
                ++$subdirectoryCount;
            }
        }

        $glob->verifyInvokedMultipleTimes($subdirectoryCount + 1);
        $isDir->verifyInvokedMultipleTimes($fileCount);
        $parentIsDir->verifyInvokedOnce([$directory]);
        $parentIsDir->verifyInvokedOnce([$fakeDirectory]);
        $chmod->verifyInvokedMultipleTimes($fileCount - $subdirectoryCount);
        $unlink->verifyInvokedMultipleTimes($fileCount - $subdirectoryCount);
        $parentIsDir->verifyInvokedMultipleTimes($subdirectoryCount + 2);
        $rmDir->verifyInvokedMultipleTimes($subdirectoryCount + 1);
        $rmDir->verifyInvokedOnce([$directory]);

        $this->assertIsBool($result1);
        $this->assertIsBool($result2);
        $this->assertTrue($result1);
        $this->assertFalse($result2);
    }
}
