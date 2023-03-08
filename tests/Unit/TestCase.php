<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Unit;

use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\PhpUnit\ProphecyTrait;
use ReliqArts\Docweaver\Contract\ConfigProvider;
use ReliqArts\Docweaver\Contract\Filesystem;
use ReliqArts\Docweaver\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use ProphecyTrait;

    /**
     * @throws DoubleException|InterfaceNotFoundException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->configProvider = $this->prophesize(ConfigProvider::class);
        $group = 'unit';

        $this->setGroups(array_merge($this->getGroups(), [$group]));
    }
}
