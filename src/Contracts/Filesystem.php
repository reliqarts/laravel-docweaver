<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Contracts;

interface Filesystem
{
    /**
     * Determine if a file or directory exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists($path);

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @param bool   $lock
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return string
     */
    public function get($path, $lock = false);

    /**
     * Get contents of a file with shared access.
     *
     * @param string $path
     *
     * @return string
     */
    public function sharedGet($path);

    /**
     * Get the returned value of a file.
     *
     * @param string $path
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return mixed
     */
    public function getRequire($path);

    /**
     * Require the given file once.
     *
     * @param string $file
     *
     * @return mixed
     */
    public function requireOnce($file);

    /**
     * Get the MD5 hash of the file at the given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function hash($path);

    /**
     * Write the contents of a file.
     *
     * @param string $path
     * @param string $contents
     * @param bool   $lock
     *
     * @return int
     */
    public function put($path, $contents, $lock = false);

    /**
     * Prepend to a file.
     *
     * @param string $path
     * @param string $data
     *
     * @return int
     */
    public function prepend($path, $data);

    /**
     * Append to a file.
     *
     * @param string $path
     * @param string $data
     *
     * @return int
     */
    public function append($path, $data);

    /**
     * Get or set UNIX mode of a file or directory.
     *
     * @param string $path
     * @param int    $mode
     *
     * @return mixed
     */
    public function chmod($path, $mode = null);

    /**
     * Delete the file at a given path.
     *
     * @param array|string $paths
     *
     * @return bool
     */
    public function delete($paths);

    /**
     * Move a file to a new location.
     *
     * @param string $path
     * @param string $target
     *
     * @return bool
     */
    public function move($path, $target);

    /**
     * Copy a file to a new location.
     *
     * @param string $path
     * @param string $target
     *
     * @return bool
     */
    public function copy($path, $target);

    /**
     * Create a hard link to the target file or directory.
     *
     * @param string $target
     * @param string $link
     */
    public function link($target, $link);

    /**
     * Extract the file name from a file path.
     *
     * @param string $path
     *
     * @return string
     */
    public function name($path);

    /**
     * Extract the trailing name component from a file path.
     *
     * @param string $path
     *
     * @return string
     */
    public function basename($path);

    /**
     * Extract the parent directory from a file path.
     *
     * @param string $path
     *
     * @return string
     */
    public function dirname($path);

    /**
     * Extract the file extension from a file path.
     *
     * @param string $path
     *
     * @return string
     */
    public function extension($path);

    /**
     * Get the file type of a given file.
     *
     * @param string $path
     *
     * @return string
     */
    public function type($path);

    /**
     * Get the mime-type of a given file.
     *
     * @param string $path
     *
     * @return false|string
     */
    public function mimeType($path);

    /**
     * Get the file size of a given file.
     *
     * @param string $path
     *
     * @return int
     */
    public function size($path);

    /**
     * Get the file's last modification time.
     *
     * @param string $path
     *
     * @return int
     */
    public function lastModified($path);

    /**
     * Determine if the given path is a directory.
     *
     * @param string $directory
     *
     * @return bool
     */
    public function isDirectory($directory);

    /**
     * Determine if the given path is readable.
     *
     * @param string $path
     *
     * @return bool
     */
    public function isReadable($path);

    /**
     * Determine if the given path is writable.
     *
     * @param string $path
     *
     * @return bool
     */
    public function isWritable($path);

    /**
     * Determine if the given path is a file.
     *
     * @param string $file
     *
     * @return bool
     */
    public function isFile($file);

    /**
     * Find path names matching a given pattern.
     *
     * @param string $pattern
     * @param int    $flags
     *
     * @return array
     */
    public function glob($pattern, $flags = 0);

    /**
     * Get an array of all files in a directory.
     *
     * @param string $directory
     * @param bool   $hidden
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public function files($directory, $hidden = false);

    /**
     * Get all of the files from the given directory (recursive).
     *
     * @param string $directory
     * @param bool   $hidden
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public function allFiles($directory, $hidden = false);

    /**
     * Get all of the directories within a given directory.
     *
     * @param string $directory
     *
     * @return array
     */
    public function directories($directory);

    /** @noinspection PhpTooManyParametersInspection */

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int    $mode
     * @param bool   $recursive
     * @param bool   $force
     *
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false);

    /**
     * Move a directory.
     *
     * @param string $from
     * @param string $to
     * @param bool   $overwrite
     *
     * @return bool
     */
    public function moveDirectory($from, $to, $overwrite = false);

    /**
     * Copy a directory from one location to another.
     *
     * @param string $directory
     * @param string $destination
     * @param int    $options
     *
     * @return bool
     */
    public function copyDirectory($directory, $destination, $options = null);

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param string $directory
     * @param bool   $preserve
     *
     * @return bool
     */
    public function deleteDirectory($directory, $preserve = false);

    /**
     * Remove all of the directories within a given directory.
     *
     * @param string $directory
     *
     * @return bool
     */
    public function deleteDirectories($directory);

    /**
     * Empty the specified directory of all files and folders.
     *
     * @param string $directory
     *
     * @return bool
     */
    public function cleanDirectory($directory);
}
