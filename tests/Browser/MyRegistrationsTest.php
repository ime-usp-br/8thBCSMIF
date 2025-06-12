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
}
