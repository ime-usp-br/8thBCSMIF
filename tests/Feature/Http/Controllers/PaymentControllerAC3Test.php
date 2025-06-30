<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentControllerAC3Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AC3 (Issue #51): After successful upload for a payment,
     * the corresponding upload form is hidden in the UI.
     *
     * This test specifically addresses AC3 requirements for Issue #51.
     */
    public function test_ac3_upload_form_hidden_after_successful_payment_specific_upload(): void
    {
        // Arrange: Create test data with multiple payments
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        // Create multiple payments for the same registration
        $payment1 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
        ]);

        $payment2 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        // Verify both forms are initially visible
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Both upload forms should be visible initially
        $this->assertStringContainsString('payments/'.$payment1->id.'/upload-proof', $content);
        $this->assertStringContainsString('payments/'.$payment2->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$payment1->id, $content);
        $this->assertStringContainsString('payment_proof_'.$payment2->id, $content);

        // Act: Upload proof for payment1 only
        $file = UploadedFile::fake()->create('payment_proof_1.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment1), [
                'payment_proof' => $file,
            ]);

        // Assert: Verify the response and database state
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify payment1 has proof uploaded
        $payment1->refresh();
        $payment2->refresh();

        $this->assertNotNull($payment1->payment_proof_path);
        $this->assertNull($payment2->payment_proof_path);

        // AC3 VERIFICATION: Check UI state after upload
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Payment1 form should be HIDDEN (AC3 requirement)
        $this->assertStringNotContainsString('payments/'.$payment1->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$payment1->id, $content);

        // Payment1 should show success confirmation instead
        $this->assertStringContainsString(__('Payment proof uploaded successfully'), $content);

        // Payment2 form should still be VISIBLE (different payment)
        $this->assertStringContainsString('payments/'.$payment2->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$payment2->id, $content);
        $this->assertStringContainsString(__('Payment Proof Upload'), $content);

        // Additional verification: Upload proof for payment2
        $file2 = UploadedFile::fake()->create('payment_proof_2.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment2), [
                'payment_proof' => $file2,
            ]);

        $response->assertRedirect();
        $payment2->refresh();
        $this->assertNotNull($payment2->payment_proof_path);

        // Final verification: Both forms should now be hidden
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        $this->assertStringNotContainsString('payments/'.$payment1->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payments/'.$payment2->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$payment1->id, $content);
        $this->assertStringNotContainsString('payment_proof_'.$payment2->id, $content);

        // Both should show success messages (but not flash messages)
        // Count only the specific payment confirmation messages (in green boxes), not the flash message
        $confirmationPattern = 'bg-green-50.*?Payment proof uploaded successfully';
        $confirmationCount = preg_match_all('/'.preg_quote('bg-green-50', '/').'.*?'.preg_quote(__('Payment proof uploaded successfully'), '/').'/s', $content);
        $this->assertEquals(2, $confirmationCount, 'Both payments should show success confirmation in their respective sections');

    }

    /**
     * Test AC3 (Issue #51): Verify that upload form hiding is payment-specific.
     * When one payment has proof uploaded, only that payment's form is hidden.
     *
     * This test specifically addresses the payment-specific aspect of AC3 for Issue #51.
     */
    public function test_ac3_upload_form_hiding_is_payment_specific(): void
    {
        // Arrange: Create test data with three payments in different states
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        // Payment with proof already uploaded
        $paymentWithProof = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/123/existing_proof.pdf',
            'payment_date' => now(),
        ]);

        // Payment without proof (should show form)
        $paymentWithoutProof = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
        ]);

        // Payment with different status (should not show form regardless)
        $approvedPayment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'status' => 'approved',
        ]);

        // Act & Assert: Verify UI state reflects payment-specific logic
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // AC3 VERIFICATION: Payment with proof should NOT show upload form
        $this->assertStringNotContainsString('payments/'.$paymentWithProof->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$paymentWithProof->id, $content);
        $this->assertStringContainsString(__('Payment proof uploaded successfully'), $content);

        // Payment without proof SHOULD show upload form
        $this->assertStringContainsString('payments/'.$paymentWithoutProof->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$paymentWithoutProof->id, $content);

        // Approved payment should NOT show upload form (status check)
        $this->assertStringNotContainsString('payments/'.$approvedPayment->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$approvedPayment->id, $content);

        // Verify that exactly one upload form is present (for the pending payment without proof)
        $uploadFormCount = substr_count($content, __('Payment Proof Upload'));
        $this->assertEquals(1, $uploadFormCount, 'Exactly one upload form should be visible for the pending payment without proof');
    }

    /**
     * Test AC3 (Issue #51): Verify the complete form hiding workflow.
     * Test the full cycle: form visible -> upload -> form hidden -> confirmation shown.
     *
     * This test specifically verifies the complete AC3 workflow for Issue #51.
     */
    public function test_ac3_complete_form_hiding_workflow(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 250.00,
        ]);

        // STEP 1: Verify upload form is initially visible
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        $this->assertStringContainsString('payments/'.$payment->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$payment->id, $content);
        $this->assertStringContainsString(__('Payment Proof Upload'), $content);
        $this->assertStringNotContainsString(__('Payment proof uploaded successfully'), $content);

        // STEP 2: Perform upload
        $file = UploadedFile::fake()->create('payment_proof.pdf', 100, 'application/pdf');

        $uploadResponse = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $file,
            ]);

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success');

        // STEP 3: Verify database state changed
        $payment->refresh();
        $this->assertNotNull($payment->payment_proof_path);
        $this->assertNotNull($payment->payment_date);

        // STEP 4: AC3 VERIFICATION - Verify form is now hidden and confirmation is shown
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Upload form should be hidden
        $this->assertStringNotContainsString('payments/'.$payment->id.'/upload-proof', $content);
        $this->assertStringNotContainsString('payment_proof_'.$payment->id, $content);
        $this->assertStringNotContainsString(__('Upload Payment Proof'), $content);

        // Success confirmation should be shown instead
        $this->assertStringContainsString(__('Payment proof uploaded successfully'), $content);
        $this->assertStringContainsString(__('Uploaded on'), $content);

        // Verify confirmation styling
        $this->assertStringContainsString('bg-green-50', $content);
        $this->assertStringContainsString('text-green-800', $content);
    }
}
