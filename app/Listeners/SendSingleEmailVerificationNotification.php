<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Cache;

class SendSingleEmailVerificationNotification
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        if ($event->user instanceof MustVerifyEmail && ! $event->user->hasVerifiedEmail()) {
            // Use cache to prevent duplicate emails within a short timeframe
            $cacheKey = 'verification_email_sent_' . $event->user->id;
            
            if (! Cache::has($cacheKey)) {
                $event->user->sendEmailVerificationNotification();
                
                // Cache for 5 minutes to prevent duplicates
                Cache::put($cacheKey, true, 300);
            }
        }
    }
}
