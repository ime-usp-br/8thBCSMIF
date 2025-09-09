<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\ApprovalQueue;
use App\Mail\EnrollmentApprovedNotification;
use App\Mail\EnrollmentRejectedNotification;
use App\Mail\PaymentApprovedNotification;
use App\Mail\PaymentRejectedNotification;
use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * ApprovalQueue Email Notification Tests
 *
 * Tests to verify that email notifications are sent when approving/rejecting
 * payment proofs and enrollment proofs through the admin approval queue.
 */
class ApprovalQueueEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        // Create and authenticate admin user
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        // Mock the mail system
        Mail::fake();
    }

    public function test_payment_approval_sends_email_notification(): void
    {
        // Arrange: Create a payment with pending approval status
        $user = User::factory()->create(['email' => 'user@test.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'email' => 'user@test.com',
        ]);
        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_PENDING_APPROVAL,
        ]);

        // Act: Approve the payment through the component
        Livewire::test(ApprovalQueue::class)
            ->call('quickApprove', 'payment', $payment->id);

        // Assert: Email notification was queued
        Mail::assertQueued(PaymentApprovedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id &&
                   $mail->approvalType === 'approval' &&
                   $mail->hasTo('user@test.com');
        });

        // Assert: Payment status was updated
        $this->assertEquals(Payment::STATUS_APPROVED, $payment->fresh()->status);
    }

    public function test_payment_rejection_sends_email_notification(): void
    {
        // Arrange: Create a payment with pending approval status
        $user = User::factory()->create(['email' => 'user@test.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'email' => 'user@test.com',
        ]);
        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_PENDING_APPROVAL,
        ]);

        // Act: Reject the payment through the component
        Livewire::test(ApprovalQueue::class)
            ->call('quickReject', 'payment', $payment->id);

        // Assert: Email notification was queued
        Mail::assertQueued(PaymentRejectedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id &&
                   $mail->rejectionReason === __('Quick rejection from approval queue') &&
                   $mail->hasTo('user@test.com');
        });

        // Assert: Payment status was updated
        $this->assertEquals(Payment::STATUS_REJECTED, $payment->fresh()->status);
    }

    public function test_enrollment_approval_sends_email_notification(): void
    {
        // Arrange: Create an enrollment proof with pending approval status
        $user = User::factory()->create(['email' => 'user@test.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'email' => 'user@test.com',
        ]);
        $enrollmentProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);

        // Act: Approve the enrollment proof through the component
        Livewire::test(ApprovalQueue::class)
            ->call('quickApprove', 'enrollment', $enrollmentProof->id);

        // Assert: Email notification was queued
        Mail::assertQueued(EnrollmentApprovedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id &&
                   $mail->hasTo('user@test.com');
        });

        // Assert: Enrollment proof status was updated
        $this->assertEquals(EnrollmentProof::STATUS_APPROVED, $enrollmentProof->fresh()->status);
    }

    public function test_enrollment_rejection_sends_email_notification(): void
    {
        // Arrange: Create an enrollment proof with pending approval status
        $user = User::factory()->create(['email' => 'user@test.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'email' => 'user@test.com',
        ]);
        $enrollmentProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);

        // Act: Reject the enrollment proof through the component
        Livewire::test(ApprovalQueue::class)
            ->call('quickReject', 'enrollment', $enrollmentProof->id);

        // Assert: Email notification was queued
        Mail::assertQueued(EnrollmentRejectedNotification::class, function ($mail) use ($registration) {
            return $mail->registration->id === $registration->id &&
                   $mail->rejectionReason === __('Quick rejection from approval queue') &&
                   $mail->hasTo('user@test.com');
        });

        // Assert: Enrollment proof status was updated
        $this->assertEquals(EnrollmentProof::STATUS_REJECTED, $enrollmentProof->fresh()->status);
    }

    public function test_email_uses_registration_email_as_fallback(): void
    {
        // Arrange: Create registration where user email is different from registration email
        $user = User::factory()->create(['email' => 'user@test.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'email' => 'different@test.com', // Different registration email
        ]);
        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_PENDING_APPROVAL,
        ]);

        // Act: Approve the payment through the component
        Livewire::test(ApprovalQueue::class)
            ->call('quickApprove', 'payment', $payment->id);

        // Assert: Email notification was queued to user email (takes precedence)
        Mail::assertQueued(PaymentApprovedNotification::class, function ($mail) {
            return $mail->hasTo('user@test.com');
        });
    }

    public function test_email_uses_user_email_when_available(): void
    {
        // Arrange: Create registration with both user and registration emails
        $user = User::factory()->create(['email' => 'user@test.com']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'email' => 'registration@test.com',
        ]);
        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => Payment::STATUS_PENDING_APPROVAL,
        ]);

        // Act: Approve the payment through the component
        Livewire::test(ApprovalQueue::class)
            ->call('quickApprove', 'payment', $payment->id);

        // Assert: Email notification was queued to user email (priority over registration email)
        Mail::assertQueued(PaymentApprovedNotification::class, function ($mail) {
            return $mail->hasTo('user@test.com');
        });
    }
}
