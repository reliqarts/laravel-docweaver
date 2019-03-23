<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Services;

use Monolog\Logger as BaseLogger;
use ReliqArts\Docweaver\Contracts\Logger as LoggerContract;

final class Logger extends BaseLogger implements LoggerContract
{
}
