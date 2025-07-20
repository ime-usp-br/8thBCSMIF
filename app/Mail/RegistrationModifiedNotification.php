<?php

namespace App\Mail;

use App\Models\Registration;
use App\Services\FeeCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationModifiedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Registration $registration,
        public bool $forCoordinator = false
    ) {
        $this->registration->refresh();
    }

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: __('Registration Modified - 8th BCSMIF'),
        );

        // Add CC to assoc.bras.estatistica@gmail.com for international participants
        if (! $this->forCoordinator && $this->registration->document_country_origin !== 'Brazil') {
            $envelope->cc('assoc.bras.estatistica@gmail.com');
        }

        return $envelope;
    }

    public function content(): Content
    {
        $template = $this->forCoordinator
            ? 'emails.registration.modified-coordinator'
            : 'emails.registration.modified';

        // Calculate fees with retroactive discounts
        /** @var list<string> $eventCodes */
        $eventCodes = array_values($this->registration->events->pluck('code')->toArray());
        $feeService = app(FeeCalculationService::class);
        $feeCalculation = $feeService->calculateFees(
            $this->registration->registration_category_snapshot,
            $eventCodes,
            $this->registration->created_at ?? now(),
            $this->registration->participation_format === 'online' ? 'online' : 'in-person',
            $this->registration
        );

        $data = [
            'registration' => $this->registration,
            'feeCalculation' => $feeCalculation,
        ];

        // Add early bird reminder data for participant emails only
        if (! $this->forCoordinator) {
            $data['showEarlyBirdReminder'] = $this->shouldShowEarlyBirdReminder($feeCalculation);
            $data['earlyBirdDeadline'] = $this->getEarlyBirdDeadline();
        }

        return new Content(
            markdown: $template,
            with: $data,
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

    /**
     * Determine if early bird reminder should be shown.
     *
     * @param  array<string, mixed>  $feeCalculation
     */
    private function shouldShowEarlyBirdReminder(array $feeCalculation): bool
    {
        // Check if there's an amount due
        if ($feeCalculation['amount_due'] <= 0) {
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
