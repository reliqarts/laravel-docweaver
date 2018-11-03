<?php

namespace ReliQArts\Docweaver\Tests\Feature;

use DocweaverConfig;
use ReliQArts\Docweaver\Tests\TestCase as TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AvailabilityTest extends TestCase
{
    /**
     * Ensure views have required data.
     */
    public function testViewData()
    {
        $routeConfig = DocweaverConfig::getRouteConfig();
        $docIndex = $routeConfig['prefix'];

        $this->visit($docIndex)
            ->assertViewHas('viewTemplateInfo');

        $this->visit($docIndex)
            ->assertViewHas('viewTemplateInfo');
    }

    /**
     * Ensure project(s) are visible and accessible via UI.
     */
    public function testProjectAvailability()
    {
        $routeConfig = DocweaverConfig::getRouteConfig();
        $docIndex = $routeConfig['prefix'];

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
