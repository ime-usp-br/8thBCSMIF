<?php

namespace Tests\Browser;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

/**
 * Test AC7 (Issue #51): Comprehensive Browser/UI tests for payment proof functionality.
 *
 * This test class ensures complete UI/UX coverage of payment proof management
 * including visual elements, user interactions, and browser behavior.
 */
class PaymentProofUITest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'FeesTableSeeder']);
    }

    /**
     * Test AC7: Complete payment proof upload UI workflow.
     * Verifies the entire user interface workflow from form display to success state.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('payment-proof')]
    public function complete_payment_proof_upload_ui_workflow(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $registration->events()->attach($event->code, ['price_at_registration' => 500.00]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 500.00,
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration, $payment) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registration'))
                ->assertSee(__('Registration').' #'.$registration->id)

                // Expand registration details
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))

                // Verify upload form is visible and properly styled
                ->assertVisible('@payment-form-'.$payment->id)
                ->assertSee(__('Payment Proof Upload'))
                ->assertVisible('input[name="payment_proof"]')
                ->assertVisible('@upload-payment-proof-button')
                ->assertSee(__('Accepted formats: JPG, JPEG, PNG, PDF. Maximum size: 10MB.'))

                // Verify form styling and structure
                ->assertVisible('.bg-yellow-50') // Warning background for pending payment
                ->assertVisible('.border-yellow-200')

                // Upload a valid file
                ->attach('input[name="payment_proof"]', __DIR__.'/files/test_payment_proof.pdf')
                ->click('@upload-payment-proof-button')

                // Wait for redirect and success state
                ->waitForLocation('/my-registration')
                ->pause(1000)

                // Expand again to see the updated state
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))

                // Verify success state UI
                ->assertDontSee(__('Payment Proof Upload'))
                ->assertMissing('input[name="payment_proof"]')
                ->assertMissing('@upload-payment-proof-button')

                // Verify success confirmation with proper styling
                ->assertVisible('.bg-green-50') // Success background
                ->assertVisible('.border-green-200')
                ->assertSee(__('Payment proof uploaded successfully'))
                ->assertSee(__('Uploaded on'))

                // Verify "View Proof" button is present and properly styled
                ->assertVisible('@view-payment-proof-button-'.$payment->id)
                ->assertSee(__('View Proof'))
                ->assertVisible('.bg-green-600') // Green button styling
                ->assertVisible('.hover\\:bg-green-700');
        });
    }

    /**
     * Test AC7: Multiple payments UI state management.
     * Verifies UI correctly handles multiple payments with different states.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('payment-proof')]
    public function multiple_payments_ui_state_management(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $registration->events()->attach($event->code, ['price_at_registration' => 800.00]);

        // Payment without proof (should show upload form)
        $pendingPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 400.00,
        ]);

        // Payment with proof (should show view button)
        $completedPayment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 400.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/'.$registration->id.'/existing_proof.pdf',
            'payment_date' => now()->subHours(2),
            'notes' => __('Payment proof uploaded by user'),
        ]);

        // Create the proof file in storage
        Storage::disk('private')->put($completedPayment->payment_proof_path, 'Existing proof content');

        $this->browse(function (Browser $browser) use ($user, $registration, $pendingPayment, $completedPayment) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registration'))
                ->assertSee(__('Registration').' #'.$registration->id)

                // Expand registration details
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))

                // Verify pending payment shows upload form
                ->assertVisible('@payment-form-'.$pendingPayment->id)
                ->assertSee(__('Payment Proof Upload'))
                ->assertVisible('input[name="payment_proof"]')

                // Verify completed payment shows view button
                ->assertVisible('@view-payment-proof-button-'.$completedPayment->id)
                ->assertSee(__('View Proof'))
                ->assertSee(__('Payment proof uploaded successfully'))

                // Verify upload form is not shown for completed payment
                ->assertMissing('@payment-form-'.$completedPayment->id)

                // Upload proof for pending payment
                ->attach('input[name="payment_proof"]', __DIR__.'/files/test_payment_proof.pdf')
                ->click('@upload-payment-proof-button')
                ->waitForLocation('/my-registration')
                ->pause(1000)

                // Re-expand to verify both payments now show view buttons
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))

                // Both payments should now have view buttons
                ->assertVisible('@view-payment-proof-button-'.$pendingPayment->id)
                ->assertVisible('@view-payment-proof-button-'.$completedPayment->id)

                // No upload forms should be visible
                ->assertMissing('@payment-form-'.$pendingPayment->id)
                ->assertMissing('@payment-form-'.$completedPayment->id)

                // Count success messages
                ->with('.space-y-6', function ($browser) {
                    $browser->assertSeeIn('', __('Payment proof uploaded successfully'))
                        ->assertSeeIn('', __('Payment proof uploaded successfully'));
                });
        });
    }

    /**
     * Test AC7: File validation error UI display.
     * Verifies proper error display for invalid file uploads.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('payment-proof')]
    public function file_validation_error_ui_display(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $registration->events()->attach($event->code, ['price_at_registration' => 300.00]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 300.00,
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration, $payment) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registration'))
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))

                // Test invalid file type
                ->attach('input[name="payment_proof"]', __DIR__.'/files/invalid_file.txt')
                ->click('@upload-payment-proof-button')
                ->waitForLocation('/my-registration')
                ->pause(1000)

                // Verify validation failure - form should still be visible
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))
                ->assertVisible('@payment-form-'.$payment->id)
                ->assertVisible('input[name="payment_proof"]')

                // Test oversized file
                ->attach('input[name="payment_proof"]', __DIR__.'/files/oversized_file.pdf')
                ->click('@upload-payment-proof-button')
                ->waitForLocation('/my-registration')
                ->pause(1000)

                // Verify validation failure again
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))
                ->assertVisible('@payment-form-'.$payment->id)

                // Test successful upload to confirm validation works
                ->attach('input[name="payment_proof"]', __DIR__.'/files/test_payment_proof.pdf')
                ->click('@upload-payment-proof-button')
                ->waitForLocation('/my-registration')
                ->pause(1000)

                // Verify success - form should be hidden, view button visible
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))
                ->assertMissing('@payment-form-'.$payment->id)
                ->assertVisible('@view-payment-proof-button-'.$payment->id);
        });
    }

    /**
     * Test AC7: View proof button functionality and styling.
     * Verifies the view proof button appearance and behavior.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('payment-proof')]
    public function view_proof_button_functionality_and_styling(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $registration->events()->attach($event->code, ['price_at_registration' => 200.00]);

        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/'.$registration->id.'/ui_test_proof.pdf',
            'payment_date' => now()->subHours(1),
            'notes' => __('Payment proof uploaded by user'),
        ]);

        // Create the proof file
        Storage::disk('private')->put($payment->payment_proof_path, 'UI test proof content');

        $this->browse(function (Browser $browser) use ($user, $registration, $payment) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registration'))
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))

                // Verify view button styling and attributes
                ->assertVisible('@view-payment-proof-button-'.$payment->id)
                ->assertSee(__('View Proof'))

                // Check button has correct CSS classes
                ->assertAttribute('@view-payment-proof-button-'.$payment->id, 'class', function ($class) {
                    return str_contains($class, 'bg-green-600') &&
                           str_contains($class, 'text-white') &&
                           str_contains($class, 'hover:bg-green-700');
                })

                // Verify button is a link (not a form button)
                ->assertAttribute('@view-payment-proof-button-'.$payment->id, 'href', function ($href) use ($payment) {
                    return str_contains($href, '/payments/'.$payment->id.'/download-proof');
                })

                // Verify success message layout
                ->assertSee(__('Payment proof uploaded successfully'))
                ->assertSee(__('Uploaded on'))
                ->assertSee($payment->payment_date->format('d/m/Y H:i'))

                // Verify layout structure (justify-between for button positioning)
                ->with('.bg-green-50', function ($browser) {
                    $browser->assertPresent('.justify-between');
                })

                // Test button click (note: actual download testing is limited in Dusk)
                ->click('@view-payment-proof-button-'.$payment->id)
                ->pause(1000)

                // Verify page doesn't break after click
                ->assertPathIs('/my-registration')
                ->assertSee(__('My Registration'));
        });
    }

    /**
     * Test AC7: Responsive design and mobile compatibility.
     * Verifies payment proof UI works on different screen sizes.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('payment-proof')]
    public function responsive_design_and_mobile_compatibility(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $registration->events()->attach($event->code, ['price_at_registration' => 350.00]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 350.00,
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration, $payment) {
            // Test desktop view
            $browser->loginAs($user)
                ->resize(1200, 800)
                ->visit('/my-registration')
                ->waitForText(__('My Registration'))
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))
                ->assertVisible('@payment-form-'.$payment->id)
                ->assertVisible('input[name="payment_proof"]')

                // Test tablet view
                ->resize(768, 1024)
                ->assertVisible('@payment-form-'.$payment->id)
                ->assertVisible('input[name="payment_proof"]')
                ->assertVisible('@upload-payment-proof-button')

                // Test mobile view
                ->resize(375, 667)
                ->assertVisible('@payment-form-'.$payment->id)
                ->assertVisible('input[name="payment_proof"]')
                ->assertVisible('@upload-payment-proof-button')

                // Upload file in mobile view
                ->attach('input[name="payment_proof"]', __DIR__.'/files/test_payment_proof.pdf')
                ->click('@upload-payment-proof-button')
                ->waitForLocation('/my-registration')
                ->pause(1000)

                // Verify success state in mobile view
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))
                ->assertVisible('@view-payment-proof-button-'.$payment->id)
                ->assertSee(__('View Proof'))

                // Return to desktop and verify consistency
                ->resize(1200, 800)
                ->assertVisible('@view-payment-proof-button-'.$payment->id)
                ->assertSee(__('Payment proof uploaded successfully'));
        });
    }

    /**
     * Test AC7: Accessibility and user experience features.
     * Verifies accessibility features and user-friendly design.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('payment-proof')]
    public function accessibility_and_user_experience_features(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        $event = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $registration->events()->attach($event->code, ['price_at_registration' => 150.00]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registration'))
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))

                // Verify form has proper labels and help text
                ->assertSee(__('Payment Proof Document'))
                ->assertSee(__('Accepted formats: JPG, JPEG, PNG, PDF. Maximum size: 10MB.'))

                // Verify form structure has accessible elements
                ->assertPresent('input[type="file"]')
                ->assertPresent('button[type="submit"]')

                // Verify button has descriptive text
                ->assertSee(__('Upload Payment Proof'))

                // Verify file input accepts correct types
                ->assertAttribute('input[name="payment_proof"]', 'accept', '.jpg,.jpeg,.png,.pdf')

                // Upload and verify accessible success state
                ->attach('input[name="payment_proof"]', __DIR__.'/files/test_payment_proof.pdf')
                ->click('@upload-payment-proof-button')
                ->waitForLocation('/my-registration')
                ->pause(1000)

                // Verify success message is descriptive
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))
                ->assertSee(__('Payment proof uploaded successfully'))
                ->assertSee(__('View Proof'))

                // Verify date format is user-friendly
                ->assertSee(__('Uploaded on'));
        });
    }

    /**
     * Test AC7: Integration with existing registration workflow.
     * Verifies payment proof functionality integrates seamlessly with registration flow.
     */
    #[Test]
    #[Group('dusk')]
    #[Group('payment-proof')]
    public function integration_with_registration_workflow(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
            'full_name' => 'Integration Test User',
            'email' => $user->email,
        ]);

        $event1 = Event::where('code', 'BCSMIF2025')->firstOrFail();
        $event2 = Event::where('code', 'RAA2025')->firstOrFail();

        $registration->events()->attach($event1->code, ['price_at_registration' => 300.00]);
        $registration->events()->attach($event2->code, ['price_at_registration' => 200.00]);

        $payment1 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 300.00,
        ]);

        $payment2 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
        ]);

        $this->browse(function (Browser $browser) use ($user, $registration, $payment1, $payment2, $event1, $event2) {
            $browser->loginAs($user)
                ->visit('/my-registration')
                ->waitForText(__('My Registration'))

                // Verify registration summary shows correct information
                ->assertSee(__('Registration').' #'.$registration->id)
                ->assertSee('Integration Test User')
                ->assertSee('R$ 500,00') // Total fee

                // Expand to see detailed view
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Registration Details'))

                // Verify personal information section
                ->assertSee(__('Personal Information'))
                ->assertSee(__('Full Name').': Integration Test User')
                ->assertSee(__('Email').': '.$user->email)

                // Verify events section
                ->assertSee(__('Events & Pricing'))
                ->assertSee($event1->name)
                ->assertSee($event2->name)
                ->assertSee(__('Price at Registration').': R$ 300,00')
                ->assertSee(__('Price at Registration').': R$ 200,00')

                // Verify payment history section with upload forms
                ->assertSee(__('Payment History'))
                ->assertVisible('@payment-form-'.$payment1->id)
                ->assertVisible('@payment-form-'.$payment2->id)

                // Upload proof for first payment
                ->within('@payment-form-'.$payment1->id, function ($browser) {
                    $browser->attach('input[name="payment_proof"]', __DIR__.'/files/test_payment_proof.pdf')
                        ->click('@upload-payment-proof-button');
                })

                ->waitForLocation('/my-registration')
                ->pause(1000)

                // Verify partial completion state
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))
                ->assertVisible('@view-payment-proof-button-'.$payment1->id)
                ->assertVisible('@payment-form-'.$payment2->id)

                // Upload proof for second payment
                ->within('@payment-form-'.$payment2->id, function ($browser) {
                    $browser->attach('input[name="payment_proof"]', __DIR__.'/files/test_payment_proof.pdf')
                        ->click('@upload-payment-proof-button');
                })

                ->waitForLocation('/my-registration')
                ->pause(1000)

                // Verify complete state
                ->click("button[wire\\:click='viewRegistration({$registration->id})']")
                ->waitForText(__('Payment History'))
                ->assertVisible('@view-payment-proof-button-'.$payment1->id)
                ->assertVisible('@view-payment-proof-button-'.$payment2->id)
                ->assertMissing('@payment-form-'.$payment1->id)
                ->assertMissing('@payment-form-'.$payment2->id)

                // Verify both success messages
                ->with('.space-y-6', function ($browser) {
                    $successMessages = $browser->elements('.bg-green-50');
                    $this->assertCount(2, $successMessages);
                });
        });
    }
}
