<?php

namespace Tests\Browser;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class MyRegistrationsTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'FeesTableSeeder']);
    }

    /**
     * AC1: Test that an unauthenticated user trying to access /my-registrations
     * is redirected to the login page (/login/local).
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/my-registrations')
                ->waitForLocation('/login/local')
                ->assertPathIs('/login/local');
        });
    }

    /**
     * Test that the payment proof upload form is displayed correctly for Brazilian users
     * with pending payment status.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function payment_proof_upload_form_is_displayed_for_brazilian_users(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create an event for the registration
        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();

        // Create a registration with pending payment status and Brazilian document
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'document_country_origin' => 'Brasil',
            'calculated_fee' => 500.00,
        ]);

        // Associate the event with the registration
        $registration->events()->attach($event->code, ['price_at_registration' => 500.00]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))
                ->waitForText(__('Registration').' #'.$registration->id)
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment Proof Upload'))
                ->assertSee(__('Payment Proof Document'))
                ->assertVisible('input[name="payment_proof"]')
                ->assertVisible('@upload-payment-proof-button');
        });
    }

    /**
     * Test that payment proof upload form is not displayed for non-Brazilian users.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function payment_proof_upload_form_is_not_displayed_for_non_brazilian_users(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create an event for the registration
        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();

        // Create a registration with pending payment status but NON-Brazilian document
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'document_country_origin' => 'Argentina', // Non-Brazilian
            'calculated_fee' => 500.00,
        ]);

        // Associate the event with the registration
        $registration->events()->attach($event->code, ['price_at_registration' => 500.00]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))
                ->waitForText(__('Registration').' #'.$registration->id)
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->assertDontSee(__('Payment Proof Upload'))
                ->assertMissing('input[name="payment_proof"]')
                ->assertMissing('@upload-payment-proof-button');
        });
    }

    /**
     * Test that payment proof upload form is not displayed when payment status is not pending.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function payment_proof_upload_form_is_not_displayed_when_payment_not_pending(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create an event for the registration
        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();

        // Create a registration with APPROVED payment status (not pending)
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'approved', // Not pending
            'document_country_origin' => 'Brasil',
            'calculated_fee' => 500.00,
        ]);

        // Associate the event with the registration
        $registration->events()->attach($event->code, ['price_at_registration' => 500.00]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))
                ->waitForText(__('Registration').' #'.$registration->id)
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->assertDontSee(__('Payment Proof Upload'))
                ->assertMissing('input[name="payment_proof"]')
                ->assertMissing('@upload-payment-proof-button');
        });
    }

    /**
     * AC2: Test that an authenticated user with no registrations sees
     * the empty list message and a link to the registration page.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function authenticated_user_with_no_registrations_sees_empty_state_message(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Ensure user has no registrations (factory doesn't create any by default)
        // But let's be explicit about it
        $user->registrations()->delete();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))
                ->assertSee(__('No registrations found'))
                ->assertSee(__('You have not registered for any events yet.'))
                ->assertSeeLink(__('Register for Event'))
                ->click('a[href="'.route('register-event').'"]')
                ->waitForLocation('/register-event')
                ->assertPathIs('/register-event');
        });
    }

    /**
     * AC3: Test that the "My Registrations" page correctly lists a test user's
     * registrations (after creating some via factory), displaying key information
     * like ID, event names, total fee and payment status.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function my_registrations_page_lists_user_registrations_correctly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create events for the registrations
        $event1 = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $event2 = Event::where('code', 'RAA2025')->firstOrFail();
        $event3 = Event::where('code', 'WDA2025')->firstOrFail();

        // Create multiple registrations with different scenarios for the user
        $registration1 = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_payment',
            'calculated_fee' => 350.50,
            'full_name' => 'Test User One',
        ]);

        $registration2 = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'approved',
            'calculated_fee' => 275.75,
            'full_name' => 'Test User One',
        ]);

        $registration3 = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending_br_proof_approval',
            'calculated_fee' => 125.00,
            'full_name' => 'Test User One',
        ]);

        // Associate events with registrations using different price points
        $registration1->events()->attach($event1->code, ['price_at_registration' => 350.50]);

        $registration2->events()->attach($event2->code, ['price_at_registration' => 175.75]);
        $registration2->events()->attach($event3->code, ['price_at_registration' => 100.00]);

        $registration3->events()->attach($event3->code, ['price_at_registration' => 125.00]);

        $this->browse(function (Browser $browser) use ($user, $registration1, $registration2, $registration3, $event1, $event2, $event3) {
            $browser->loginAs($user)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))

                // Verify all three registrations are displayed
                ->assertSee(__('Registration').' #'.$registration1->id)
                ->assertSee(__('Registration').' #'.$registration2->id)
                ->assertSee(__('Registration').' #'.$registration3->id)

                // Verify registration 1 details
                ->assertSee($event1->name)
                ->assertSee('R$ 350,50')
                ->assertSee(__('Pending payment'))

                // Verify registration 2 details (multiple events)
                ->assertSee($event2->name)
                ->assertSee('R$ 275,75')
                ->assertSee(__('Approved'))

                // Verify registration 3 details
                ->assertSee('R$ 125,00')
                ->assertSee(__('Pending br proof approval'))

                // Verify that all event names appear somewhere on the page
                ->assertSee($event1->name)
                ->assertSee($event2->name)
                ->assertSee($event3->name);
        });
    }
}
