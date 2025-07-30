<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentUploadFormHidingAfterModificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that upload form for older payment is hidden when a newer pending payment exists
     * after registration modification (adding new events).
     *
     * This addresses the user story: when a user registers for a workshop and later adds
     * a new event, the first upload form should not appear to avoid confusion.
     */
    public function test_older_payment_upload_form_hidden_when_newer_pending_payment_exists(): void
    {
        // Arrange: Create test data simulating the registration modification scenario
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brazil',
        ]);

        // Create first payment (workshop registration)
        $originalPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
            'created_at' => now()->subHours(2), // Older payment
        ]);

        // Create second payment (added event after modification)
        $modificationPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'created_at' => now()->subHour(1), // Newer payment
        ]);

        // Act: Visit the my-registrations page
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Assert: Verify that only the newer payment shows upload form
        // Original payment form should be HIDDEN (key requirement)
        $this->assertStringNotContainsString('payments/'.$originalPayment->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$originalPayment->id, $content);

        // Newer payment form should be VISIBLE
        $this->assertStringContainsString('payments/'.$modificationPayment->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$modificationPayment->id, $content);

        // Verify exactly one upload form is present
        $uploadFormCount = substr_count($content, __('Payment Proof Upload'));
        $this->assertEquals(1, $uploadFormCount, 'Only one upload form should be visible (for the newest payment)');
    }

    /**
     * Test that download functionality is preserved for older payments with uploaded proofs
     * even when newer pending payments exist.
     */
    public function test_download_preserved_for_older_payments_with_proof_when_newer_pending_exists(): void
    {
        // Arrange: Create test scenario with older payment having proof and newer pending payment
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brazil',
        ]);

        // Create first payment with proof already uploaded
        $paymentWithProof = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
            'status' => 'pending_br_proof_approval',
            'payment_proof_path' => 'proofs/123/payment_proof.pdf',
            'payment_date' => now()->subHours(3),
            'created_at' => now()->subHours(4), // Older payment
        ]);

        // Create second payment (newer, without proof)
        $newerPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
            'created_at' => now()->subHour(1), // Newer payment
        ]);

        // Act: Visit the my-registrations page
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Assert: Verify older payment shows download but no upload form
        // Download button should be present
        $this->assertStringContainsString('payments/'.$paymentWithProof->id.'/download-proof', $content);
        $this->assertStringContainsString(__('View Proof'), $content);
        $this->assertStringContainsString(__('Payment proof uploaded successfully'), $content);

        // Upload form should NOT be present for older payment
        $this->assertStringNotContainsString('payments/'.$paymentWithProof->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$paymentWithProof->id, $content);

        // Newer payment should show upload form
        $this->assertStringContainsString('payments/'.$newerPayment->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$newerPayment->id, $content);
    }

    /**
     * Test the complete workflow: original registration -> add events -> only newest form visible.
     * This simulates the exact user story described in the issue.
     */
    public function test_complete_modification_workflow_hides_older_upload_forms(): void
    {
        // Arrange: Create user and initial registration
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brazil',
        ]);

        // STEP 1: Initial payment (workshop registration)
        $workshopPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 75.00,
            'created_at' => now()->subHours(3),
        ]);

        // STEP 2: Verify initial upload form is visible
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        $this->assertStringContainsString('payments/'.$workshopPayment->id.'/upload-proof', $content);
        $this->assertEquals(1, substr_count($content, __('Payment Proof Upload')));

        // STEP 3: Simulate modification - adding new event (creates second payment)
        $additionalPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
            'created_at' => now()->subHour(1), // More recent
        ]);

        // STEP 4: Verify behavior after modification
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Original workshop payment form should be HIDDEN
        $this->assertStringNotContainsString('payments/'.$workshopPayment->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$workshopPayment->id, $content);

        // New payment form should be VISIBLE
        $this->assertStringContainsString('payments/'.$additionalPayment->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$additionalPayment->id, $content);

        // Only one upload form should be present
        $this->assertEquals(1, substr_count($content, __('Payment Proof Upload')));

        // STEP 5: Test upload to newer payment works correctly
        $file = UploadedFile::fake()->create('combined_payment_proof.pdf', 100, 'application/pdf');

        $uploadResponse = $this->actingAs($user)
            ->post(route('payments.upload-proof', $additionalPayment), [
                'payment_proof' => $file,
            ]);

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success');

        // Verify upload worked
        $additionalPayment->refresh();
        $this->assertNotNull($additionalPayment->payment_proof_path);
    }

    /**
     * Test that when there are multiple modifications, only the most recent payment shows upload form.
     */
    public function test_multiple_modifications_only_newest_shows_upload_form(): void
    {
        // Arrange: Create registration with multiple payments over time
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brazil',
        ]);

        // Create three payments at different times (simulating multiple modifications)
        $payment1 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 50.00,
            'created_at' => now()->subHours(5), // Oldest
        ]);

        $payment2 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
            'created_at' => now()->subHours(3), // Middle
        ]);

        $payment3 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 75.00,
            'created_at' => now()->subHour(1), // Newest
        ]);

        // Act: Visit the page
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Assert: Only the newest payment should show upload form
        $this->assertStringNotContainsString('payments/'.$payment1->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$payment1->id, $content);

        $this->assertStringNotContainsString('payments/'.$payment2->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$payment2->id, $content);

        $this->assertStringContainsString('payments/'.$payment3->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$payment3->id, $content);

        // Exactly one upload form should be present
        $this->assertEquals(1, substr_count($content, __('Payment Proof Upload')));
    }

    /**
     * Test the specific bug reported: first form should stay hidden even after upload on second payment.
     * This addresses the user story where uploading proof on newer payment changes its status to
     * 'pending_br_proof_approval', but the older payment form should still remain hidden.
     */
    public function test_first_form_stays_hidden_after_upload_on_second_payment(): void
    {
        // Arrange: Create scenario that matches user's reported issue
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brazil',
        ]);

        // STEP 1: User registers for workshop (first payment)
        $workshopPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 75.00,
            'created_at' => now()->subHours(2), // Older payment
        ]);

        // STEP 2: User adds another workshop (modification creates second payment)
        $modificationPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'created_at' => now()->subHour(1), // Newer payment
        ]);

        // STEP 3: Verify first form is hidden before upload (original behavior should work)
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        $this->assertStringNotContainsString('payments/'.$workshopPayment->id.'/upload-proof', $content);
        $this->assertStringContainsString('payments/'.$modificationPayment->id.'/upload-proof', $content);

        // STEP 4: User uploads proof on second payment (THIS IS WHERE THE BUG HAPPENED)
        $file = UploadedFile::fake()->create('combined_payment_proof.pdf', 100, 'application/pdf');

        $uploadResponse = $this->actingAs($user)
            ->post(route('payments.upload-proof', $modificationPayment), [
                'payment_proof' => $file,
            ]);

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success');

        // Verify the payment status changed to pending_br_proof_approval
        $modificationPayment->refresh();
        $this->assertEquals('pending_br_proof_approval', $modificationPayment->status);
        $this->assertNotNull($modificationPayment->payment_proof_path);

        // STEP 5: CRITICAL TEST - First form should STILL be hidden (bug fix verification)
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // BUG FIX VERIFICATION: First payment form should STILL be hidden
        $this->assertStringNotContainsString('payments/'.$workshopPayment->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$workshopPayment->id, $content);

        // Second payment should show success message (no upload form anymore)
        $this->assertStringNotContainsString('payments/'.$modificationPayment->id.'/upload-proof', $content);
        $this->assertStringContainsString(__('Payment proof uploaded successfully'), $content);

        // No upload forms should be visible now
        $this->assertEquals(0, substr_count($content, __('Payment Proof Upload')));
    }

    /**
     * Test that approved payments don't show upload forms regardless of creation time.
     */
    public function test_approved_payments_never_show_upload_form(): void
    {
        // Arrange: Create mix of approved and pending payments
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brazil',
        ]);

        // Old approved payment
        $approvedPayment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
            'status' => 'approved',
            'created_at' => now()->subHours(5),
        ]);

        // Newer pending payment
        $pendingPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'created_at' => now()->subHour(1),
        ]);

        // Act: Visit the page
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Assert: Approved payment should not show upload form
        $this->assertStringNotContainsString('payments/'.$approvedPayment->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$approvedPayment->id, $content);

        // Pending payment should show upload form
        $this->assertStringContainsString('payments/'.$pendingPayment->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$pendingPayment->id, $content);

        // Only one upload form
        $this->assertEquals(1, substr_count($content, __('Payment Proof Upload')));
    }
}
