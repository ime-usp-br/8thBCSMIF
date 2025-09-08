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
 * PaymentRejectedNotification Mailable
 *
 * AC4: Sends notification when a payment proof is rejected with admin reason.
 * Includes rejection reason and link to /my-registration for resubmission.
 */
class PaymentRejectedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Registration  $registration  The registration that was rejected
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
        $subject = __('Payment Rejected - 8th BCSMIF');

        $envelope = new Envelope(
            subject: $subject,
        );

        // Add coordinator email in BCC for payment rejections
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
            markdown: 'emails.registration.payment-rejected',
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
