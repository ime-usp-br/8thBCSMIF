<?php

namespace Tests\Feature;

use Tests\TestCase;

class FeesPageTest extends TestCase
{
    /**
     * Test that the fees page loads successfully.
     */
    public function test_fees_page_returns_successful_response(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
    }

    /**
     * Test that the fees page contains fee structure titles and key content.
     */
    public function test_fees_page_contains_fee_information(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee('Registration Fees');
        $response->assertSee('8th BCSMIF Conference and Satellite Workshops');
        $response->assertSee('8th BCSMIF Conference');
        $response->assertSee('Satellite Workshops (each one)');
    }

    /**
     * Test that the fees page contains participant categories.
     */
    public function test_fees_page_contains_participant_categories(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee('Undergraduate Student');
        $response->assertSee('Graduate Student');
        $response->assertSee('Professor - ABE member');
        $response->assertSee('Professor - ABE non-member / Professional');
    }

    /**
     * Test that the fees page contains pricing information.
     */
    public function test_fees_page_contains_pricing_information(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee('Free');
        $response->assertSee('R$ 600');
        $response->assertSee('R$ 700');
        $response->assertSee('R$ 200');
        $response->assertSee('R$ 1,200');
        $response->assertSee('R$ 1,400');
        $response->assertSee('R$ 400');
        $response->assertSee('R$ 1,600');
        $response->assertSee('R$ 2,000');
        $response->assertSee('R$ 800');
    }

    /**
     * Test that the fees page contains registration periods.
     */
    public function test_fees_page_contains_registration_periods(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee('Until 08/15/2025');
        $response->assertSee('After 08/15/2025');
        $response->assertSee('Online');
    }

    /**
     * Test that the fees page has proper title.
     */
    public function test_fees_page_has_proper_title(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee('<title>Registration Fees - 8th BCSMIF</title>', false);
    }

    /**
     * Test that the fees page contains important information and notes.
     */
    public function test_fees_page_contains_important_information(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee('Important Information');
        $response->assertSee('Undergraduate students attend all events free of charge');
        $response->assertSee('Graduate students attend satellite workshops free of charge');
        $response->assertSee('Registration deadline for early bird prices: August 15, 2025');
        $response->assertSee('ABE (Brazilian Statistical Association) membership provides reduced fees');
    }

    /**
     * Test that the fees page contains workshop discount information.
     */
    public function test_fees_page_contains_workshop_discount_information(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee('Discounted prices in parentheses apply to 8th BCSMIF main conference participants');
        $response->assertSee('R$ 250');
        $response->assertSee('(R$ 100*)');
        $response->assertSee('R$ 700');
        $response->assertSee('(R$ 500*)');
    }
}
