<?php

namespace Tests\Browser;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class AdminRegistrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'FeesTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /**
     * AC9.1: Test that unauthenticated users cannot access admin registration pages
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function unauthenticated_user_cannot_access_admin_registrations(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/admin/registrations')
                ->waitForLocation('/login/local')
                ->assertPathIs('/login/local');
        });
    }

    /**
     * AC9.2: Test that authenticated users without admin role cannot access admin registration pages
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function authenticated_non_admin_user_cannot_access_admin_registrations(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Assign non-admin role
        $user->assignRole('usp_user');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/registrations')
                ->assertSee('403')
                ->assertDontSee(__('Registration Management'));
        });
    }

    /**
     * AC9.3: Test that admin users can access the registration list interface
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function admin_user_can_access_registration_list_interface(): void
    {
        $adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        $this->browse(function (Browser $browser) use ($adminUser) {
            $browser->loginAs($adminUser)
                ->visit('/admin/registrations')
                ->waitForText(__('Registration List'))
                ->assertSee(__('Registration List'))
                ->assertSee(__('Filter by Event'))
                ->assertSee(__('Filter by Payment Status'))
                ->assertVisible('#filterEventCode')
                ->assertVisible('#filterPaymentStatus');
        });
    }

    /**
     * AC9.4: Test registration listing displays correctly with pagination
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function admin_registration_list_displays_correctly(): void
    {
        $adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        // Create a few registrations to test display
        $users = User::factory()->count(3)->create();
        $event = Event::where('code', 'BCSMIF2025')->first();

        foreach ($users as $index => $user) {
            $registration = Registration::factory()->create([
                'user_id' => $user->id,
                'full_name' => "Test User {$index}",
                'email' => $user->email,
                'payment_status' => 'pending_payment',
                'calculated_fee' => 350.50,
            ]);
            $registration->events()->attach($event->code, ['price_at_registration' => 350.50]);
        }

        $this->browse(function (Browser $browser) use ($adminUser) {
            $browser->loginAs($adminUser)
                ->visit('/admin/registrations')
                ->waitForText(__('Registration List'))
                ->assertSee('Test User 0')
                ->assertSee('Test User 1')
                ->assertSee('Test User 2')
                
                // Test that registration data is properly displayed
                ->assertSee('R$ 350,50')
                ->assertSee(__('Pending Payment'))
                ->assertSee('BCSMIF2025'); // Event code should be visible
        });
    }

    /**
     * AC9.5: Test event filter functionality
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function admin_can_filter_registrations_by_event(): void
    {
        $adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        // Create registrations for different events
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $eventBCSMIF = Event::where('code', 'BCSMIF2025')->first();
        $eventRAA = Event::where('code', 'RAA2025')->first();

        $registration1 = Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'BCSMIF Participant',
            'email' => $user1->email,
            'payment_status' => 'pending_payment',
        ]);
        $registration1->events()->attach($eventBCSMIF->code, ['price_at_registration' => 350.50]);

        $registration2 = Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'RAA Participant',
            'email' => $user2->email,
            'payment_status' => 'pending_payment',
        ]);
        $registration2->events()->attach($eventRAA->code, ['price_at_registration' => 250.00]);

        $registration3 = Registration::factory()->create([
            'user_id' => $user3->id,
            'full_name' => 'Both Events Participant',
            'email' => $user3->email,
            'payment_status' => 'approved',
        ]);
        $registration3->events()->attach($eventBCSMIF->code, ['price_at_registration' => 350.50]);
        $registration3->events()->attach($eventRAA->code, ['price_at_registration' => 250.00]);

        $this->browse(function (Browser $browser) use ($adminUser) {
            $browser->loginAs($adminUser)
                ->visit('/admin/registrations')
                ->waitForText(__('Registration List'))

                // Initially should see all registrations
                ->assertSee('BCSMIF Participant')
                ->assertSee('RAA Participant')
                ->assertSee('Both Events Participant')

                // Filter by BCSMIF2025 event
                ->select('#filterEventCode', 'BCSMIF2025')
                ->pause(500) // Wait for Livewire to process the filter
                ->assertSee('BCSMIF Participant')
                ->assertSee('Both Events Participant') // This participant has both events
                ->assertDontSee('RAA Participant')

                // Filter by RAA2025 event
                ->select('#filterEventCode', 'RAA2025')
                ->pause(500)
                ->assertSee('RAA Participant')
                ->assertSee('Both Events Participant')
                ->assertDontSee('BCSMIF Participant')

                // Clear filter - should see all again
                ->select('#filterEventCode', '')
                ->pause(500)
                ->assertSee('BCSMIF Participant')
                ->assertSee('RAA Participant')
                ->assertSee('Both Events Participant');
        });
    }

    /**
     * AC9.6: Test payment status filter functionality
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function admin_can_filter_registrations_by_payment_status(): void
    {
        $adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        // Create registrations with different payment statuses
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $event = Event::where('code', 'BCSMIF2025')->first();

        $registration1 = Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'Pending Payment User',
            'email' => $user1->email,
            'payment_status' => 'pending_payment',
        ]);
        $registration1->events()->attach($event->code, ['price_at_registration' => 350.50]);

        $registration2 = Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'Paid BR User',
            'email' => $user2->email,
            'payment_status' => 'paid_br',
        ]);
        $registration2->events()->attach($event->code, ['price_at_registration' => 350.50]);

        $registration3 = Registration::factory()->create([
            'user_id' => $user3->id,
            'full_name' => 'Cancelled User',
            'email' => $user3->email,
            'payment_status' => 'cancelled',
        ]);
        $registration3->events()->attach($event->code, ['price_at_registration' => 350.50]);

        $this->browse(function (Browser $browser) use ($adminUser) {
            $browser->loginAs($adminUser)
                ->visit('/admin/registrations')
                ->waitForText(__('Registration List'))

                // Initially should see all registrations
                ->assertSee('Pending Payment User')
                ->assertSee('Paid BR User')
                ->assertSee('Cancelled User')

                // Filter by pending payment status
                ->select('#filterPaymentStatus', 'pending_payment')
                ->pause(500)
                ->assertSee('Pending Payment User')
                ->assertDontSee('Paid BR User')
                ->assertDontSee('Cancelled User')

                // Filter by paid_br status
                ->select('#filterPaymentStatus', 'paid_br')
                ->pause(500)
                ->assertSee('Paid BR User')
                ->assertDontSee('Pending Payment User')
                ->assertDontSee('Cancelled User')

                // Clear filter - should see all again
                ->select('#filterPaymentStatus', '')
                ->pause(500)
                ->assertSee('Pending Payment User')
                ->assertSee('Paid BR User')
                ->assertSee('Cancelled User');
        });
    }

    /**
     * AC9.7: Test that registration details links are available and functional
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function admin_can_access_registration_details_links(): void
    {
        $adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        $user = User::factory()->create();
        $event1 = Event::where('code', 'BCSMIF2025')->first();

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe Test',
            'email' => 'john.doe@test.com',
            'payment_status' => 'pending_payment',
            'calculated_fee' => 350.50,
            'nationality' => 'Brazilian',
            'document_country_origin' => 'Brasil',
            'affiliation' => 'USP',
        ]);

        $registration->events()->attach($event1->code, ['price_at_registration' => 350.50]);

        $this->browse(function (Browser $browser) use ($adminUser, $registration, $event1) {
            $browser->loginAs($adminUser)
                ->visit('/admin/registrations')
                ->waitForText(__('Registration List'))
                ->assertSee('John Doe Test')
                ->assertSee('john.doe@test.com')

                // Test that Details links are present and correctly formed
                ->assertSee(__('Details'))
                
                // Directly visit the details page to test it works
                ->visit('/admin/registrations/'.$registration->id)
                ->assertSee('John Doe Test')
                ->assertSee('john.doe@test.com')
                ->assertSee('Brazilian')
                ->assertSee('Brasil')
                ->assertSee('USP')
                ->assertSee($event1->name)
                ->assertSee('R$ 350,50');
        });
    }

    /**
     * AC9.8: Test combined filters (event + payment status)
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function admin_can_use_combined_filters(): void
    {
        $adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        // Create registrations with different combinations of event and payment status
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user4 = User::factory()->create();

        $eventBCSMIF = Event::where('code', 'BCSMIF2025')->first();
        $eventRAA = Event::where('code', 'RAA2025')->first();

        // BCSMIF + pending payment
        $registration1 = Registration::factory()->create([
            'user_id' => $user1->id,
            'full_name' => 'BCSMIF Pending',
            'email' => $user1->email,
            'payment_status' => 'pending_payment',
        ]);
        $registration1->events()->attach($eventBCSMIF->code, ['price_at_registration' => 350.50]);

        // BCSMIF + paid_br
        $registration2 = Registration::factory()->create([
            'user_id' => $user2->id,
            'full_name' => 'BCSMIF Paid',
            'email' => $user2->email,
            'payment_status' => 'paid_br',
        ]);
        $registration2->events()->attach($eventBCSMIF->code, ['price_at_registration' => 350.50]);

        // RAA + pending payment
        $registration3 = Registration::factory()->create([
            'user_id' => $user3->id,
            'full_name' => 'RAA Pending',
            'email' => $user3->email,
            'payment_status' => 'pending_payment',
        ]);
        $registration3->events()->attach($eventRAA->code, ['price_at_registration' => 250.00]);

        // RAA + paid_br
        $registration4 = Registration::factory()->create([
            'user_id' => $user4->id,
            'full_name' => 'RAA Paid',
            'email' => $user4->email,
            'payment_status' => 'paid_br',
        ]);
        $registration4->events()->attach($eventRAA->code, ['price_at_registration' => 250.00]);

        $this->browse(function (Browser $browser) use ($adminUser) {
            $browser->loginAs($adminUser)
                ->visit('/admin/registrations')
                ->waitForText(__('Registration List'))

                // Apply combined filters: BCSMIF + pending payment
                ->select('#filterEventCode', 'BCSMIF2025')
                ->select('#filterPaymentStatus', 'pending_payment')
                ->pause(500)
                ->assertSee('BCSMIF Pending')
                ->assertDontSee('BCSMIF Paid')
                ->assertDontSee('RAA Pending')
                ->assertDontSee('RAA Paid')

                // Change to RAA + paid_br
                ->select('#filterEventCode', 'RAA2025')
                ->select('#filterPaymentStatus', 'paid_br')
                ->pause(500)
                ->assertSee('RAA Paid')
                ->assertDontSee('BCSMIF Pending')
                ->assertDontSee('BCSMIF Paid')
                ->assertDontSee('RAA Pending');
        });
    }

    /**
     * AC9.9: Test empty state when no registrations match filters
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function admin_sees_empty_state_when_no_registrations_match_filters(): void
    {
        $adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        // Create one registration that won't match our filter
        $user = User::factory()->create();
        $event = Event::where('code', 'BCSMIF2025')->first();

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Test User',
            'email' => $user->email,
            'payment_status' => 'pending_payment',
        ]);
        $registration->events()->attach($event->code, ['price_at_registration' => 350.50]);

        $this->browse(function (Browser $browser) use ($adminUser) {
            $browser->loginAs($adminUser)
                ->visit('/admin/registrations')
                ->waitForText(__('Registration List'))
                ->assertSee('Test User')

                // Apply filter that won't match any registration
                ->select('#filterEventCode', 'WDA2025') // Different event
                ->pause(500)
                ->assertDontSee('Test User')
                ->assertSee(__('No registrations found'))
                ->assertSee(__('No registrations match your current filters'))
                ->assertSee(__('Clear all filters'))

                // Click clear filters button
                ->click('button[wire\\:click="$set(\'filterEventCode\', \'\')"]')
                ->pause(500)
                ->assertSee('Test User')
                ->assertDontSee(__('No registrations found'));
        });
    }

    /**
     * AC9.10: Test responsive behavior on mobile and desktop views
     */
    #[Test]
    #[Group('dusk')]
    #[Group('admin')]
    public function admin_registration_list_is_responsive(): void
    {
        $adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        $user = User::factory()->create();
        $event = Event::where('code', 'BCSMIF2025')->first();

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Responsive Test User',
            'email' => 'responsive@test.com',
            'payment_status' => 'pending_payment',
            'calculated_fee' => 350.50,
        ]);
        $registration->events()->attach($event->code, ['price_at_registration' => 350.50]);

        $this->browse(function (Browser $browser) use ($adminUser) {
            // Test desktop view (1920x1080)
            $browser->loginAs($adminUser)
                ->resize(1920, 1080)
                ->visit('/admin/registrations')
                ->waitForText(__('Registration List'))
                ->assertVisible('.hidden.lg\\:block') // Desktop table should be visible
                ->assertMissing('.block.lg\\:hidden') // Mobile cards should be hidden

                // Test mobile view (375x667)
                ->resize(375, 667)
                ->pause(500)
                ->assertVisible('.block.lg\\:hidden') // Mobile cards should be visible
                ->assertMissing('.hidden.lg\\:block') // Desktop table should be hidden
                ->assertSee('Responsive Test User')
                ->assertSee('responsive@test.com');
        });
    }
}
