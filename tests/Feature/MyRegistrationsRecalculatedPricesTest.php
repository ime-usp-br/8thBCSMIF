<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyRegistrationsRecalculatedPricesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Event $mainEvent;

    protected Event $workshopEvent;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create(['email_verified_at' => now()]);

        // Create events
        $this->mainEvent = Event::factory()->create([
            'code' => 'BCSMIF2025',
            'name' => 'Main Conference',
            'is_main_conference' => true,
        ]);

        $this->workshopEvent = Event::factory()->create([
            'code' => 'WORKSHOP1',
            'name' => 'Workshop 1',
            'is_main_conference' => false,
        ]);

        // Create fees - workshop has discount when attending main conference
        Fee::factory()->create([
            'event_code' => 'BCSMIF2025',
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 100.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        Fee::factory()->create([
            'event_code' => 'WORKSHOP1',
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 50.00, // Full price
            'is_discount_for_main_event_participant' => false,
        ]);

        Fee::factory()->create([
            'event_code' => 'WORKSHOP1',
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 25.00, // Discounted price
            'is_discount_for_main_event_participant' => true,
        ]);

        // Create registration
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'calculated_fee' => 50.00,
            'registration_category_snapshot' => 'grad_student',
            'payment_status' => 'paid_br',
            'participation_format' => 'in-person',
        ]);
    }

    public function test_workshop_paid_full_price_then_added_main_conference_shows_discount_retroactively()
    {
        // Step 1: User initially paid for workshop only at full price
        $workshopPayment = Payment::factory()->paidBr()->create([
            'user_id' => $this->user->id,
            'total_amount' => 50.00,
        ]);

        $workshopPayment->events()->attach('WORKSHOP1', [
            'individual_price' => 50.00, // Full price paid initially
            'registration_id' => $this->user->registration->id,
        ]);

        $this->user->registration->events()->attach('WORKSHOP1', [
            'price_at_registration' => 50.00,
        ]);

        // Step 2: User later paid for main conference
        $mainPayment = Payment::factory()->paidBr()->create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
        ]);

        $mainPayment->events()->attach('BCSMIF2025', [
            'individual_price' => 100.00,
            'registration_id' => $this->user->registration->id,
        ]);

        $this->user->registration->events()->attach('BCSMIF2025', [
            'price_at_registration' => 100.00,
        ]);

        // Test the my-registrations page
        $response = $this->actingAs($this->user)
            ->get(route('registrations.my'));

        $response->assertStatus(200);

        // Debug: let's see what's actually in the response
        $content = $response->getContent();

        // Check the core functionality works
        $response->assertSee('R$ 150,00'); // Historical total (50 + 100)
        $response->assertSee('R$ 125,00'); // Recalculated total (25 + 100) with discount
        $response->assertSee('With current discounts'); // Shows discount information
        $response->assertSee('Fully Paid'); // Shows as fully paid
    }

    public function test_my_registrations_shows_recalculated_event_prices()
    {
        // Setup same scenario as above
        $workshopPayment = Payment::factory()->paidBr()->create([
            'user_id' => $this->user->id,
            'total_amount' => 50.00,
        ]);

        $workshopPayment->events()->attach('WORKSHOP1', [
            'individual_price' => 50.00,
            'registration_id' => $this->user->registration->id,
        ]);

        $this->user->registration->events()->attach('WORKSHOP1', [
            'price_at_registration' => 50.00,
        ]);

        $mainPayment = Payment::factory()->paidBr()->create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
        ]);

        $mainPayment->events()->attach('BCSMIF2025', [
            'individual_price' => 100.00,
            'registration_id' => $this->user->registration->id,
        ]);

        $this->user->registration->events()->attach('BCSMIF2025', [
            'price_at_registration' => 100.00,
        ]);

        // Test the page response for now since it's a Volt component
        $response = $this->actingAs($this->user)
            ->get(route('registrations.my'));

        $response->assertStatus(200);

        // Should see both historical total and recalculated total
        $response->assertSee('R$ 150,00'); // Historical total
        $response->assertSee('R$ 125,00'); // Recalculated total with discount
        $response->assertSee('With current discounts'); // Shows discount information
    }

    public function test_shows_basic_information_about_discount_scenario()
    {
        // Same setup as first test
        $workshopPayment = Payment::factory()->paidBr()->create([
            'user_id' => $this->user->id,
            'total_amount' => 50.00,
        ]);

        $workshopPayment->events()->attach('WORKSHOP1', [
            'individual_price' => 50.00,
            'registration_id' => $this->user->registration->id,
        ]);

        $this->user->registration->events()->attach('WORKSHOP1', [
            'price_at_registration' => 50.00,
        ]);

        $mainPayment = Payment::factory()->paidBr()->create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
        ]);

        $mainPayment->events()->attach('BCSMIF2025', [
            'individual_price' => 100.00,
            'registration_id' => $this->user->registration->id,
        ]);

        $this->user->registration->events()->attach('BCSMIF2025', [
            'price_at_registration' => 100.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('registrations.my'));

        $response->assertStatus(200);

        // Basic check that the page loads and shows discount information
        $response->assertSee('R$ 150,00'); // Total paid
        $response->assertSee('R$ 125,00'); // Recalculated total
        $response->assertSee('Fully Paid'); // Status

        // This verifies our functionality is working - user paid R$ 150 but
        // current value with discounts is R$ 125, and they are marked as fully paid
        $this->assertTrue(true, 'Discount calculation and display is working correctly');
    }
}
