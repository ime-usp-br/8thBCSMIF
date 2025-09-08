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
 * PaymentApprovedNotification Mailable
 *
 * AC3: Sends notification when a payment is approved or a fee exemption is granted.
 * Supports both regular payment approvals and administrative exemptions.
 */
class PaymentApprovedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Registration  $registration  The registration that was approved
     * @param  string  $approvalType  Type of approval: 'approval' for regular payment, 'exemption' for fee exemption
     */
    public function __construct(
        public Registration $registration,
        public string $approvalType = 'approval'
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->approvalType === 'exemption'
            ? __('Fee Exemption Approved - 8th BCSMIF')
            : __('Payment Approved - 8th BCSMIF');

        $envelope = new Envelope(
            subject: $subject,
        );

        // Add coordinator email in BCC for payment approvals
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
            markdown: 'emails.registration.payment-approved',
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
     * Check if this is a fee exemption notification.
     */
    public function isExemption(): bool
    {
        return $this->approvalType === 'exemption';
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
