<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AC2: Backend logic associates proof file with correct payment record.
     * This test verifies that the uploadProof method correctly updates the
     * specific payment record with the payment_proof_path.
     */
    public function test_upload_proof_associates_file_with_correct_payment_record(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create multiple payments for the same registration
        $payment1 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        $payment2 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
        ]);

        // Create a fake file for upload
        $file = UploadedFile::fake()->create('payment_proof.pdf', 100, 'application/pdf');

        // Act: Upload proof for the first payment specifically
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment1), [
                'payment_proof' => $file,
            ]);

        // Assert: Verify the response
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // AC2: Verify the file is associated with the CORRECT payment record
        $payment1->refresh();
        $payment2->refresh();

        // The uploaded file should be associated with payment1 only
        $this->assertNotNull($payment1->payment_proof_path);
        $this->assertStringContainsString('payment_'.$payment1->id, $payment1->payment_proof_path);
        $this->assertNotNull($payment1->payment_date);
        $this->assertEquals(__('Payment proof uploaded by user'), $payment1->notes);

        // Payment2 should remain unchanged
        $this->assertNull($payment2->payment_proof_path);
        $this->assertNull($payment2->payment_date);
        $this->assertNull($payment2->notes);

        // Verify the file was stored in the correct location
        $expectedPath = "proofs/{$registration->id}";
        $this->assertTrue(Storage::disk('private')->exists($payment1->payment_proof_path));
        $this->assertStringStartsWith($expectedPath, $payment1->payment_proof_path);
    }

    /**
     * Test AC2: Verify route model binding correctly identifies payment.
     * This test ensures that the route /payments/{payment}/upload-proof
     * correctly binds to the specific payment instance.
     */
    public function test_upload_proof_route_binding_identifies_correct_payment(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
        ]);

        $file = UploadedFile::fake()->create('receipt.jpg', 50, 'image/jpeg');

        // Act: Upload proof using the specific payment ID in the route
        $response = $this->actingAs($user)
            ->post("/payments/{$payment->id}/upload-proof", [
                'payment_proof' => $file,
            ]);

        // Assert: Verify the correct payment was updated
        $response->assertRedirect();
        $payment->refresh();

        // AC2: The file should be associated with the exact payment specified in the route
        $this->assertNotNull($payment->payment_proof_path);
        $this->assertStringContainsString("payment_{$payment->id}", $payment->payment_proof_path);
    }

    /**
     * Test AC2: Verify file storage structure includes payment identification.
     * This test ensures that the stored file path contains payment-specific information.
     */
    public function test_upload_proof_file_storage_includes_payment_identification(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 300.00,
        ]);

        $file = UploadedFile::fake()->create('proof_document.png', 75, 'image/png');

        // Act: Upload proof
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $file,
            ]);

        // Assert: Verify file storage structure
        $response->assertRedirect();
        $payment->refresh();

        // AC2: The stored file path should include payment identification
        $storedPath = $payment->payment_proof_path;
        $this->assertNotNull($storedPath);

        // Verify the path structure: proofs/{registration_id}/timestamp_payment_{payment_id}_filename.ext
        $this->assertStringStartsWith("proofs/{$registration->id}/", $storedPath);
        $this->assertStringContainsString("_payment_{$payment->id}_", $storedPath);
        $this->assertStringEndsWith('.png', $storedPath);

        // Verify the file actually exists in storage
        $this->assertTrue(Storage::disk('private')->exists($storedPath));
    }

    /**
     * Test AC2: Verify unauthorized access to upload proof for other user's payment.
     * This test ensures that users cannot upload proofs for payments they don't own.
     */
    public function test_upload_proof_unauthorized_access_denied(): void
    {
        // Arrange: Create two different users with their own registrations
        Storage::fake('private');

        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $registration1 = Registration::factory()->create(['user_id' => $user1->id]);
        $registration2 = Registration::factory()->create(['user_id' => $user2->id]);

        $payment1 = Payment::factory()->pending()->create([
            'registration_id' => $registration1->id,
        ]);

        $payment2 = Payment::factory()->pending()->create([
            'registration_id' => $registration2->id,
        ]);

        $file = UploadedFile::fake()->create('unauthorized_proof.pdf', 100, 'application/pdf');

        // Act: User1 tries to upload proof for User2's payment
        $response = $this->actingAs($user1)
            ->post(route('payments.upload-proof', $payment2), [
                'payment_proof' => $file,
            ]);

        // Assert: Access should be denied
        $response->assertStatus(403);

        // AC2: Verify no file was associated with the payment
        $payment2->refresh();
        $this->assertNull($payment2->payment_proof_path);
        $this->assertNull($payment2->payment_date);
        $this->assertNull($payment2->notes);
    }

    /**
     * Test AC2: Verify upload fails for non-pending payments.
     * This test ensures that proof can only be uploaded for pending payments.
     */
    public function test_upload_proof_fails_for_non_pending_payments(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'completed', // Not pending
            'amount' => 100.00,
            'payment_proof_path' => null, // Ensure it starts as null
        ]);

        $file = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');

        // Act: Try to upload proof for completed payment
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $file,
            ]);

        // Assert: Upload should fail
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // AC2: Verify no file was associated with the payment
        $payment->refresh();
        $this->assertNull($payment->payment_proof_path);
    }

    /**
     * Test AC6: Verify validation error for missing file shows organization contact message.
     * This test ensures that when no file is provided, the error message instructs
     * the user to contact the organization for assistance.
     */
    public function test_upload_proof_missing_file_shows_organization_contact_message(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        // Act: Try to upload without providing a file
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                // No 'payment_proof' field provided
            ]);

        // Assert: Validation should fail with organization contact message
        $response->assertSessionHasErrors(['payment_proof']);
        $errors = $response->getSession()->get('errors');
        $paymentProofError = $errors->get('payment_proof')[0];
        
        // AC6: Error message should instruct user to contact the organization
        $this->assertStringContainsString('contact the organization', $paymentProofError);
        $this->assertStringContainsString('assistance', $paymentProofError);
    }

    /**
     * Test AC6: Verify validation error for invalid file type shows organization contact message.
     * This test ensures that when an unsupported file type is uploaded, the error message
     * instructs the user to contact the organization for assistance.
     */
    public function test_upload_proof_invalid_file_type_shows_organization_contact_message(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        // Create an unsupported file type
        $file = UploadedFile::fake()->create('document.txt', 10, 'text/plain');

        // Act: Try to upload unsupported file type
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $file,
            ]);

        // Assert: Validation should fail with organization contact message
        $response->assertSessionHasErrors(['payment_proof']);
        $errors = $response->getSession()->get('errors');
        $paymentProofError = $errors->get('payment_proof')[0];
        
        // AC6: Error message should instruct user to contact the organization
        $this->assertStringContainsString('contact the organization', $paymentProofError);
        $this->assertStringContainsString('assistance', $paymentProofError);
        $this->assertStringContainsString('file format', $paymentProofError);
    }

    /**
     * Test AC6: Verify validation error for oversized file shows organization contact message.
     * This test ensures that when a file exceeds the size limit, the error message
     * instructs the user to contact the organization for assistance.
     */
    public function test_upload_proof_oversized_file_shows_organization_contact_message(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        // Create a file that exceeds the 10MB limit (10240 KB)
        $file = UploadedFile::fake()->create('large_document.pdf', 10241, 'application/pdf');

        // Act: Try to upload oversized file
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $file,
            ]);

        // Assert: Validation should fail with organization contact message
        $response->assertSessionHasErrors(['payment_proof']);
        $errors = $response->getSession()->get('errors');
        $paymentProofError = $errors->get('payment_proof')[0];
        
        // AC6: Error message should instruct user to contact the organization
        $this->assertStringContainsString('contact the organization', $paymentProofError);
        $this->assertStringContainsString('assistance', $paymentProofError);
        $this->assertStringContainsString('10MB', $paymentProofError);
        $this->assertStringContainsString('larger file', $paymentProofError);
    }

    /**
     * Test AC6: Verify error message translations contain organization contact info.
     * This test ensures that all error message translations instruct 
     * the user to contact the organization for assistance.
     */
    public function test_upload_proof_error_messages_contain_organization_contact_info(): void
    {
        // Test English error messages
        $serverError = __('Failed to upload payment proof. Please contact the organization for assistance.');
        $this->assertStringContainsString('contact the organization', $serverError);
        $this->assertStringContainsString('assistance', $serverError);
        
        $requiredError = __('Payment proof file is required. Please contact the organization for assistance if you are unable to upload.');
        $this->assertStringContainsString('contact the organization', $requiredError);
        $this->assertStringContainsString('assistance', $requiredError);
        
        $mimeError = __('Payment proof must be a JPG, JPEG, PNG, or PDF file. Please contact the organization for assistance if your file format is not supported.');
        $this->assertStringContainsString('contact the organization', $mimeError);
        $this->assertStringContainsString('assistance', $mimeError);
        
        $sizeError = __('Payment proof file size must not exceed 10MB. Please contact the organization for assistance if you need to upload a larger file.');
        $this->assertStringContainsString('contact the organization', $sizeError);
        $this->assertStringContainsString('assistance', $sizeError);
        
        // Test Portuguese translations
        app()->setLocale('pt_BR');
        $serverErrorPt = __('Failed to upload payment proof. Please contact the organization for assistance.');
        $this->assertStringContainsString('organização', $serverErrorPt);
        $this->assertStringContainsString('assistência', $serverErrorPt);
    }
}