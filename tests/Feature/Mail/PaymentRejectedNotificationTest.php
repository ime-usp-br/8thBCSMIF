<?php

namespace Tests\Feature\Mail;

use App\Mail\PaymentRejectedNotification;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PaymentRejectedNotification Mailable Tests
 *
 * AC4: Tests for rejection notification email content and functionality
 */
class PaymentRejectedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejection_notification_contains_required_content(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john@test.com',
            'status' => Registration::STATUS_REJECTED,
        ]);

        $rejectionReason = 'Payment proof image is unclear. Please provide a higher quality image.';

        $mailable = new PaymentRejectedNotification($registration, $rejectionReason);

        // Test subject line
        $envelope = $mailable->envelope();
        $this->assertEquals(__('Payment Rejected - 8th BCSMIF'), $envelope->subject);

        // Test mailable uses correct template
        $content = $mailable->content();
        $this->assertEquals('emails.registration.payment-rejected', $content->markdown);

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
            'full_name' => 'Jane Smith',
            'email' => 'jane@test.com',
        ]);

        $mailable = new PaymentRejectedNotification($registration, 'Test rejection reason');
        $envelope = $mailable->envelope();

        // Check coordinator is in BCC (Laravel uses Address objects in envelope)
        $bccEmails = collect($envelope->bcc)->pluck('address')->toArray();
        $this->assertContains('coordinator@test.com', $bccEmails);
    }

    public function test_rejection_notification_handles_missing_coordinator_email(): void
    {
        // Clear coordinator email in config
        config(['mail.coordinator_email' => null]);

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'email' => 'test@test.com',
        ]);

        $mailable = new PaymentRejectedNotification($registration, 'Test reason');
        $envelope = $mailable->envelope();

        // Should not throw exception when coordinator email is null
        $this->assertEmpty($envelope->bcc);
    }

    public function test_rejection_notification_is_queueable(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $mailable = new PaymentRejectedNotification($registration, 'Test reason');

        // Test that mailable implements ShouldQueue
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mailable);
    }

    public function test_get_coordinator_email_helper_method(): void
    {
        // Test with valid coordinator email
        config(['mail.coordinator_email' => 'coordinator@test.com']);
        $this->assertEquals('coordinator@test.com', PaymentRejectedNotification::getCoordinatorEmail());

        // Test with null coordinator email
        config(['mail.coordinator_email' => null]);
        $this->assertNull(PaymentRejectedNotification::getCoordinatorEmail());

        // Test with non-string coordinator email
        config(['mail.coordinator_email' => ['invalid@test.com']]);
        $this->assertNull(PaymentRejectedNotification::getCoordinatorEmail());
    }

    public function test_rejection_notification_renders_without_errors(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Test User',
            'email' => 'test@test.com',
        ]);

        $rejectionReason = 'Invalid payment amount. Expected R$ 350,00 but received R$ 300,00.';

        $mailable = new PaymentRejectedNotification($registration, $rejectionReason);

        // Test that mailable renders without throwing exceptions
        $rendered = $mailable->render();
        $this->assertIsString($rendered);
        $this->assertNotEmpty($rendered);

        // Check that key content appears in rendered email
        $this->assertStringContainsString($registration->full_name, $rendered);
        $this->assertStringContainsString($rejectionReason, $rendered);
        $this->assertStringContainsString(__('Payment Rejected - 8th BCSMIF'), $rendered);
    }
}
