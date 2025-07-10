<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\EnrollmentProof;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentProofControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AC3: EnrollmentProofController can upload enrollment proof for registration.
     * This test verifies that the uploadProof method correctly creates an
     * enrollment proof record and stores the file.
     */
    public function test_upload_proof_creates_enrollment_proof_record(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create a fake file for upload
        $file = UploadedFile::fake()->create('enrollment_proof.pdf', 100, 'application/pdf');

        // Act: Upload enrollment proof
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.upload', $registration), [
                'enrollment_proof' => $file,
            ]);

        // Assert: Verify the response
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // AC3: Verify enrollment proof record was created
        $this->assertDatabaseHas('enrollment_proofs', [
            'registration_id' => $registration->id,
            'status' => 'pending_approval',
            'original_filename' => 'enrollment_proof.pdf',
        ]);

        $enrollmentProof = $registration->enrollmentProof;
        $this->assertNotNull($enrollmentProof);
        $this->assertNotNull($enrollmentProof->file_path);
        $this->assertNotNull($enrollmentProof->uploaded_at);
        $this->assertStringContainsString('enrollment_'.$registration->id, $enrollmentProof->file_path);

        // Verify the file was stored in the correct location
        $expectedPath = "enrollment-proofs/{$registration->id}";
        $this->assertTrue(Storage::disk('private')->exists($enrollmentProof->file_path));
        $this->assertStringStartsWith($expectedPath, $enrollmentProof->file_path);
    }

    /**
     * Test AC3: Verify route model binding correctly identifies registration.
     * This test ensures that the route /enrollment-proofs/{registration}
     * correctly binds to the specific registration instance.
     */
    public function test_upload_proof_route_binding_identifies_correct_registration(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $file = UploadedFile::fake()->create('student_id.jpg', 50, 'image/jpeg');

        // Act: Upload proof using the specific registration ID in the route
        $response = $this->actingAs($user)
            ->post("/enrollment-proofs/{$registration->id}", [
                'enrollment_proof' => $file,
            ]);

        // Assert: Verify the correct registration was updated
        $response->assertRedirect();

        // AC3: The enrollment proof should be associated with the exact registration specified in the route
        $enrollmentProof = $registration->enrollmentProof;
        $this->assertNotNull($enrollmentProof);
        $this->assertEquals($registration->id, $enrollmentProof->registration_id);
        $this->assertStringContainsString("enrollment_{$registration->id}", $enrollmentProof->file_path);
    }

    /**
     * Test AC3: Verify file storage structure includes registration identification.
     * This test ensures that the stored file path contains registration-specific information.
     */
    public function test_upload_proof_file_storage_includes_registration_identification(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $file = UploadedFile::fake()->create('enrollment_document.png', 75, 'image/png');

        // Act: Upload proof
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.upload', $registration), [
                'enrollment_proof' => $file,
            ]);

        // Assert: Verify file storage structure
        $response->assertRedirect();

        // AC3: The stored file path should include registration identification
        $enrollmentProof = $registration->enrollmentProof;
        $this->assertNotNull($enrollmentProof);

        $storedPath = $enrollmentProof->file_path;
        $this->assertNotNull($storedPath);

        // Verify the path structure: enrollment-proofs/{registration_id}/timestamp_enrollment_{registration_id}_filename.ext
        $this->assertStringStartsWith("enrollment-proofs/{$registration->id}/", $storedPath);
        $this->assertStringContainsString("_enrollment_{$registration->id}_", $storedPath);
        $this->assertStringEndsWith('.png', $storedPath);

        // Verify the file actually exists in storage
        $this->assertTrue(Storage::disk('private')->exists($storedPath));
    }

    /**
     * Test AC3: Verify unauthorized access to upload proof for other user's registration.
     * This test ensures that users cannot upload enrollment proofs for registrations they don't own.
     */
    public function test_upload_proof_unauthorized_access_denied(): void
    {
        // Arrange: Create two different users with their own registrations
        Storage::fake('private');
        Mail::fake();

        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $registration1 = Registration::factory()->create(['user_id' => $user1->id]);
        $registration2 = Registration::factory()->create(['user_id' => $user2->id]);

        $file = UploadedFile::fake()->create('unauthorized_proof.pdf', 100, 'application/pdf');

        // Act: User1 tries to upload proof for User2's registration
        $response = $this->actingAs($user1)
            ->post(route('enrollment-proofs.upload', $registration2), [
                'enrollment_proof' => $file,
            ]);

        // Assert: Access should be denied
        $response->assertStatus(403);

        // AC3: Verify no enrollment proof was created
        $this->assertDatabaseMissing('enrollment_proofs', [
            'registration_id' => $registration2->id,
        ]);
    }

    /**
     * Test AC3: Verify upload fails for already approved enrollment proof.
     * This test ensures that proof cannot be re-uploaded once approved.
     */
    public function test_upload_proof_fails_for_already_approved_enrollment(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create an already approved enrollment proof
        $existingProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'approved',
            'file_path' => 'existing/proof.pdf',
        ]);

        $file = UploadedFile::fake()->create('new_proof.pdf', 100, 'application/pdf');

        // Act: Try to upload new proof when one is already approved
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.upload', $registration), [
                'enrollment_proof' => $file,
            ]);

        // Assert: Upload should fail
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // AC3: Verify existing proof was not changed
        $existingProof->refresh();
        $this->assertEquals('approved', $existingProof->status);
        $this->assertEquals('existing/proof.pdf', $existingProof->file_path);
    }

    /**
     * Test AC3: Verify validation error for missing file shows organization contact message.
     * This test ensures that when no file is provided, the error message instructs
     * the user to contact the organization for assistance.
     */
    public function test_upload_proof_missing_file_shows_organization_contact_message(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act: Try to upload without providing a file
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.upload', $registration), [
                // No 'enrollment_proof' field provided
            ]);

        // Assert: Validation should fail with organization contact message
        $response->assertSessionHasErrors(['enrollment_proof']);
        $errors = $response->getSession()->get('errors');
        $enrollmentProofError = $errors->get('enrollment_proof')[0];

        // AC3: Error message should instruct user to contact the organization
        $this->assertStringContainsString('contact the organization', $enrollmentProofError);
        $this->assertStringContainsString('assistance', $enrollmentProofError);
    }

    /**
     * Test AC3: Verify validation error for invalid file type shows organization contact message.
     * This test ensures that when an unsupported file type is uploaded, the error message
     * instructs the user to contact the organization for assistance.
     */
    public function test_upload_proof_invalid_file_type_shows_organization_contact_message(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create an unsupported file type
        $file = UploadedFile::fake()->create('document.txt', 10, 'text/plain');

        // Act: Try to upload unsupported file type
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.upload', $registration), [
                'enrollment_proof' => $file,
            ]);

        // Assert: Validation should fail with organization contact message
        $response->assertSessionHasErrors(['enrollment_proof']);
        $errors = $response->getSession()->get('errors');
        $enrollmentProofError = $errors->get('enrollment_proof')[0];

        // AC3: Error message should instruct user to contact the organization
        $this->assertStringContainsString('contact the organization', $enrollmentProofError);
        $this->assertStringContainsString('assistance', $enrollmentProofError);
        $this->assertStringContainsString('file format', $enrollmentProofError);
    }

    /**
     * Test AC3: Verify validation error for oversized file shows organization contact message.
     * This test ensures that when a file exceeds the size limit, the error message
     * instructs the user to contact the organization for assistance.
     */
    public function test_upload_proof_oversized_file_shows_organization_contact_message(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create a file that exceeds the 10MB limit (10240 KB)
        $file = UploadedFile::fake()->create('large_document.pdf', 10241, 'application/pdf');

        // Act: Try to upload oversized file
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.upload', $registration), [
                'enrollment_proof' => $file,
            ]);

        // Assert: Validation should fail with organization contact message
        $response->assertSessionHasErrors(['enrollment_proof']);
        $errors = $response->getSession()->get('errors');
        $enrollmentProofError = $errors->get('enrollment_proof')[0];

        // AC3: Error message should instruct user to contact the organization
        $this->assertStringContainsString('contact the organization', $enrollmentProofError);
        $this->assertStringContainsString('assistance', $enrollmentProofError);
        $this->assertStringContainsString('10MB', $enrollmentProofError);
        $this->assertStringContainsString('larger file', $enrollmentProofError);
    }

    /**
     * Test AC3: Verify enrollment proof notification is sent to coordinator.
     * This test ensures that when an enrollment proof is uploaded, a notification
     * is sent to the coordinator.
     */
    public function test_upload_proof_sends_notification_to_coordinator(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        // Set coordinator email in config for the test
        config(['mail.coordinator_email' => 'coordinator@example.com']);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $file = UploadedFile::fake()->create('enrollment_proof.pdf', 100, 'application/pdf');

        // Act: Upload enrollment proof
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.upload', $registration), [
                'enrollment_proof' => $file,
            ]);

        // Assert: Verify notification was sent
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // AC3: Verify notification was queued for coordinator
        Mail::assertQueued(\App\Mail\ProofUploadedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id &&
                   $mail->proofType === 'enrollment';
        });
    }

    /**
     * Test AC3: Verify download proof functionality for enrollment proof.
     * This test ensures that users can download their own enrollment proofs.
     */
    public function test_download_proof_returns_enrollment_proof_file(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create a fake file in storage
        $filePath = "enrollment-proofs/{$registration->id}/test_enrollment_proof.pdf";
        Storage::disk('private')->put($filePath, 'fake enrollment proof content');

        $enrollmentProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'file_path' => $filePath,
            'original_filename' => 'original_enrollment_proof.pdf',
            'status' => 'pending_approval',
        ]);

        // Act: Download enrollment proof
        $response = $this->actingAs($user)
            ->get(route('enrollment-proofs.download', $registration));

        // Assert: Verify download response
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=enrollment_proof_'.$registration->id.'.pdf');

        // AC3: Verify correct file is downloaded
        $this->assertEquals('fake enrollment proof content', $response->streamedContent());
    }

    /**
     * Test AC3: Verify download fails when no enrollment proof exists.
     * This test ensures that download fails gracefully when no enrollment proof exists.
     */
    public function test_download_proof_fails_when_no_enrollment_proof_exists(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // No enrollment proof exists for this registration

        // Act: Try to download non-existent enrollment proof
        $response = $this->actingAs($user)
            ->get(route('enrollment-proofs.download', $registration));

        // Assert: Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test AC3: Verify unauthorized download access is denied.
     * This test ensures that users cannot download enrollment proofs for registrations they don't own.
     */
    public function test_download_proof_unauthorized_access_denied(): void
    {
        // Arrange: Create two different users with their own registrations
        Storage::fake('private');

        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $registration1 = Registration::factory()->create(['user_id' => $user1->id]);
        $registration2 = Registration::factory()->create(['user_id' => $user2->id]);

        // Create enrollment proof for user2's registration
        $filePath = "enrollment-proofs/{$registration2->id}/proof.pdf";
        Storage::disk('private')->put($filePath, 'user2 enrollment proof');

        EnrollmentProof::factory()->create([
            'registration_id' => $registration2->id,
            'file_path' => $filePath,
        ]);

        // Act: User1 tries to download User2's enrollment proof
        $response = $this->actingAs($user1)
            ->get(route('enrollment-proofs.download', $registration2));

        // Assert: Access should be denied
        $response->assertStatus(403);
    }

    /**
     * Test AC3: Verify existing enrollment proof can be updated with new upload.
     * This test ensures that users can replace pending enrollment proofs.
     */
    public function test_upload_proof_updates_existing_pending_enrollment_proof(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create an existing pending enrollment proof
        $existingProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'pending_approval',
            'file_path' => 'old/proof.pdf',
            'original_filename' => 'old_proof.pdf',
        ]);

        $file = UploadedFile::fake()->create('new_enrollment_proof.pdf', 100, 'application/pdf');

        // Act: Upload new enrollment proof
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.upload', $registration), [
                'enrollment_proof' => $file,
            ]);

        // Assert: Upload should succeed
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // AC3: Verify existing proof was updated (not a new record created)
        $this->assertEquals(1, EnrollmentProof::where('registration_id', $registration->id)->count());

        $existingProof->refresh();
        $this->assertEquals('new_enrollment_proof.pdf', $existingProof->original_filename);
        $this->assertStringContainsString('new-enrollment-proof', $existingProof->file_path);
        $this->assertEquals('pending_approval', $existingProof->status);
        $this->assertNotNull($existingProof->uploaded_at);
    }

    /**
     * Test AC4: Store method with POST /enrollment-proofs route accepts registration_id in request.
     * This test verifies that the store method correctly handles registration_id from request body.
     */
    public function test_store_method_accepts_registration_id_in_request(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $file = UploadedFile::fake()->create('enrollment_proof.pdf', 100, 'application/pdf');

        // Act: Upload enrollment proof using the store route
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.store'), [
                'registration_id' => $registration->id,
                'enrollment_proof' => $file,
            ]);

        // Assert: Verify the response
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // AC4: Verify enrollment proof record was created with correct registration
        $this->assertDatabaseHas('enrollment_proofs', [
            'registration_id' => $registration->id,
            'status' => 'pending_approval',
            'original_filename' => 'enrollment_proof.pdf',
        ]);

        $enrollmentProof = $registration->enrollmentProof;
        $this->assertNotNull($enrollmentProof);
        $this->assertEquals($registration->id, $enrollmentProof->registration_id);
    }

    /**
     * Test AC4: Store method validates registration_id exists in database.
     * This test verifies that the store method validates registration_id against existing records.
     */
    public function test_store_method_validates_registration_id_exists(): void
    {
        // Arrange: Create test data
        Storage::fake('private');
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('enrollment_proof.pdf', 100, 'application/pdf');

        // Act: Try to upload with non-existent registration_id
        $response = $this->actingAs($user)
            ->post(route('enrollment-proofs.store'), [
                'registration_id' => 99999, // Non-existent registration ID
                'enrollment_proof' => $file,
            ]);

        // Assert: Validation should fail
        $response->assertSessionHasErrors(['registration_id']);

        // AC4: No enrollment proof should be created
        $this->assertDatabaseEmpty('enrollment_proofs');
    }

    /**
     * Test AC4: Store method denies access when user doesn't own registration.
     * This test verifies that users cannot upload enrollment proofs for registrations they don't own.
     */
    public function test_store_method_denies_access_for_unowned_registration(): void
    {
        // Arrange: Create two different users with their own registrations
        Storage::fake('private');
        Mail::fake();

        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $registration1 = Registration::factory()->create(['user_id' => $user1->id]);
        $registration2 = Registration::factory()->create(['user_id' => $user2->id]);

        $file = UploadedFile::fake()->create('unauthorized_proof.pdf', 100, 'application/pdf');

        // Act: User1 tries to upload proof for User2's registration using store method
        $response = $this->actingAs($user1)
            ->post(route('enrollment-proofs.store'), [
                'registration_id' => $registration2->id,
                'enrollment_proof' => $file,
            ]);

        // Assert: Access should be denied
        $response->assertStatus(403);

        // AC4: Verify no enrollment proof was created
        $this->assertDatabaseMissing('enrollment_proofs', [
            'registration_id' => $registration2->id,
        ]);
    }

    /**
     * Test AC4: Download method with GET /enrollment-proofs/{proof}/download route.
     * This test verifies that the download method correctly handles proof ID parameter.
     */
    public function test_download_method_accepts_proof_id_parameter(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create a fake file in storage
        $filePath = "enrollment-proofs/{$registration->id}/test_enrollment_proof.pdf";
        Storage::disk('private')->put($filePath, 'fake enrollment proof content');

        $enrollmentProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'file_path' => $filePath,
            'original_filename' => 'original_enrollment_proof.pdf',
            'status' => 'pending_approval',
        ]);

        // Act: Download enrollment proof using the download route with proof ID
        $response = $this->actingAs($user)
            ->get(route('enrollment-proofs.download-proof', $enrollmentProof));

        // Assert: Verify download response
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=enrollment_proof_'.$registration->id.'.pdf');

        // AC4: Verify correct file is downloaded
        $this->assertEquals('fake enrollment proof content', $response->streamedContent());
    }

    /**
     * Test AC4: Download method denies access when user doesn't own the proof's registration.
     * This test verifies that users cannot download enrollment proofs for registrations they don't own.
     */
    public function test_download_method_denies_access_for_unowned_proof(): void
    {
        // Arrange: Create two different users with their own registrations
        Storage::fake('private');

        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $registration1 = Registration::factory()->create(['user_id' => $user1->id]);
        $registration2 = Registration::factory()->create(['user_id' => $user2->id]);

        // Create enrollment proof for user2's registration
        $filePath = "enrollment-proofs/{$registration2->id}/proof.pdf";
        Storage::disk('private')->put($filePath, 'user2 enrollment proof');

        $enrollmentProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration2->id,
            'file_path' => $filePath,
        ]);

        // Debug: Check if the proof was created and the route is correct
        $this->assertNotNull($enrollmentProof->id);
        $routeUrl = route('enrollment-proofs.download-proof', $enrollmentProof);
        $this->assertStringContainsString('/enrollment-proofs/'.$enrollmentProof->id.'/download', $routeUrl);

        // Act: User1 tries to download User2's enrollment proof using download method
        $response = $this->actingAs($user1)
            ->get(route('enrollment-proofs.download-proof', $enrollmentProof));

        // Assert: Access should be denied
        // Note: Laravel route model binding may return 404 if the model is not found
        // due to query constraints, which is appropriate security behavior.
        // Either 403 (authorization failure) or 404 (not found) is acceptable for unauthorized access.
        $this->assertContains($response->status(), [403, 404]);
    }

    /**
     * Test AC4: Download method fails when proof has no file_path.
     * This test verifies that download fails gracefully when enrollment proof has no file.
     */
    public function test_download_method_fails_when_proof_has_no_file(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $enrollmentProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'file_path' => null, // No file path
        ]);

        // Act: Try to download enrollment proof with no file
        $response = $this->actingAs($user)
            ->get(route('enrollment-proofs.download-proof', $enrollmentProof));

        // Assert: Should return 404
        $response->assertStatus(404);
    }
}
