<?php

namespace App\Providers;

use App\Models\Registration;
use App\Policies\RegistrationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            $rule = Password::min(8);

            return $rule->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        Gate::policy(Registration::class, RegistrationPolicy::class);
    }
}
