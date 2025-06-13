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

    /**
     * AC5: Test Dusk verifies that when clicking to view registration details,
     * the correct data is displayed, including all associated events and
     * their respective price_at_registration values.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function registration_details_display_correct_events_and_prices(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create events for the registration
        $event1 = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $event2 = Event::where('code', 'RAA2025')->firstOrFail();
        $event3 = Event::where('code', 'WDA2025')->firstOrFail();

        // Create a registration with multiple events and different prices
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'approved',
            'calculated_fee' => 625.25,
            'full_name' => 'John Doe Registration Test',
            'email' => 'john.doe@example.com',
            'nationality' => 'Brazilian',
            'document_country_origin' => 'Brasil',
        ]);

        // Associate multiple events with different price_at_registration values
        $registration->events()->attach($event1->code, ['price_at_registration' => 350.50]);
        $registration->events()->attach($event2->code, ['price_at_registration' => 175.75]);
        $registration->events()->attach($event3->code, ['price_at_registration' => 99.00]);

        $this->browse(function (Browser $browser) use ($user, $registration, $event1, $event2, $event3) {
            $browser->loginAs($user)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))
                ->waitForText(__('Registration').' #'.$registration->id)

                // Click to view registration details
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Registration Details'))

                // Verify personal information is displayed
                ->assertSeeIn('.grid', __('Personal Information'))
                ->assertSeeIn('.grid', __('Full Name').': John Doe Registration Test')
                ->assertSeeIn('.grid', __('Email').': john.doe@example.com')
                ->assertSeeIn('.grid', __('Nationality').': Brazilian')
                ->assertSeeIn('.grid', __('Document Country').': Brasil')

                // Verify Events & Pricing section is displayed
                ->assertSeeIn('.grid', __('Events & Pricing'))

                // Verify all three events are displayed with correct names
                ->assertSeeIn('.grid', $event1->name)
                ->assertSeeIn('.grid', $event2->name)
                ->assertSeeIn('.grid', $event3->name)

                // Verify each event shows its correct price_at_registration
                ->assertSeeIn('.grid', __('Price at Registration').': R$ 350,50')
                ->assertSeeIn('.grid', __('Price at Registration').': R$ 175,75')
                ->assertSeeIn('.grid', __('Price at Registration').': R$ 99,00')

                // Verify total calculated fee is displayed
                ->assertSeeIn('.grid', __('Total Fee').': R$ 625,25')

                // Verify that clicking the button again hides the details
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitUntilMissing('.grid')
                ->assertDontSee(__('Registration Details'));
        });
    }

    /**
     * AC8: Test Dusk simulates uploading a valid payment proof file (small PDF)
     * and verifies that a success message is displayed and the registration status
     * is updated to 'pending_br_proof_approval'.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function payment_proof_upload_success_with_valid_pdf_file(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create an event for the registration
        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();

        // Create a registration eligible for payment proof upload
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
                ->assertVisible('input[name="payment_proof"]')
                ->assertVisible('@upload-payment-proof-button')

                // Upload the test PDF file
                ->attach('payment_proof', __DIR__.'/files/test_payment_proof.pdf')
                ->click('@upload-payment-proof-button')

                // Wait for page to load after form submission
                ->waitForLocation('/my-registrations')
                ->pause(1000)

                // Verify the page reflects the updated payment status directly in the list
                ->waitForText(__('Pending br proof approval'))
                ->assertSee(__('Pending br proof approval'))

                // Click to view the registration details to verify upload form is no longer visible
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Registration Details'))
                
                // Verify that the payment proof upload form is no longer displayed since status changed
                ->assertDontSee(__('Payment Proof Upload'))
                ->assertMissing('input[name="payment_proof"]')
                ->assertMissing('@upload-payment-proof-button');
        });

        // Verify database was updated correctly
        $registration->refresh();
        $this->assertEquals('pending_br_proof_approval', $registration->payment_status);
        $this->assertNotNull($registration->payment_proof_path);
        $this->assertNotNull($registration->payment_uploaded_at);

        // Since we're using Storage::fake('private'), the file path should be set but file won't exist in fake storage
        $this->assertStringContainsString('proofs/'.$registration->id, $registration->payment_proof_path);
    }

    /**
     * AC4: Test Dusk confirms that an authenticated user does NOT see
     * other user's registrations in their "My Registrations" list.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('my-registrations')]
    public function authenticated_user_does_not_see_other_users_registrations(): void
    {
        // Create two different users
        $user1 = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'User One',
            'email' => 'user1@example.com',
        ]);

        $user2 = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'User Two',
            'email' => 'user2@example.com',
        ]);

        // Create events for the registrations
        $event1 = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $event2 = Event::where('code', 'RAA2025')->firstOrFail();

        // Create registrations for user1
        $user1Registration1 = Registration::factory()->create([
            'user_id' => $user1->id,
            'payment_status' => 'pending_payment',
            'calculated_fee' => 350.50,
            'full_name' => 'User One Registration',
        ]);

        $user1Registration2 = Registration::factory()->create([
            'user_id' => $user1->id,
            'payment_status' => 'approved',
            'calculated_fee' => 225.75,
            'full_name' => 'User One Second Registration',
        ]);

        // Create registrations for user2 (different user)
        $user2Registration1 = Registration::factory()->create([
            'user_id' => $user2->id,
            'payment_status' => 'pending_payment',
            'calculated_fee' => 400.00,
            'full_name' => 'User Two Registration',
        ]);

        $user2Registration2 = Registration::factory()->create([
            'user_id' => $user2->id,
            'payment_status' => 'pending_br_proof_approval',
            'calculated_fee' => 150.00,
            'full_name' => 'User Two Other Registration',
        ]);

        // Associate events with registrations
        $user1Registration1->events()->attach($event1->code, ['price_at_registration' => 350.50]);
        $user1Registration2->events()->attach($event2->code, ['price_at_registration' => 225.75]);
        $user2Registration1->events()->attach($event1->code, ['price_at_registration' => 400.00]);
        $user2Registration2->events()->attach($event2->code, ['price_at_registration' => 150.00]);

        $this->browse(function (Browser $browser) use ($user1, $user2, $user1Registration1, $user1Registration2, $user2Registration1, $user2Registration2) {
            // Login as user1 and verify they only see their own registrations
            $browser->loginAs($user1)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))

                // User1 should see their own registrations
                ->assertSee(__('Registration').' #'.$user1Registration1->id)
                ->assertSee(__('Registration').' #'.$user1Registration2->id)
                ->assertSee('R$ 350,50')
                ->assertSee('R$ 225,75')

                // User1 should NOT see user2's registrations
                ->assertDontSee(__('Registration').' #'.$user2Registration1->id)
                ->assertDontSee(__('Registration').' #'.$user2Registration2->id)
                ->assertDontSee('R$ 400,00')
                ->assertDontSee('R$ 150,00')

                // Logout user1
                ->logout();

            // Login as user2 and verify they only see their own registrations
            $browser->loginAs($user2)
                ->visit('/my-registrations')
                ->waitForText(__('My Registrations'))

                // User2 should see their own registrations
                ->assertSee(__('Registration').' #'.$user2Registration1->id)
                ->assertSee(__('Registration').' #'.$user2Registration2->id)
                ->assertSee('R$ 400,00')
                ->assertSee('R$ 150,00')

                // User2 should NOT see user1's registrations
                ->assertDontSee(__('Registration').' #'.$user1Registration1->id)
                ->assertDontSee(__('Registration').' #'.$user1Registration2->id)
                ->assertDontSee('R$ 350,50')
                ->assertDontSee('R$ 225,75')

                // Additional verification: Click on user2's first registration to view details
                // and verify full_name is correctly shown (only user2's names)
                ->click("button[wire\\:click='viewRegistration({$user2Registration1->id})']")
                ->waitForText(__('Registration Details'))
                ->assertSee('User Two Registration')
                ->assertDontSee('User One Registration')
                ->assertDontSee('User One Second Registration');
        });
    }
}
