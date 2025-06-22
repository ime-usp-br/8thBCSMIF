<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationModifiedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Registration $registration,
        public bool $forCoordinator = false
    ) {
        //
    }

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: __('Registration Modified - 8th BCSMIF'),
        );

        // Add coordinator email as recipient when sending coordinator notification
        if ($this->forCoordinator) {
            $coordinatorEmail = config('mail.coordinator_email');
            if (is_string($coordinatorEmail)) {
                $envelope->to($coordinatorEmail);
            }
        }

        return $envelope;
    }

    public function content(): Content
    {
        $template = $this->forCoordinator
            ? 'emails.registration.modified-coordinator'
            : 'emails.registration.modified';

        return new Content(
            markdown: $template,
            with: [
                'registration' => $this->registration,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public static function getCoordinatorEmail(): ?string
    {
        $email = config('mail.coordinator_email');

        return is_string($email) ? $email : null;
    }
}
