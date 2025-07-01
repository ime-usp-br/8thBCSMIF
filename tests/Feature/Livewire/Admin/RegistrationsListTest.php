<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\RegistrationsList;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationsListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'usp_user', 'guard_name' => 'web']);
    }

    public function test_component_can_render(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->assertOk();
    }

    public function test_component_displays_registration_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $event = Event::factory()->create([
            'code' => 'BCSMIF2025',
            'name' => '8th BCSMIF',
            'is_main_conference' => true,
            'registration_deadline_early' => now()->addDays(30),
            'registration_deadline_late' => now()->addDays(60),
        ]);
        $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Test Doe',
            'email' => 'john@example.com',
            'payment_status' => 'pending_payment',
            'registration_category_snapshot' => 'graduate_student',
            'participation_format' => 'in-person',
        ]);

        // Create fee for the test event to match the expected behavior
        \App\Models\Fee::factory()->create([
            'event_code' => 'BCSMIF2025',
            'participant_category' => 'graduate_student',
            'type' => 'in-person',
            'period' => 'early',
            'price' => 100.50,
            'is_discount_for_main_event_participant' => false,
        ]);

        $registration->events()->attach($event->code, ['price_at_registration' => 100.50]);

        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->assertSee('#'.$registration->id)
            ->assertSee('John Test Doe')
            ->assertSee('john@example.com')
            ->assertSee('BCSMIF2025')
            ->assertSee('R$ 100,50')
            ->assertSee(__('Pending Payment'))
            ->assertSee($registration->created_at->format('d/m/Y'));
    }

    public function test_component_displays_multiple_events(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $event1 = Event::factory()->create(['code' => 'BCSMIF2025']);
        $event2 = Event::factory()->create(['code' => 'RAA2025']);
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Multi Event User',
            'payment_status' => 'paid_br',
        ]);

        $registration->events()->attach([
            $event1->code => ['price_at_registration' => 50.00],
            $event2->code => ['price_at_registration' => 30.00],
        ]);

        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->assertSee('BCSMIF2025')
            ->assertSee('RAA2025')
            ->assertSee(__('Paid (BR)'));
    }

    public function test_component_displays_no_events_message(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'No Events User',
        ]);

        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->assertSee('No Events User')
            ->assertSee(__('No events'));
    }

    public function test_component_displays_different_payment_statuses(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user1->id,
            'payment_status' => 'pending_payment',
            'full_name' => 'Pending User',
        ]);

        Registration::factory()->create([
            'user_id' => $user2->id,
            'payment_status' => 'paid_br',
            'full_name' => 'Paid BR User',
        ]);

        Registration::factory()->create([
            'user_id' => $user3->id,
            'payment_status' => 'paid_int',
            'full_name' => 'Paid Int User',
        ]);

        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->assertSee(__('Pending Payment'))
            ->assertSee(__('Paid (BR)'))
            ->assertSee(__('Paid (International)'));
    }

    public function test_component_shows_no_registrations_message_when_empty(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->assertSee(__('No registrations found'));
    }

    public function test_component_paginates_registrations(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create 20 registrations to test pagination (component uses 15 per page)
        $users = User::factory()->count(20)->create();
        foreach ($users as $index => $user) {
            Registration::factory()->create([
                'user_id' => $user->id,
                'full_name' => "User {$index}",
            ]);
        }

        $component = Livewire::actingAs($admin)->test(RegistrationsList::class);

        // Should see first page users
        $component->assertSee('User 0');
        $component->assertSee('User 14');

        // Should not see users from second page on first page
        $component->assertDontSee('User 15');
        $component->assertDontSee('User 19');
    }

    public function test_component_orders_registrations_by_created_at_desc(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create first registration
        $registration1 = Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'First Registration',
            'created_at' => now()->subDay(),
        ]);

        // Create second registration (more recent)
        $registration2 = Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'Second Registration',
            'created_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->assertSeeInOrder(['Second Registration', 'First Registration']);
    }

    public function test_component_filters_by_event_code(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $eventBcsmif = Event::factory()->create(['code' => 'BCSMIF2025', 'name' => '8th BCSMIF']);
        $eventRaa = Event::factory()->create(['code' => 'RAA2025', 'name' => 'RAA 2025']);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Registration with BCSMIF event
        $registrationBcsmif = Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'BCSMIF User',
        ]);
        $registrationBcsmif->events()->attach($eventBcsmif->code, ['price_at_registration' => 100.00]);

        // Registration with RAA event
        $registrationRaa = Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'RAA User',
        ]);
        $registrationRaa->events()->attach($eventRaa->code, ['price_at_registration' => 50.00]);

        // Registration with both events
        $registrationBoth = Registration::factory()->create([
            'user_id' => $user3->id,
            'full_name' => 'Both Events User',
        ]);
        $registrationBoth->events()->attach([
            $eventBcsmif->code => ['price_at_registration' => 100.00],
            $eventRaa->code => ['price_at_registration' => 50.00],
        ]);

        // Test filter by BCSMIF event
        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->set('filterEventCode', 'BCSMIF2025')
            ->assertSee('BCSMIF User')
            ->assertSee('Both Events User')
            ->assertDontSee('RAA User');

        // Test filter by RAA event
        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->set('filterEventCode', 'RAA2025')
            ->assertSee('RAA User')
            ->assertSee('Both Events User')
            ->assertDontSee('BCSMIF User');

        // Test no filter (all registrations)
        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->set('filterEventCode', '')
            ->assertSee('BCSMIF User')
            ->assertSee('RAA User')
            ->assertSee('Both Events User');
    }

    public function test_displays_individual_payment_statuses(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id, 'full_name' => 'Multi Payment User']);

        \App\Models\Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'paid',
        ]);

        \App\Models\Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'pending',
        ]);

        \App\Models\Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'pending_approval',
        ]);

        Livewire::actingAs($admin)
            ->test(RegistrationsList::class)
            ->assertSee('Multi Payment User')
            ->assertSee(__('paid'))
            ->assertSee(__('pending'))
            ->assertSee(__('pending_approval'));
    }

    public function test_component_filters_reset_pagination(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $event = Event::factory()->create(['code' => 'BCSMIF2025']);

        // Create enough registrations to trigger pagination (20 registrations, 15 per page)
        $users = User::factory()->count(20)->create();
        foreach ($users as $index => $user) {
            $registration = Registration::factory()->create([
                'user_id' => $user->id,
                'full_name' => "User {$index}",
                'payment_status' => $index < 10 ? 'pending_payment' : 'paid_br',
            ]);

            if ($index < 5) {
                $registration->events()->attach($event->code, ['price_at_registration' => 100.00]);
            }
        }

        $component = Livewire::actingAs($admin)->test(RegistrationsList::class);

        // Navigate to page 2 by clicking pagination link
        $component->call('gotoPage', 2);

        // Apply event filter - should show filtered results on page 1
        $component->set('filterEventCode', 'BCSMIF2025');

        // Verify we have filtered results (only 5 registrations with BCSMIF event should be visible)
        for ($i = 0; $i < 5; $i++) {
            $component->assertSee("User {$i}");
        }
        // Users 5-19 should not be visible (they don't have BCSMIF event)
        $component->assertDontSee('User 15');
    }
}
