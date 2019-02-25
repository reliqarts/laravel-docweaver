<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Services;

use Monolog\Logger as BaseLogger;
use ReliQArts\Docweaver\Contracts\Logger as LoggerContract;

final class Logger extends BaseLogger implements LoggerContract
{
}
