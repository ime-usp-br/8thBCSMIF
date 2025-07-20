<?php

namespace App\Listeners;

use App\Events\NewRegistrationCreated;
use App\Mail\NewRegistrationNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewRegistrationNotifications
{
    /**
     * Keep track of processed registrations to avoid duplicates within the same request
     *
     * @var array<string, array<int, int>>
     */
    private static array $processedInCurrentRequest = [];

    /**
     * Handle the event.
     */
    public function handle(NewRegistrationCreated $event): void
    {
        $registration = $event->registration;

        // Reset static cache between different requests/tests
        $currentRequestId = spl_object_hash($event);
        if (! isset(self::$processedInCurrentRequest[$currentRequestId])) {
            self::$processedInCurrentRequest[$currentRequestId] = [];
        }

        // Prevent duplicate processing for the same registration in the same request
        $registrationKey = (int) $registration->id;
        $processedIds = self::$processedInCurrentRequest[$currentRequestId];

        if (in_array($registrationKey, $processedIds, true)) {
            Log::warning(__('Duplicate registration notification prevented'), [
                'registration_id' => $registration->id,
                'request_id' => $currentRequestId,
            ]);

            return;
        }

        // Mark as processed for this request
        self::$processedInCurrentRequest[$currentRequestId][] = $registrationKey;

        Log::info(__('Sending registration notifications'), [
            'registration_id' => $registration->id,
            'user_email' => $registration->user->email,
        ]);

        // Send notification to the user
        Mail::to($registration->user->email)
            ->queue(new NewRegistrationNotification($registration, forCoordinator: false));

        Log::info(__('User registration notification sent'), [
            'registration_id' => $registration->id,
            'user_email' => $registration->user->email,
        ]);

        // Send notification to coordinator if coordinator email is configured
        $coordinatorEmail = NewRegistrationNotification::getCoordinatorEmail();
        if ($coordinatorEmail) {
            Mail::to($coordinatorEmail)
                ->queue(new NewRegistrationNotification($registration, forCoordinator: true));

            Log::info(__('Coordinator registration notification sent'), [
                'registration_id' => $registration->id,
                'coordinator_email' => $coordinatorEmail,
            ]);
        } else {
            Log::warning(__('Coordinator email not configured - coordinator notification not sent'), [
                'registration_id' => $registration->id,
            ]);
        }
    }
}
