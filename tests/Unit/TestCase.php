<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit;

use ReliQArts\Docweaver\Contracts\ConfigProvider;
use ReliQArts\Docweaver\Contracts\Filesystem;
use ReliQArts\Docweaver\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->configProvider = $this->prophesize(ConfigProvider::class);
        $group = 'unit';

        $this->setGroups(array_merge($this->getGroups(), [$group]));
    }
}
