<?php

namespace Tests\Browser;

use App\Mail\RegistrationModifiedNotification;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\FeesTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

#[Group('dusk')]
#[Group('notification')]
#[Group('modification')]
class RegistrationModificationNotificationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(EventsTableSeeder::class);
        $this->seed(FeesTableSeeder::class);
    }

    #[Test]
    public function user_can_modify_registration_and_notification_is_sent(): void
    {
        Mail::fake();

        // Create user with verified email
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create initial registration with BCSMIF only
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe Test',
            'email' => $user->email,
            'calculated_fee' => 600.00,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registrations'))
                ->assertSee('John Doe Test')
                ->assertSee('BCSMIF2025')

                // Click modify button
                ->click('[data-registration-id="'.$registration->id.'"] .modify-button')
                ->waitForText(__('Modify Registration'))
                ->assertSee(__('Selected Events'))

                // Add workshop to the registration
                ->check('events[RAA2025]')
                ->waitFor('[wire\\:loading]', 10, false) // Wait for loading to complete
                ->pause(1000) // Additional pause for fee calculation

                // Submit modification
                ->press(__('Update Registration'))
                ->waitForText(__('Registration updated successfully'))
                ->assertSee(__('Registration updated successfully'));
        });

        // Verify that the notification was sent
        Mail::assertSent(RegistrationModifiedNotification::class, function ($mail) use ($registration, $user) {
            return $mail->registration->id === $registration->id &&
                   $mail->hasTo($user->email) &&
                   $mail->forCoordinator === false;
        });

        // Verify coordinator notification was sent
        Mail::assertSent(RegistrationModifiedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id &&
                   $mail->forCoordinator === true;
        });

        // Total should be 2 notifications (user + coordinator)
        Mail::assertSent(RegistrationModifiedNotification::class, 2);
    }

    #[Test]
    public function modification_form_displays_current_events_correctly(): void
    {
        // Create user with verified email
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create registration with multiple events
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Multi Event User',
            'email' => $user->email,
            'calculated_fee' => 850.00,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $raaEvent = Event::where('code', 'RAA2025')->first();

        $registration->events()->attach([
            $bcsmifEvent->code => [
                'price_at_registration' => 600.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $raaEvent->code => [
                'price_at_registration' => 250.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registrations'))

                // Click modify button
                ->click('[data-registration-id="'.$registration->id.'"] .modify-button')
                ->waitForText(__('Modify Registration'))

                // Verify current events are pre-selected
                ->assertChecked('events[BCSMIF2025]')
                ->assertChecked('events[RAA2025]')

                // Verify current fee is displayed
                ->assertSee('R$ 850,00');
        });
    }

    #[Test]
    public function modification_form_shows_fee_updates_dynamically(): void
    {
        // Create user with verified email
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create registration with only BCSMIF
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Fee Update Test',
            'email' => $user->email,
            'calculated_fee' => 600.00,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registrations'))

                // Click modify button
                ->click('[data-registration-id="'.$registration->id.'"] .modify-button')
                ->waitForText(__('Modify Registration'))

                // Initial fee should show current amount
                ->assertSee('R$ 600,00')

                // Add RAA workshop
                ->check('events[RAA2025]')
                ->waitFor('[wire\\:loading]', 10, false) // Wait for loading spinner to disappear
                ->pause(1000) // Wait for fee calculation

                // New total should be higher (600 + workshop fee)
                ->assertSee('R$ 850,00') // BCSMIF(600) + RAA(250)

                // Remove BCSMIF (keep only workshop)
                ->uncheck('events[BCSMIF2025]')
                ->waitFor('[wire\\:loading]', 10, false)
                ->pause(1000)

                // Should show only workshop fee
                ->assertSee('R$ 250,00');
        });
    }

    #[Test]
    public function modification_prevents_removing_all_events(): void
    {
        // Create user with verified email
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create registration with only BCSMIF
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Validation Test',
            'email' => $user->email,
            'calculated_fee' => 600.00,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registrations'))

                // Click modify button
                ->click('[data-registration-id="'.$registration->id.'"] .modify-button')
                ->waitForText(__('Modify Registration'))

                // Try to uncheck the only event
                ->uncheck('events[BCSMIF2025]')
                ->pause(500)

                // Submit form - should show validation error
                ->press(__('Update Registration'))
                ->waitForText(__('You must select at least one event'))
                ->assertSee(__('You must select at least one event'));
        });
    }

    #[Test]
    public function modification_requires_authentication_and_ownership(): void
    {
        // Create two users
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        // Create registration owned by first user
        $registration = Registration::factory()->create([
            'user_id' => $owner->id,
            'full_name' => 'Owner Test',
            'email' => $owner->email,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($otherUser, $registration) {
            // Test unauthenticated access
            $browser->logout()
                ->visit('/my-registration/modify/'.$registration->id)
                ->waitForLocation('/login/local')
                ->assertPathIs('/login/local');

            // Test access by wrong user
            $browser->loginAs($otherUser)
                ->visit('/my-registration/modify/'.$registration->id)
                ->assertSee('403')
                ->assertDontSee(__('Modify Registration'));
        });
    }

    #[Test]
    public function modification_success_shows_updated_information(): void
    {
        Mail::fake();

        // Create user with verified email
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create registration with BCSMIF only
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Success Test User',
            'email' => $user->email,
            'calculated_fee' => 600.00,
        ]);

        $bcsmifEvent = Event::where('code', 'BCSMIF2025')->first();
        $registration->events()->attach($bcsmifEvent->code, [
            'price_at_registration' => 600.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registrations'))

                // Modify registration to add workshop
                ->click('[data-registration-id="'.$registration->id.'"] .modify-button')
                ->waitForText(__('Modify Registration'))
                ->check('events[RAA2025]')
                ->waitFor('[wire\\:loading]', 10, false)
                ->pause(1000)
                ->press(__('Update Registration'))
                ->waitForText(__('Registration updated successfully'))

                // Return to my registrations and verify updated information
                ->visit('/my-registration')
                ->waitForText(__('My Registrations'))
                ->assertSee('Success Test User')

                // Check details to verify both events are now listed
                ->click('[data-registration-id="'.$registration->id.'"] .details-button')
                ->waitForText(__('Registration Details'))
                ->assertSee('BCSMIF2025')
                ->assertSee('RAA2025')
                ->assertSee('R$ 850,00'); // Updated total
        });

        // Verify notifications were sent
        Mail::assertSent(RegistrationModifiedNotification::class, 2);
    }
}
