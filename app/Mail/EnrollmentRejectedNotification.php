<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * EnrollmentRejectedNotification Mailable
 *
 * Sends notification when an enrollment proof is rejected with admin reason.
 * Includes rejection reason and link to /my-registration for resubmission.
 * Specifically designed for academic documentation issues.
 */
class EnrollmentRejectedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Registration  $registration  The registration whose enrollment proof was rejected
     * @param  string  $rejectionReason  Reason provided by the admin for rejection
     */
    public function __construct(
        public Registration $registration,
        public string $rejectionReason
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: __('Enrollment Proof Rejected - 8th BCSMIF'),
        );

        // Add coordinator email in BCC for enrollment rejections
        $coordinatorEmail = config('mail.coordinator_email');
        if (is_string($coordinatorEmail)) {
            $envelope->bcc($coordinatorEmail);
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.registration.enrollment-rejected',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the coordinator email address.
     */
    public static function getCoordinatorEmail(): ?string
    {
        $email = config('mail.coordinator_email');

        return is_string($email) ? $email : null;
    }
}
