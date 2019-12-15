<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Service;

use Monolog\Logger as BaseLogger;
use ReliqArts\Docweaver\Contract\Logger as LoggerContract;

final class Logger extends BaseLogger implements LoggerContract
{
}
