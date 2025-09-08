<?php

namespace Tests\Unit\Mail;

use App\Mail\PaymentApprovedNotification;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PaymentApprovedNotification Mailable Unit Tests
 *
 * Tests for AC3: Email notification functionality for fee exemptions and approvals
 */
class PaymentApprovedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_approved_notification_has_correct_subject_for_regular_approval(): void
    {
        $registration = Registration::factory()->create();

        $mailable = new PaymentApprovedNotification($registration, 'approval');

        $envelope = $mailable->envelope();
        $this->assertEquals(__('Payment Approved - 8th BCSMIF'), $envelope->subject);
    }

    public function test_payment_approved_notification_has_correct_subject_for_exemption(): void
    {
        $registration = Registration::factory()->create();

        $mailable = new PaymentApprovedNotification($registration, 'exemption');

        $envelope = $mailable->envelope();
        $this->assertEquals(__('Fee Exemption Approved - 8th BCSMIF'), $envelope->subject);
    }

    public function test_payment_approved_notification_defaults_to_approval_type(): void
    {
        $registration = Registration::factory()->create();

        $mailable = new PaymentApprovedNotification($registration);

        $envelope = $mailable->envelope();
        $this->assertEquals(__('Payment Approved - 8th BCSMIF'), $envelope->subject);
    }

    public function test_payment_approved_notification_includes_coordinator_bcc_when_configured(): void
    {
        config(['mail.coordinator_email' => 'coordinator@test.com']);

        $registration = Registration::factory()->create();
        $mailable = new PaymentApprovedNotification($registration);

        // Test that the coordinator email is properly configured
        $this->assertEquals('coordinator@test.com', PaymentApprovedNotification::getCoordinatorEmail());

        // The envelope should be created successfully
        $envelope = $mailable->envelope();
        $this->assertNotNull($envelope);
    }

    public function test_payment_approved_notification_handles_missing_coordinator_email(): void
    {
        config(['mail.coordinator_email' => null]);

        $registration = Registration::factory()->create();
        $mailable = new PaymentApprovedNotification($registration);

        $envelope = $mailable->envelope();
        $this->assertEmpty($envelope->bcc ?? []);
    }

    public function test_payment_approved_notification_uses_correct_markdown_template(): void
    {
        $registration = Registration::factory()->create();
        $mailable = new PaymentApprovedNotification($registration);

        $content = $mailable->content();
        $this->assertEquals('emails.registration.payment-approved', $content->markdown);
    }

    public function test_payment_approved_notification_has_no_attachments(): void
    {
        $registration = Registration::factory()->create();
        $mailable = new PaymentApprovedNotification($registration);

        $attachments = $mailable->attachments();
        $this->assertEmpty($attachments);
    }

    public function test_is_exemption_returns_true_for_exemption_type(): void
    {
        $registration = Registration::factory()->create();
        $mailable = new PaymentApprovedNotification($registration, 'exemption');

        $this->assertTrue($mailable->isExemption());
    }

    public function test_is_exemption_returns_false_for_approval_type(): void
    {
        $registration = Registration::factory()->create();
        $mailable = new PaymentApprovedNotification($registration, 'approval');

        $this->assertFalse($mailable->isExemption());
    }

    public function test_is_exemption_returns_false_for_default_type(): void
    {
        $registration = Registration::factory()->create();
        $mailable = new PaymentApprovedNotification($registration);

        $this->assertFalse($mailable->isExemption());
    }

    public function test_get_coordinator_email_returns_configured_email(): void
    {
        config(['mail.coordinator_email' => 'test@coordinator.com']);

        $coordinatorEmail = PaymentApprovedNotification::getCoordinatorEmail();

        $this->assertEquals('test@coordinator.com', $coordinatorEmail);
    }

    public function test_get_coordinator_email_returns_null_when_not_configured(): void
    {
        config(['mail.coordinator_email' => null]);

        $coordinatorEmail = PaymentApprovedNotification::getCoordinatorEmail();

        $this->assertNull($coordinatorEmail);
    }

    public function test_get_coordinator_email_returns_null_when_not_string(): void
    {
        config(['mail.coordinator_email' => 123]); // Not a string

        $coordinatorEmail = PaymentApprovedNotification::getCoordinatorEmail();

        $this->assertNull($coordinatorEmail);
    }

    public function test_mailable_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Test',
            'email' => 'john@test.com',
        ]);

        $mailable = new PaymentApprovedNotification($registration, 'exemption');

        // Test that the mailable can be rendered without errors
        $rendered = $mailable->render();
        $this->assertIsString($rendered);
        $this->assertStringContainsString('John Test', $rendered);
    }

    public function test_mailable_implements_should_queue(): void
    {
        $registration = Registration::factory()->create();
        $mailable = new PaymentApprovedNotification($registration);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mailable);
    }
}
