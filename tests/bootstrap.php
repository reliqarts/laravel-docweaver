<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php'; // composer autoload

$kernel = \AspectMock\Kernel::getInstance();
$kernel->init([
    'debug' => true,
    'cacheDir' => __DIR__ . '/../build/cache',
    'includePaths' => [__DIR__ . '/../src'],
    'excludePaths' => [__DIR__],
]);
