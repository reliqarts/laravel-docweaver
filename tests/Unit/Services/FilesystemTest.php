<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit\Services;

use AspectMock\Test;
use ReliQArts\Docweaver\Services\Filesystem;
use ReliQArts\Docweaver\Tests\Unit\AspectMockedTestCase;

/**
 * Class FilesystemTest.
 *
 * @coversDefaultClass \ReliQArts\Docweaver\Services\Filesystem
 *
 * @internal
 */
final class FilesystemTest extends AspectMockedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->namespace = '\ReliQArts\Docweaver\Services';
        $this->parentNamespace = '\Illuminate\Filesystem';
        $this->filesystem = new Filesystem();
    }


    /**
     * @covers ::deleteDirectory
     * @small
     * @group aspectMock
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDeleteDirectoryWithAspectMock(): void
    {
        $directory = 'foo';
        $files = ['file 1', 'file 2', 'dir 1', 'dir 2', 'file 3'];
        $fileCount = count($files);
        $glob = Test::func($this->namespace, 'glob', function ($pattern) use ($directory, $files) {
            return stripos($pattern, sprintf('%s/', $directory)) !== false ? $files : [];
        });
        $isDir = Test::func($this->namespace, 'is_dir', function ($file) {
            return stripos($file, 'dir') !== false;
        });
        $parentIsDir = Test::func($this->parentNamespace, 'is_dir', true);
        $rmDir = Test::func($this->namespace, 'rmdir', true);
        $chmod = Test::func($this->namespace, 'chmod', true);
        $unlink = Test::func($this->namespace, 'unlink', true);
        $subdirectoryCount = 0;

        $result = $this->filesystem->deleteDirectory($directory);

        foreach ($files as $file) {
            if (stripos($file, 'dir') !== false) {
                ++$subdirectoryCount;
            }
        }

        $glob->verifyInvokedMultipleTimes($subdirectoryCount + 1);
        $isDir->verifyInvokedMultipleTimes($fileCount);
        $parentIsDir->verifyInvokedOnce([$directory]);
        $chmod->verifyInvokedMultipleTimes($fileCount - $subdirectoryCount);
        $unlink->verifyInvokedMultipleTimes($fileCount - $subdirectoryCount);
        $parentIsDir->verifyInvokedMultipleTimes($subdirectoryCount + 1);
        $rmDir->verifyInvokedMultipleTimes($subdirectoryCount + 1);
        $rmDir->verifyInvokedOnce([$directory]);

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }
}
