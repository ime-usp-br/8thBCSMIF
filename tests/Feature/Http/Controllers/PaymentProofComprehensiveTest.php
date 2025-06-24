<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test AC7 (Issue #51): Comprehensive automated tests for payment proof functionality.
 *
 * This test class ensures complete coverage of payment proof management features
 * including upload, download, validation, authorization, and UI state management.
 */
class PaymentProofComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EventsTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'FeesTableSeeder']);
    }

    /**
     * Test AC7: Complete upload workflow integration.
     * Verifies the entire upload process from form submission to database updates.
     */
    public function test_complete_upload_workflow_integration(): void
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

        $file = UploadedFile::fake()->create('comprehensive_test_proof.pdf', 500, 'application/pdf');

        // Act: Complete upload workflow
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $file,
            ]);

        // Assert: Verify complete workflow
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify database updates
        $payment->refresh();
        $this->assertNotNull($payment->payment_proof_path);
        $this->assertNotNull($payment->payment_date);
        $this->assertEquals(__('Payment proof uploaded by user'), $payment->notes);

        // Verify file storage
        $this->assertTrue(Storage::disk('private')->exists($payment->payment_proof_path));
        $this->assertStringStartsWith("proofs/{$registration->id}/", $payment->payment_proof_path);
        $this->assertStringContainsString("_payment_{$payment->id}_", $payment->payment_proof_path);

        // Verify audit trail
        $this->assertInstanceOf(\Carbon\Carbon::class, $payment->payment_date);
        $this->assertTrue($payment->payment_date->greaterThan(now()->subMinute()));
    }

    /**
     * Test AC7: Multiple payment scenarios with different statuses.
     * Ensures the system correctly handles various payment states.
     */
    public function test_multiple_payment_scenarios(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        // Create payments in different states
        $pendingPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        $completedPayment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'status' => 'completed',
            'payment_proof_path' => null,
        ]);

        $approvedPayment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
            'status' => 'approved',
            'payment_proof_path' => null,
        ]);

        $file = UploadedFile::fake()->create('status_test.pdf', 100, 'application/pdf');

        // Test pending payment (should succeed)
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $pendingPayment), [
                'payment_proof' => $file,
            ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Test completed payment (should fail) - use fresh file for each attempt
        $file2 = UploadedFile::fake()->create('status_test2.pdf', 100, 'application/pdf');
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $completedPayment), [
                'payment_proof' => $file2,
            ]);
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Test approved payment (should fail) - use fresh file for each attempt
        $file3 = UploadedFile::fake()->create('status_test3.pdf', 100, 'application/pdf');
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $approvedPayment), [
                'payment_proof' => $file3,
            ]);
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify only pending payment was updated
        $pendingPayment->refresh();
        $completedPayment->refresh();
        $approvedPayment->refresh();

        $this->assertNotNull($pendingPayment->payment_proof_path);
        $this->assertNull($completedPayment->payment_proof_path);
        $this->assertNull($approvedPayment->payment_proof_path);
    }

    /**
     * Test AC7: File type validation with all supported formats.
     * Verifies that all specified file types are accepted.
     */
    public function test_all_supported_file_formats(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $supportedFormats = [
            ['jpg', 'image/jpeg'],
            ['jpeg', 'image/jpeg'],
            ['png', 'image/png'],
            ['pdf', 'application/pdf'],
        ];

        foreach ($supportedFormats as [$extension, $mimeType]) {
            $payment = Payment::factory()->pending()->create([
                'registration_id' => $registration->id,
                'amount' => 100.00,
            ]);

            $file = UploadedFile::fake()->create("test_file.{$extension}", 100, $mimeType);

            $response = $this->actingAs($user)
                ->post(route('payments.upload-proof', $payment), [
                    'payment_proof' => $file,
                ]);

            $response->assertRedirect();
            $response->assertSessionHas('success');

            $payment->refresh();
            $this->assertNotNull($payment->payment_proof_path);
            $this->assertStringEndsWith(".{$extension}", $payment->payment_proof_path);
        }
    }

    /**
     * Test AC7: Error handling for various failure scenarios.
     * Ensures proper error messages and system stability.
     */
    public function test_comprehensive_error_handling(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        // Test missing file
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), []);

        $response->assertSessionHasErrors(['payment_proof']);
        $errors = $response->getSession()->get('errors');
        $error = $errors->get('payment_proof')[0];
        $this->assertStringContainsString('contact the organization', $error);

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $invalidFile,
            ]);

        $response->assertSessionHasErrors(['payment_proof']);
        $errors = $response->getSession()->get('errors');
        $error = $errors->get('payment_proof')[0];
        $this->assertStringContainsString('contact the organization', $error);

        // Test oversized file
        $oversizedFile = UploadedFile::fake()->create('large_file.pdf', 10241, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $oversizedFile,
            ]);

        $response->assertSessionHasErrors(['payment_proof']);
        $errors = $response->getSession()->get('errors');
        $error = $errors->get('payment_proof')[0];
        $this->assertStringContainsString('contact the organization', $error);
    }

    /**
     * Test AC7: Download functionality with edge cases.
     * Comprehensive testing of payment proof download features.
     */
    public function test_comprehensive_download_functionality(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        // Test successful download
        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'payment_proof_path' => 'proofs/123/test_download.pdf',
        ]);

        $testContent = 'Test download content';
        Storage::disk('private')->put($payment->payment_proof_path, $testContent);

        $response = $this->actingAs($user)
            ->get(route('payments.download-proof', $payment));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('payment_proof_'.$payment->id, $response->headers->get('content-disposition'));

        // Test download with special characters in filename
        $payment2 = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
            'payment_proof_path' => 'proofs/123/test-file-with-special-chars.pdf',
        ]);

        Storage::disk('private')->put($payment2->payment_proof_path, 'Special chars content');

        $response = $this->actingAs($user)
            ->get(route('payments.download-proof', $payment2));

        $response->assertOk();
        $this->assertStringContainsString('payment_proof_'.$payment2->id, $response->headers->get('content-disposition'));
    }

    /**
     * Test AC7: User interface state management.
     * Verifies that UI properly reflects payment proof states.
     */
    public function test_ui_state_management(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        // Payment without proof (should show upload form)
        $paymentWithoutProof = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        // Payment with proof (should show view button)
        $paymentWithProof = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/123/existing_proof.pdf',
            'payment_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Verify upload form is shown for payment without proof
        $this->assertStringContainsString('payments/'.$paymentWithoutProof->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$paymentWithoutProof->id, $content);

        // Verify view button is shown for payment with proof
        $this->assertStringContainsString('payments/'.$paymentWithProof->id.'/download-proof', $content);
        $this->assertStringContainsString('view-payment-proof-button-'.$paymentWithProof->id, $content);

        // Verify proper status messages
        $this->assertStringContainsString(__('Payment proof uploaded successfully'), $content);
    }

    /**
     * Test AC7: Security and authorization comprehensive tests.
     * Ensures proper access control for all payment proof operations.
     */
    public function test_comprehensive_security_authorization(): void
    {
        Storage::fake('private');

        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $registration1 = Registration::factory()->create(['user_id' => $user1->id]);
        $registration2 = Registration::factory()->create(['user_id' => $user2->id]);

        $payment1 = Payment::factory()->pending()->create(['registration_id' => $registration1->id]);
        $payment2 = Payment::factory()->create([
            'registration_id' => $registration2->id,
            'payment_proof_path' => 'proofs/456/user2_proof.pdf',
        ]);

        Storage::disk('private')->put($payment2->payment_proof_path, 'User 2 content');

        $file = UploadedFile::fake()->create('unauthorized_upload.pdf', 100, 'application/pdf');

        // Test unauthorized upload
        $response = $this->actingAs($user1)
            ->post(route('payments.upload-proof', $payment2), [
                'payment_proof' => $file,
            ]);
        $response->assertStatus(403);

        // Test unauthorized download
        $response = $this->actingAs($user1)
            ->get(route('payments.download-proof', $payment2));
        $response->assertStatus(403);

        // Logout any previously authenticated user for this part of the test
        auth()->logout();

        // Test unauthenticated upload access
        $response = $this->post(route('payments.upload-proof', $payment1), [
            'payment_proof' => $file,
        ]);
        $response->assertRedirect(route('login.local'));

        // Test unauthenticated download access
        $response = $this->get(route('payments.download-proof', $payment2));
        $response->assertRedirect(route('login.local'));
    }

    /**
     * Test AC7: Localization and error message coverage.
     * Verifies all error messages are properly localized.
     */
    public function test_localization_and_error_messages(): void
    {
        // Test English error messages
        app()->setLocale('en');

        $requiredError = __('Payment proof file is required. Please contact the organization for assistance if you are unable to upload.');
        $this->assertStringContainsString('contact the organization', $requiredError);

        $mimeError = __('Payment proof must be a JPG, JPEG, PNG, or PDF file. Please contact the organization for assistance if your file format is not supported.');
        $this->assertStringContainsString('contact the organization', $mimeError);

        $sizeError = __('Payment proof file size must not exceed 10MB. Please contact the organization for assistance if you need to upload a larger file.');
        $this->assertStringContainsString('contact the organization', $sizeError);

        $serverError = __('Failed to upload payment proof. Please contact the organization for assistance.');
        $this->assertStringContainsString('contact the organization', $serverError);

        // Test Portuguese error messages
        app()->setLocale('pt_BR');

        $requiredErrorPt = __('Payment proof file is required. Please contact the organization for assistance if you are unable to upload.');
        $this->assertStringContainsString('organização', $requiredErrorPt);

        $mimeErrorPt = __('Payment proof must be a JPG, JPEG, PNG, or PDF file. Please contact the organization for assistance if your file format is not supported.');
        $this->assertStringContainsString('organização', $mimeErrorPt);

        $sizeErrorPt = __('Payment proof file size must not exceed 10MB. Please contact the organization for assistance if you need to upload a larger file.');
        $this->assertStringContainsString('organização', $sizeErrorPt);

        $serverErrorPt = __('Failed to upload payment proof. Please contact the organization for assistance.');
        $this->assertStringContainsString('organização', $serverErrorPt);
    }

    /**
     * Test AC7: Performance and storage management.
     * Ensures efficient file handling and storage management.
     */
    public function test_performance_and_storage_management(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        // Test multiple uploads to same payment (should replace, not accumulate)
        $payment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        $file1 = UploadedFile::fake()->create('first_upload.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->create('second_upload.pdf', 100, 'application/pdf');

        // First upload
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $file1,
            ]);
        $response->assertRedirect();

        $payment->refresh();
        $firstPath = $payment->payment_proof_path;
        $this->assertNotNull($firstPath);

        // Second upload (should replace first)
        $response = $this->actingAs($user)
            ->post(route('payments.upload-proof', $payment), [
                'payment_proof' => $file2,
            ]);
        $response->assertRedirect();

        $payment->refresh();
        $secondPath = $payment->payment_proof_path;
        $this->assertNotNull($secondPath);
        $this->assertNotEquals($firstPath, $secondPath);

        // Verify file path structure is consistent
        $this->assertStringStartsWith("proofs/{$registration->id}/", $secondPath);
        $this->assertStringContainsString("_payment_{$payment->id}_", $secondPath);
        $this->assertTrue(Storage::disk('private')->exists($secondPath));
    }
}
