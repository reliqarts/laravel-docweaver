<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Tests\Feature;

/**
 * @coversDefaultClass \ReliqArts\Docweaver\Http\Controllers\DocumentationController
 *
 * @internal
 */
final class AvailabilityTest extends TestCase
{
    /**
     * Ensure views have required data.
     *
     * @covers ::__construct
     * @covers ::index
     * @small
     */
    public function testViewData(): void
    {
        $docIndex = $this->configProvider->getRoutePrefix();

        $this->visit($docIndex)
            ->assertViewHas('docweaverConfigProvider');
    }

    /**
     * Ensure project(s) are visible and accessible via UI.
     *
     * @covers ::__construct
     * @covers ::productIndex
     * @covers ::show
     * @small
     */
    public function testProjectAvailability(): void
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
