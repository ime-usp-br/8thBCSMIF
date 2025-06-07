<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    /**
     * Test that the home page loads successfully.
     */
    public function test_home_page_returns_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test that the home page contains 8th BCSMIF title and key content.
     */
    public function test_home_page_contains_bcsmif_description(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('8th BCSMIF');
        $response->assertSee('Brazilian Conference on Statistical Modeling in Insurance and Finance');
        $response->assertSee('Institute of Mathematics and Statistics of the University of São Paulo');
        $response->assertSee('September 28 to October 3, 2025');
        $response->assertSee('Maresias Beach Hotel');
    }

    /**
     * Test that the home page contains satellite workshops information.
     */
    public function test_home_page_contains_satellite_workshops(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Satellite Workshops');
        $response->assertSee('Risk Analysis and Applications');
        $response->assertSee('Dependence Analysis');
    }

    /**
     * Test that the home page has proper title.
     */
    public function test_home_page_has_proper_title(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<title>8th BCSMIF - Brazilian Conference on Statistical Modeling in Insurance and Finance</title>', false);
    }
}
