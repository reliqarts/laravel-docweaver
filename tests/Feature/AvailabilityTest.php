<?php

namespace ReliQArts\Docweaver\Tests\Feature;

use DocweaverHelper;
use ReliQArts\Docweaver\Tests\TestCase as TestCase;

class AvailabilityTest extends TestCase
{
    /**
     * Ensure views have required data.
     */
    public function testViewData()
    {
        $routeConfig = DocweaverHelper::getRouteConfig();
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
        $routeConfig = DocweaverHelper::getRouteConfig();
        $docIndex = $routeConfig['prefix'];

        $this->visit($docIndex)
            ->see('Sandy')
            ->see('Project Sandy the great.')
            ->see('4.7')
            ->click('Sandy')
            ->dontSee('documentation')
            ->see('elements are important')
            ->see('Haha! It\'s pre-installed fam.');
    }
}
