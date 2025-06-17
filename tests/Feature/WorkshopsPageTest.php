<?php

namespace Tests\Feature;

use Tests\TestCase;

class WorkshopsPageTest extends TestCase
{
    /**
     * Test that the workshops page loads successfully.
     */
    public function test_workshops_page_returns_successful_response(): void
    {
        $response = $this->withoutMiddleware(['ensure.registration'])->get('/workshops');

        $response->assertStatus(200);
    }

    /**
     * Test that the workshops page contains workshop titles and key content.
     */
    public function test_workshops_page_contains_workshop_information(): void
    {
        $response = $this->withoutMiddleware(['ensure.registration'])->get('/workshops');

        $response->assertStatus(200);
        $response->assertSee('Satellite Workshops');
        $response->assertSee('Workshop on Risk Analysis and Applications (WRAA)');
        $response->assertSee('Workshop on Dependence Analysis (WDA)');
    }

    /**
     * Test that the workshops page contains dates and locations.
     */
    public function test_workshops_page_contains_dates_and_locations(): void
    {
        $response = $this->withoutMiddleware(['ensure.registration'])->get('/workshops');

        $response->assertStatus(200);
        $response->assertSee('September 24+25, 2025');
        $response->assertSee('September 26+27, 2025');
        $response->assertSee('At IME-USP, São Paulo');
        $response->assertSee('At IMECC-UNICAMP, Campinas');
    }

    /**
     * Test that the workshops page contains external links.
     */
    public function test_workshops_page_contains_external_links(): void
    {
        $response = $this->withoutMiddleware(['ensure.registration'])->get('/workshops');

        $response->assertStatus(200);
        $response->assertSee('https://sites.google.com/usp.br/raa/');
        $response->assertSee('https://sites.google.com/usp.br/wda-unicamp/');
    }

    /**
     * Test that the workshops page has proper title.
     */
    public function test_workshops_page_has_proper_title(): void
    {
        $response = $this->withoutMiddleware(['ensure.registration'])->get('/workshops');

        $response->assertStatus(200);
        $response->assertSee('Satellite Workshops', false);
        $response->assertSee('<title>', false);
    }

    /**
     * Test that the workshops page contains program features.
     */
    public function test_workshops_page_contains_program_features(): void
    {
        $response = $this->withoutMiddleware(['ensure.registration'])->get('/workshops');

        $response->assertStatus(200);
        $response->assertSee('Focus Areas');
        $response->assertSee('Risk modeling and quantification');
        $response->assertSee('Dependence structures and copulas');
        $response->assertSee('Official Website');
    }
}
