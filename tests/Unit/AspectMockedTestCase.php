<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Tests\Unit;

use AspectMock\Test;

abstract class AspectMockedTestCase extends TestCase
{
    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $parentNamespace;

    protected function tearDown(): void
    {
        Test::clean();

        parent::tearDown();
    }
}
