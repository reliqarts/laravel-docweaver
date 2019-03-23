<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Feature;

use ReliqArts\Docweaver\Contracts\ConfigProvider;
use ReliqArts\Docweaver\Contracts\Filesystem;
use ReliqArts\Docweaver\Tests\TestCase as BaseTestCase;

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
