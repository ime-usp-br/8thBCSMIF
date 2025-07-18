<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewRegistrationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Registration $registration,
        public bool $forCoordinator = false
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: __('Registration Confirmation - 8th BCSMIF'),
        );

        // Add coordinator email as recipient when sending coordinator notification
        if ($this->forCoordinator) {
            $coordinatorEmail = config('mail.coordinator_email');
            if (is_string($coordinatorEmail)) {
                $envelope->to($coordinatorEmail);
            }
        }

        // Add CC to assoc.bras.estatistica@gmail.com for international participants
        if (! $this->forCoordinator && $this->registration->document_country_origin !== 'Brazil') {
            $envelope->cc('assoc.bras.estatistica@gmail.com');
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $template = $this->forCoordinator
            ? 'emails.registration.new-coordinator'
            : 'emails.registration.new';

        $data = ['registration' => $this->registration];

        // Add early bird reminder data for participant emails only
        if (! $this->forCoordinator) {
            $data['showEarlyBirdReminder'] = $this->shouldShowEarlyBirdReminder();
            $data['earlyBirdDeadline'] = $this->getEarlyBirdDeadline();
        }

        return new Content(
            markdown: $template,
            with: $data,
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

    /**
     * Determine if early bird reminder should be shown.
     */
    private function shouldShowEarlyBirdReminder(): bool
    {
        // Check if there's an amount due
        if ($this->registration->calculateCorrectTotalFee() <= 0) {
            return false;
        }

        // Get the early bird deadline from the first event
        $earlyBirdDeadline = $this->getEarlyBirdDeadline();
        if (! $earlyBirdDeadline) {
            return false;
        }

        // Check if we're still within the early bird period
        return now() <= $earlyBirdDeadline;
    }

    /**
     * Get the early bird deadline from registration events.
     */
    private function getEarlyBirdDeadline(): ?\Illuminate\Support\Carbon
    {
        return $this->registration->events->first()?->registration_deadline_early;
    }
}
