<?php

namespace ReliQArts\DocWeaver\Tests\Feature;

use DocWeaverHelper;
use ReliQArts\DocWeaver\Tests\TestCase as TestCase;

class AvailabilityTest extends TestCase
{
    /**
     * Ensure views have required data.
     */
    public function testViewData()
    {
        $routeConfig = DocWeaverHelper::getRouteConfig();
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
        $routeConfig = DocWeaverHelper::getRouteConfig();
        $docIndex = $routeConfig['prefix'];

        $this->visit($docIndex)
            ->see('Sand')
            ->see('Version: 4.7')
            ->click('Sand')
            ->dontSee('documentation')
            ->see('elements are important')
            ->see('Haha! It\'s pre-installed fam.');
    }
}
