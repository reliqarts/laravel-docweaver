<?php

/**
 * @noinspection PhpTooManyParametersInspection
 */

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract;

interface FileHelper
{
    /**
     * Returns absolute path name
     *
     * @return string|false the canonicalize-d absolute pathname on success. False on failure
     * @see realpath()
     */
    public function realPath(string $path);

    /**
     * @return string|false The function returns the read data or false on failure.
     * @see file_get_contents()
     */
    public function getFileContents(string $filename, bool $useIncludePath = false);
}
