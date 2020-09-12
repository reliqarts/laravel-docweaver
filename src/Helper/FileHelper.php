<?php

/**
 * @noinspection PhpTooManyParametersInspection
 */

declare(strict_types=1);

namespace ReliqArts\Docweaver\Helper;

use ReliqArts\Docweaver\Contract\FileHelper as FileHelperContract;

final class FileHelper implements FileHelperContract
{
    public function realPath(string $path)
    {
        return realpath($path);
    }

    public function getFileContents(string $filename, bool $useIncludePath = false)
    {
        return file_get_contents($filename, $useIncludePath);
    }
}
