<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Feature;

use ReliQArts\Docweaver\Contracts\ConfigProvider;
use ReliQArts\Docweaver\Contracts\Filesystem;
use ReliQArts\Docweaver\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = resolve(Filesystem::class);
        $this->configProvider = resolve(ConfigProvider::class);
        $group = 'feature';

        $this->setGroups(array_merge($this->getGroups(), [$group]));
    }
}
