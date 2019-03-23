<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Services;

use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;
use ReliqArts\Docweaver\Contracts\Filesystem as FilesystemContract;

final class Filesystem extends IlluminateFilesystem implements FilesystemContract
{
    /**
     * @param string $directory
     * @param bool   $preserve
     *
     * @return bool
     */
    public function deleteDirectory($directory, $preserve = false)
    {
        if (!$this->isDirectory($directory)) {
            return false;
        }

        /**
         * @var string Pattern for finding all contained files/directories
         *             inclusive of hidden directories such as `.git`.
         *
         * @see https://stackoverflow.com/a/49031383/3466460 Pattern Source
         */
        $pattern = sprintf('%s/{*,.[!.]*,..?*}', $directory);
        $files = glob($pattern, GLOB_BRACE);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDirectory($file);

                continue;
            }
            chmod($file, 0777);
            unlink($file);
        }

        return $preserve ?: rmdir($directory);
    }
}
