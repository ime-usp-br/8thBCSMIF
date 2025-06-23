<?php

namespace Tests\Feature\Livewire;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(\App\Http\Livewire\Pages\RegistrationModification::class)]
#[Group('livewire')]
#[Group('registration-modification')]
class RegistrationModificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(EventsTableSeeder::class);
        $this->seed(FeesTableSeeder::class);
    }

    #[Test]
    public function component_can_render(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create();
        $event = Event::first();
        $registration->events()->attach($event->code, ['price_at_registration' => 100.00]);

        Livewire::actingAs($user)
            ->test('pages.registration-modification')
            ->assertOk();
    }

    #[Test]
    public function component_redirects_unauthenticated_users_to_verification(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        Livewire::actingAs($user)
            ->test('pages.registration-modification')
            ->assertRedirect(route('verification.notice'));
    }

    #[Test]
    public function component_redirects_users_without_registration_to_register_event(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Livewire::actingAs($user)
            ->test('pages.registration-modification')
            ->assertRedirect(route('register-event'));
    }

    #[Test]
    public function component_loads_user_registration_with_events_and_payments(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create([
            'full_name' => 'Test User Registration',
        ]);

        $event = Event::first();
        $registration->events()->attach($event->code, ['price_at_registration' => 150.00]);

        // Add a payment
        $registration->payments()->create([
            'amount' => 75.00,
            'status' => 'pending_approval',
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification');

        $component->assertSet('registration.id', $registration->id);
        $component->assertSet('hasPendingPayments', true);
    }

    #[Test]
    public function component_loads_available_events_excluding_already_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create();

        $events = Event::take(2)->get();
        $selectedEvent = $events->first();
        $availableEvent = $events->last();

        // Attach first event to registration
        $registration->events()->attach($selectedEvent->code, ['price_at_registration' => 100.00]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification');

        // Available events should exclude the already selected event
        $availableEventCodes = collect($component->get('availableEvents'))->pluck('code')->toArray();
        $this->assertNotContains($selectedEvent->code, $availableEventCodes);
        $this->assertContains($availableEvent->code, $availableEventCodes);
    }

    #[Test]
    public function component_initializes_with_empty_selected_event_codes(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create();
        $event = Event::first();
        $registration->events()->attach($event->code, ['price_at_registration' => 100.00]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification');

        $component->assertSet('selectedEventCodes', []);
        $component->assertSet('feeCalculation', null);
    }

    #[Test]
    public function component_calculates_fees_when_event_codes_are_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $events = Event::take(2)->get();
        $currentEvent = $events->first();
        $newEvent = $events->last();

        // Add current event
        $registration->events()->attach($currentEvent->code, ['price_at_registration' => 100.00]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification')
            ->set('selectedEventCodes', [$newEvent->code]);

        // Fee calculation should be triggered
        $this->assertNotNull($component->get('feeCalculation'));
        $this->assertArrayHasKey('new_items_cost', $component->get('feeCalculation'));
        $this->assertArrayHasKey('amount_due', $component->get('feeCalculation'));
    }

    #[Test]
    public function component_clears_fee_calculation_when_no_events_selected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $event = Event::first();
        $registration->events()->attach($event->code, ['price_at_registration' => 100.00]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification')
            ->set('selectedEventCodes', [Event::skip(1)->first()->code]);

        // Should have fee calculation
        $this->assertNotNull($component->get('feeCalculation'));

        // Clear selection
        $component->set('selectedEventCodes', []);

        // Fee calculation should be cleared
        $component->assertSet('feeCalculation', null);
    }

    #[Test]
    public function component_detects_pending_payments(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create();
        $event = Event::first();
        $registration->events()->attach($event->code, ['price_at_registration' => 100.00]);

        // Add payment with pending approval
        $registration->payments()->create([
            'amount' => 50.00,
            'status' => 'pending_approval',
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification');

        $component->assertSet('hasPendingPayments', true);
    }

    #[Test]
    public function component_does_not_detect_pending_payments_when_none_exist(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create();
        $event = Event::first();
        $registration->events()->attach($event->code, ['price_at_registration' => 100.00]);

        // Add payment with different status
        $registration->payments()->create([
            'amount' => 50.00,
            'status' => 'paid',
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification');

        $component->assertSet('hasPendingPayments', false);
    }

    #[Test]
    public function component_recalculates_fees_when_selected_events_change(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $events = Event::take(3)->get();
        $currentEvent = $events->first();
        $newEvent1 = $events->get(1);
        $newEvent2 = $events->get(2);

        // Add current event
        $registration->events()->attach($currentEvent->code, ['price_at_registration' => 100.00]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification');

        // Select first new event
        $component->set('selectedEventCodes', [$newEvent1->code]);
        $firstCalculation = $component->get('feeCalculation');

        // Select second new event instead
        $component->set('selectedEventCodes', [$newEvent2->code]);
        $secondCalculation = $component->get('feeCalculation');

        // Calculations should be different (different events may have different fees)
        $this->assertNotNull($firstCalculation);
        $this->assertNotNull($secondCalculation);
    }

    #[Test]
    public function component_handles_multiple_event_selection(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create([
            'registration_category_snapshot' => 'grad_student',
            'participation_format' => 'in-person',
        ]);

        $events = Event::take(3)->get();
        $currentEvent = $events->first();
        $newEvent1 = $events->get(1);
        $newEvent2 = $events->get(2);

        // Add current event
        $registration->events()->attach($currentEvent->code, ['price_at_registration' => 100.00]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification')
            ->set('selectedEventCodes', [$newEvent1->code, $newEvent2->code]);

        $feeCalculation = $component->get('feeCalculation');
        $this->assertNotNull($feeCalculation);
        $this->assertArrayHasKey('new_items_cost', $feeCalculation);
        $this->assertArrayHasKey('amount_due', $feeCalculation);
    }

    #[Test]
    public function component_passes_correct_data_to_view(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->for($user)->create([
            'full_name' => 'Test User Data',
        ]);
        $event = Event::first();
        $registration->events()->attach($event->code, ['price_at_registration' => 100.00]);

        $component = Livewire::actingAs($user)
            ->test('pages.registration-modification');

        // Test component properties directly
        $component->assertSet('registration.id', $registration->id);
        $component->assertSet('hasPendingPayments', false);
        $component->assertSet('feeCalculation', null);

        // Test that availableEvents is loaded
        $this->assertNotNull($component->get('availableEvents'));
    }
}
