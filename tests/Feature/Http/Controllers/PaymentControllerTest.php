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
}
