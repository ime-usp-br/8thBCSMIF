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

        $event = Event::factory()->create(['code' => 'BCSMIF2025', 'name' => '8th BCSMIF']);
        $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Test Doe',
            'email' => 'john@example.com',
            'calculated_fee' => 100.50,
            'payment_status' => 'pending_payment',
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
}
