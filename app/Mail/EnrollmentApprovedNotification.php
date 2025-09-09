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
 * EnrollmentApprovedNotification Mailable
 *
 * Sends notification when an enrollment proof is approved.
 * Specifically designed for academic documentation validation.
 */
class EnrollmentApprovedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Registration  $registration  The registration whose enrollment proof was approved
     */
    public function __construct(
        public Registration $registration
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: __('Enrollment Proof Approved - 8th BCSMIF'),
        );

        // Add coordinator email in BCC for enrollment approvals
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
            markdown: 'emails.registration.enrollment-approved',
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
