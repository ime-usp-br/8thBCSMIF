<?php

namespace App\Listeners;

use App\Events\NewRegistrationCreated;
use App\Mail\NewRegistrationNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewRegistrationNotifications
{
    /**
     * Handle the event.
     */
    public function handle(NewRegistrationCreated $event): void
    {
        $registration = $event->registration;

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
