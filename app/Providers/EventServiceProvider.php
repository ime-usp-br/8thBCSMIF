<?php

namespace App\Providers;

use App\Listeners\SendSingleEmailVerificationNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Don't call parent::boot() to avoid any automatic registration
        // parent::boot();
        
        // Use booted callback to ensure this happens after all providers are loaded
        $this->app->booted(function () {
            // Clear any existing listeners for Registered event
            Event::forget(Registered::class);
            
            // Register only our custom listener
            Event::listen(Registered::class, SendSingleEmailVerificationNotification::class);
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * Override the default email verification configuration to prevent
     * automatic registration of the default SendEmailVerificationNotification listener.
     */
    protected function configureEmailVerification(): void
    {
        // Intentionally empty to prevent automatic listener registration
    }
}
