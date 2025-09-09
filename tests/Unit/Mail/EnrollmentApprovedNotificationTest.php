<?php

namespace Tests\Unit\Mail;

use App\Mail\EnrollmentApprovedNotification;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EnrollmentApprovedNotification Mailable Tests
 *
 * Tests for enrollment approval notification email content and functionality
 */
class EnrollmentApprovedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_approved_notification_has_correct_subject(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john@test.com',
        ]);

        $mailable = new EnrollmentApprovedNotification($registration);

        // Test subject line
        $envelope = $mailable->envelope();
        $this->assertEquals(__('Enrollment Proof Approved - 8th BCSMIF'), $envelope->subject);
    }

    public function test_enrollment_approved_notification_uses_correct_template(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $mailable = new EnrollmentApprovedNotification($registration);

        // Test mailable uses correct template
        $content = $mailable->content();
        $this->assertEquals('emails.registration.enrollment-approved', $content->markdown);
    }

    public function test_enrollment_approved_notification_includes_coordinator_bcc(): void
    {
        // Set coordinator email in config
        config(['mail.coordinator_email' => 'coordinator@test.com']);

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $mailable = new EnrollmentApprovedNotification($registration);
        $envelope = $mailable->envelope();

        $this->assertTrue(collect($envelope->bcc)->contains(function ($bcc) {
            return $bcc->address === 'coordinator@test.com';
        }));
    }

    public function test_enrollment_approved_notification_handles_missing_coordinator_email(): void
    {
        // Clear coordinator email config
        config(['mail.coordinator_email' => null]);

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $mailable = new EnrollmentApprovedNotification($registration);
        $envelope = $mailable->envelope();

        $this->assertEmpty($envelope->bcc);
    }

    public function test_enrollment_approved_notification_has_no_attachments(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $mailable = new EnrollmentApprovedNotification($registration);

        $this->assertEmpty($mailable->attachments());
    }

    public function test_get_coordinator_email_helper(): void
    {
        // Test with valid email
        config(['mail.coordinator_email' => 'test@test.com']);
        $this->assertEquals('test@test.com', EnrollmentApprovedNotification::getCoordinatorEmail());

        // Test with null
        config(['mail.coordinator_email' => null]);
        $this->assertNull(EnrollmentApprovedNotification::getCoordinatorEmail());

        // Test with non-string
        config(['mail.coordinator_email' => 123]);
        $this->assertNull(EnrollmentApprovedNotification::getCoordinatorEmail());
    }

    public function test_mailable_can_be_rendered(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
        ]);

        $mailable = new EnrollmentApprovedNotification($registration);
        $rendered = $mailable->render();

        $this->assertStringContainsString('John Doe', $rendered);
        $this->assertStringContainsString(__('Enrollment Proof Validated!'), $rendered);
    }

    public function test_mailable_implements_should_queue(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        $mailable = new EnrollmentApprovedNotification($registration);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mailable);
    }
}
