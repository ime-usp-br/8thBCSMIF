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
        $response->assertSee(__('Registration Fees'));
        $response->assertSee(__('8th BCSMIF Conference'));
        $response->assertSee(__('Satellite Workshops'));
        $response->assertSee(__('Workshop (each one)'));
    }

    /**
     * Test that the fees page contains participant categories.
     */
    public function test_fees_page_contains_participant_categories(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee(__('Undergraduate Student'));
        $response->assertSee(__('Graduate Student'));
        $response->assertSee(__('Professor - ABE member'));
        $response->assertSee(__('Professor - ABE non-member / Professional'));
    }

    /**
     * Test that the fees page contains pricing information.
     */
    public function test_fees_page_contains_pricing_information(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee(__('Free'));
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
        $response->assertSee(__('Until 08/15/2025'));
        $response->assertSee(__('After 08/15/2025'));
        $response->assertSee(__('Online'));
    }

    /**
     * Test that the fees page has proper title.
     */
    public function test_fees_page_has_proper_title(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee(__('Registration Fees'), false);
    }

    /**
     * Test that the fees page contains important information and notes.
     */
    public function test_fees_page_contains_important_information(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee(__('Important Information'));
        $response->assertSee(__('Undergraduate students are exempt from fees for all events'));
        $response->assertSee(__('Graduate students are exempt from workshop fees'));
        $response->assertSee(__('Early bird registration deadline: August 15, 2025'));
        $response->assertSee(__('ABE membership status will be verified during registration'));
    }

    /**
     * Test that the fees page contains workshop discount information.
     */
    public function test_fees_page_contains_workshop_discount_information(): void
    {
        $response = $this->get('/fees');

        $response->assertStatus(200);
        $response->assertSee(__('Numbers in parentheses refer to discounts for 8th BCSMIF participants'));
        $response->assertSee('R$ 250');
        $response->assertSee('(R$ 100)*');
        $response->assertSee('R$ 700');
        $response->assertSee('(R$ 500)*');
    }
}
