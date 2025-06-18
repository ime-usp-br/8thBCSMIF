<?php

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\AdditionalRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdditionalRegistrationService $service;

    protected User $user;

    protected Event $mainEvent;

    protected Event $workshopEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AdditionalRegistrationService::class);

        // Create test user with existing registration
        $this->user = User::factory()->create();

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

        // Create fees
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
            'price' => 50.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        Fee::factory()->create([
            'event_code' => 'WORKSHOP1',
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 25.00,
            'is_discount_for_main_event_participant' => true,
        ]);

        // Create existing registration
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'calculated_fee' => 100.00,
            'registration_category_snapshot' => 'grad_student',
            'payment_status' => 'paid_br',
        ]);

        // Create existing payment for main event
        $payment = Payment::factory()->paidBr()->create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
        ]);

        // Associate main event with payment and registration
        $payment->events()->attach('BCSMIF2025', [
            'individual_price' => 100.00,
            'registration_id' => $registration->id,
        ]);

        $registration->events()->attach('BCSMIF2025', [
            'price_at_registration' => 100.00,
        ]);
    }

    public function test_calculate_additional_events_fees_with_discount()
    {
        $result = $this->service->calculateAdditionalEventsFees(
            $this->user,
            ['WORKSHOP1'],
            'grad_student',
            'in-person'
        );

        $this->assertTrue($result['can_register']);
        $this->assertEquals(25.00, $result['total_new_fee']); // Discounted price
        $this->assertEquals(25.00, $result['difference_to_pay']);
        $this->assertCount(1, $result['details']);
        $this->assertEquals('WORKSHOP1', $result['details'][0]['event_code']);
        $this->assertEquals(25.00, $result['details'][0]['calculated_price']);
    }

    public function test_calculate_additional_events_fees_without_main_conference()
    {
        // Remove main conference from existing payments
        $this->user->payments()->first()->events()->detach();
        $this->user->registration->events()->detach();

        $result = $this->service->calculateAdditionalEventsFees(
            $this->user,
            ['WORKSHOP1'],
            'grad_student',
            'in-person'
        );

        $this->assertTrue($result['can_register']);
        $this->assertEquals(50.00, $result['total_new_fee']); // Full price
        $this->assertEquals(50.00, $result['difference_to_pay']);
    }

    public function test_calculate_additional_events_fees_already_paid()
    {
        // Add workshop to existing payment
        $payment = $this->user->payments()->first();
        $payment->events()->attach('WORKSHOP1', [
            'individual_price' => 25.00,
            'registration_id' => $this->user->registration->id,
        ]);

        $result = $this->service->calculateAdditionalEventsFees(
            $this->user,
            ['WORKSHOP1'],
            'grad_student',
            'in-person'
        );

        $this->assertFalse($result['can_register']);
        $this->assertEquals(0.0, $result['total_new_fee']);
        $this->assertEquals(0.0, $result['difference_to_pay']);
        $this->assertStringContainsString('already paid', $result['message']);
    }

    public function test_create_additional_registration_with_payment()
    {
        $result = $this->service->createAdditionalRegistration(
            $this->user,
            ['WORKSHOP1'],
            'grad_student',
            'in-person',
            'bank_transfer'
        );

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Payment::class, $result['payment']);
        $this->assertEquals(25.00, $result['payment']->total_amount);
        $this->assertEquals('pending_payment', $result['payment']->payment_status);

        // Check that workshop was added to registration
        $this->assertTrue($this->user->registration->events()->where('code', 'WORKSHOP1')->exists());

        // Check that payment-event relationship exists
        $this->assertTrue($result['payment']->events()->where('code', 'WORKSHOP1')->exists());
    }

    public function test_create_additional_registration_free_event()
    {
        // Create a free workshop
        $freeWorkshop = Event::factory()->create([
            'code' => 'FREE_WORKSHOP',
            'name' => 'Free Workshop',
            'is_main_conference' => false,
        ]);

        Fee::factory()->create([
            'event_code' => 'FREE_WORKSHOP',
            'participant_category' => 'grad_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 0.00,
            'is_discount_for_main_event_participant' => false,
        ]);

        $result = $this->service->createAdditionalRegistration(
            $this->user,
            ['FREE_WORKSHOP'],
            'grad_student',
            'in-person'
        );

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Payment::class, $result['payment']);
        $this->assertEquals(0.00, $result['payment']->total_amount);
        $this->assertEquals('paid_br', $result['payment']->payment_status); // Auto-marked as paid
    }

    public function test_get_user_accessible_events()
    {
        $accessibleEvents = $this->service->getUserAccessibleEvents($this->user);

        $this->assertCount(1, $accessibleEvents);
        $this->assertEquals('BCSMIF2025', $accessibleEvents[0]['code']);
    }

    public function test_can_user_register_for_events_success()
    {
        $result = $this->service->canUserRegisterForEvents($this->user, ['WORKSHOP1']);

        $this->assertTrue($result['can_register']);
        $this->assertEquals(__('Can register for all selected events'), $result['message']);
    }

    public function test_can_user_register_for_events_already_registered()
    {
        $result = $this->service->canUserRegisterForEvents($this->user, ['BCSMIF2025']);

        $this->assertFalse($result['can_register']);
        $this->assertEquals(__('Some events are already paid and cannot be modified. Paid events are non-refundable.'), $result['message']);
        $this->assertContains('BCSMIF2025', $result['blocked_events']);
    }

    public function test_calculate_fees_without_existing_registration()
    {
        $userWithoutRegistration = User::factory()->create();

        $result = $this->service->calculateAdditionalEventsFees(
            $userWithoutRegistration,
            ['WORKSHOP1'],
            'grad_student',
            'in-person'
        );

        $this->assertFalse($result['can_register']);
        $this->assertEquals(__('No existing registration found'), $result['message']);
    }
}
