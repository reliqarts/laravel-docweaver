<?php

namespace ReliQArts\Docweaver\Traits;

/**
 * A handler of files.
 */
trait FileHandler
{
    /**
     * The filesystem implementation.
     *
     * @var Filesystem
     */
    protected $files;
    
    /**
     * Format path correctly based on OS.
     * i.e. using DIRECTORY_SEPARATOR.
     *
     * @param string $path
     * @return string
     */
    private function dirPath($path)
    {
        return realpath($path);
    }
}
