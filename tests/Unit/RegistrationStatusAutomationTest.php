<?php

namespace Tests\Unit;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationStatusAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_calculates_status_for_undergrad_student_based_on_enrollment_proof_only(): void
    {
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'registration_category_snapshot' => 'undergrad_student',
            'payment_status' => Registration::PAYMENT_STATUS_PENDING,
        ]);

        // Test without enrollment proof - should be pending
        $this->assertEquals(Registration::PAYMENT_STATUS_PENDING, $registration->calculateStatusFromRelatedModels());

        // Create enrollment proof with pending_approval status
        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);

        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_PENDING_APPROVAL, $registration->calculateStatusFromRelatedModels());

        // Update enrollment proof to approved
        $registration->enrollmentProof->update(['status' => EnrollmentProof::STATUS_APPROVED]);
        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_APPROVED, $registration->calculateStatusFromRelatedModels());

        // Create payment but it should not affect undergrad student status
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_REJECTED,
        ]);

        $registration->refresh();
        // Status should still be approved (based on enrollment proof, not payment)
        $this->assertEquals(Registration::PAYMENT_STATUS_APPROVED, $registration->calculateStatusFromRelatedModels());
    }

    /** @test */
    public function it_calculates_status_for_grad_student_requiring_both_payment_and_enrollment_proof(): void
    {
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'registration_category_snapshot' => 'grad_student',
            'payment_status' => Registration::PAYMENT_STATUS_PENDING,
        ]);

        // Test without payment or enrollment proof - should be pending
        $this->assertEquals(Registration::PAYMENT_STATUS_PENDING, $registration->calculateStatusFromRelatedModels());

        // Create payment only - should still be pending (missing enrollment proof)
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_APPROVED,
        ]);

        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_PENDING, $registration->calculateStatusFromRelatedModels());

        // Create enrollment proof only - should still be pending (missing approved payment)
        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_APPROVED,
        ]);

        // Update payment to pending_approval
        $registration->payments()->latest()->first()->update(['status' => Payment::STATUS_PENDING_APPROVAL]);
        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_PENDING_APPROVAL, $registration->calculateStatusFromRelatedModels());

        // Both approved - should be approved
        $registration->payments()->latest()->first()->update(['status' => Payment::STATUS_APPROVED]);
        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_APPROVED, $registration->calculateStatusFromRelatedModels());

        // If either is rejected, registration should be rejected
        $registration->enrollmentProof->update(['status' => EnrollmentProof::STATUS_REJECTED]);
        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_REJECTED, $registration->calculateStatusFromRelatedModels());
    }

    /** @test */
    public function it_calculates_status_for_other_categories_based_on_payment_only(): void
    {
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'registration_category_snapshot' => 'professor_abe',
            'payment_status' => Registration::PAYMENT_STATUS_PENDING,
        ]);

        // Test without payment - should be pending
        $this->assertEquals(Registration::PAYMENT_STATUS_PENDING, $registration->calculateStatusFromRelatedModels());

        // Create payment with pending_approval status
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_PENDING_APPROVAL,
        ]);

        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_PENDING_APPROVAL, $registration->calculateStatusFromRelatedModels());

        // Update payment to approved
        $registration->payments()->latest()->first()->update(['status' => Payment::STATUS_APPROVED]);
        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_APPROVED, $registration->calculateStatusFromRelatedModels());

        // Create enrollment proof but it should not affect other categories
        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_REJECTED,
        ]);

        $registration->refresh();
        // Status should still be approved (based on payment, not enrollment proof)
        $this->assertEquals(Registration::PAYMENT_STATUS_APPROVED, $registration->calculateStatusFromRelatedModels());
    }

    /** @test */
    public function it_automatically_updates_registration_status_when_payment_changes(): void
    {
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'registration_category_snapshot' => 'professor_abe',
            'payment_status' => Registration::PAYMENT_STATUS_PENDING,
        ]);

        // Create payment - observer should update registration status
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_APPROVED,
        ]);

        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_APPROVED, $registration->payment_status);

        // Update payment status - observer should update registration status
        $registration->payments()->latest()->first()->update(['status' => Payment::STATUS_REJECTED]);

        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_REJECTED, $registration->payment_status);
    }

    /** @test */
    public function it_automatically_updates_registration_status_when_enrollment_proof_changes(): void
    {
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'registration_category_snapshot' => 'undergrad_student',
            'payment_status' => Registration::PAYMENT_STATUS_PENDING,
        ]);

        // Create enrollment proof - observer should update registration status
        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_APPROVED,
        ]);

        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_APPROVED, $registration->payment_status);

        // Update enrollment proof status - observer should update registration status
        $registration->enrollmentProof->update(['status' => EnrollmentProof::STATUS_REJECTED]);

        $registration->refresh();
        $this->assertEquals(Registration::PAYMENT_STATUS_REJECTED, $registration->payment_status);
    }

    /** @test */
    public function it_automatically_updates_registration_status_when_category_changes(): void
    {
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'registration_category_snapshot' => 'professor_abe',
            'payment_status' => Registration::PAYMENT_STATUS_PENDING,
        ]);

        // Create both payment and enrollment proof
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_APPROVED,
        ]);

        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_REJECTED,
        ]);

        $registration->refresh();
        // As professor, status should be approved (based on payment)
        $this->assertEquals(Registration::PAYMENT_STATUS_APPROVED, $registration->payment_status);

        // Change to undergrad student - observer should update registration status
        $registration->update(['registration_category_snapshot' => 'undergrad_student']);

        $registration->refresh();
        // As undergrad, status should be rejected (based on enrollment proof)
        $this->assertEquals(Registration::PAYMENT_STATUS_REJECTED, $registration->payment_status);
    }

    /** @test */
    public function it_handles_multiple_payments_correctly_using_latest(): void
    {
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'registration_category_snapshot' => 'professor_abe',
            'payment_status' => Registration::PAYMENT_STATUS_PENDING,
        ]);

        // Create first payment
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_APPROVED,
            'created_at' => now()->subHour(),
        ]);

        // Create second (latest) payment
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_REJECTED,
            'created_at' => now(),
        ]);

        $registration->refresh();
        // Status should reflect the latest payment (rejected)
        $this->assertEquals(Registration::PAYMENT_STATUS_REJECTED, $registration->calculateStatusFromRelatedModels());
    }

    /** @test */
    public function update_payment_status_from_related_models_returns_correct_boolean(): void
    {
        $registration = Registration::factory()->create([
            'user_id' => $this->user->id,
            'registration_category_snapshot' => 'professor_abe',
            'payment_status' => Registration::PAYMENT_STATUS_PENDING,
        ]);

        // Should return true when status actually changes
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_APPROVED,
        ]);

        $changed = $registration->updatePaymentStatusFromRelatedModels();
        $this->assertTrue($changed);
        $this->assertEquals(Registration::PAYMENT_STATUS_APPROVED, $registration->fresh()->payment_status);

        // Should return false when status doesn't change
        $changed = $registration->updatePaymentStatusFromRelatedModels();
        $this->assertFalse($changed);
    }
}
