<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit;

use ReliqArts\Docweaver\Contracts\ConfigProvider;
use ReliqArts\Docweaver\Contracts\Filesystem;
use ReliqArts\Docweaver\Tests\TestCase as BaseTestCase;

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
