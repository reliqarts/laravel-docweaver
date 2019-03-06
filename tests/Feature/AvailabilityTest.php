<?php

namespace ReliQArts\Docweaver\Tests\Feature;

use ReliQArts\Docweaver\Tests\TestCase as TestCase;

/**
 * @internal
 * @coversDefaultClass \ReliQArts\Docweaver\Http\Controllers\DocumentationController
 */
final class AvailabilityTest extends TestCase
{
    /**
     * Ensure views have required data.
     *
     * @covers ::index
     */
    public function testViewData()
    {
        $docIndex = $this->configProvider->getRoutePrefix();

        $this->visit($docIndex)
            ->assertViewHas('docweaverConfigProvider');
    }

    /**
     * Ensure project(s) are visible and accessible via UI.
     *
     * @covers ::productIndex
     * @covers ::show
     */
    public function testProjectAvailability()
    {
        $docIndex = $this->configProvider->getRoutePrefix();

        $this->visit($docIndex)
            ->see('Sandy')
            ->see('Project Sandy the great.')
            ->see('4.7')
            ->see('Oh my! Docs!')
            ->click('Sandy')
            ->dontSee('documentation')
            ->see('elements are important')
            ->see('Haha! It\'s pre-installed fam.');
    }
}
