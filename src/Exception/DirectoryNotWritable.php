<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Exception;

final class DirectoryNotWritable extends InvalidDirectory
{
    protected const MESSAGE_TEMPLATE = 'Directory `%s` is not writable.';
}
