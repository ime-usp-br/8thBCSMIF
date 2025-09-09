<?php

namespace Tests\Feature\Mail;

use App\Mail\EnrollmentRejectedNotification;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EnrollmentRejectedNotification Mailable Tests
 *
 * Tests for enrollment rejection notification email content and functionality
 */
class EnrollmentRejectedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejection_notification_contains_required_content(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john@test.com',
        ]);

        $rejectionReason = 'Enrollment document is unclear. Please provide a higher quality image.';

        $mailable = new EnrollmentRejectedNotification($registration, $rejectionReason);

        // Test subject line
        $envelope = $mailable->envelope();
        $this->assertEquals(__('Enrollment Proof Rejected - 8th BCSMIF'), $envelope->subject);

        // Test mailable uses correct template
        $content = $mailable->content();
        $this->assertEquals('emails.registration.enrollment-rejected', $content->markdown);

        // Test mailable properties are accessible
        $this->assertEquals($registration->id, $mailable->registration->id);
        $this->assertEquals($rejectionReason, $mailable->rejectionReason);
    }

    public function test_rejection_notification_includes_coordinator_bcc(): void
    {
        // Set coordinator email in config
        config(['mail.coordinator_email' => 'coordinator@test.com']);

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $mailable = new EnrollmentRejectedNotification($registration, 'Test reason');
        $envelope = $mailable->envelope();

        $this->assertTrue(collect($envelope->bcc)->contains(function ($bcc) {
            return $bcc->address === 'coordinator@test.com';
        }));
    }

    public function test_rejection_notification_handles_missing_coordinator_email(): void
    {
        // Clear coordinator email config
        config(['mail.coordinator_email' => null]);

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $mailable = new EnrollmentRejectedNotification($registration, 'Test reason');
        $envelope = $mailable->envelope();

        $this->assertEmpty($envelope->bcc);
    }

    public function test_rejection_notification_is_queueable(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $mailable = new EnrollmentRejectedNotification($registration, 'Test reason');

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mailable);
    }

    public function test_get_coordinator_email_helper_method(): void
    {
        // Test with valid email
        config(['mail.coordinator_email' => 'test@test.com']);
        $this->assertEquals('test@test.com', EnrollmentRejectedNotification::getCoordinatorEmail());

        // Test with null
        config(['mail.coordinator_email' => null]);
        $this->assertNull(EnrollmentRejectedNotification::getCoordinatorEmail());
    }

    public function test_rejection_notification_renders_without_errors(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
        ]);

        $rejectionReason = 'Document quality is too poor to verify enrollment status.';
        $mailable = new EnrollmentRejectedNotification($registration, $rejectionReason);
        $rendered = $mailable->render();

        $this->assertStringContainsString('John Doe', $rendered);
        $this->assertStringContainsString($rejectionReason, $rendered);
        $this->assertStringContainsString(__('Enrollment Document Not Approved'), $rendered);
    }
}
