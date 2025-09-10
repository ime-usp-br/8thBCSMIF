<?php

namespace App\Providers;

use App\Console\Commands\FixOrphanedPayments;
use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Observers\EnrollmentProofObserver;
use App\Observers\PaymentObserver;
use App\Observers\RegistrationObserver;
use App\Policies\RegistrationPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
            FixOrphanedPayments::class,
        ]);

        if ($this->app->environment('production')) {
            $request = $this->app->make('request');
            $request->server->set('HTTPS', true);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        } else {
            Mail::alwaysTo(env('MAIL_DEV_TEST'));
        }

        Password::defaults(function () {
            $rule = Password::min(8);

            return $rule->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        Gate::policy(Registration::class, RegistrationPolicy::class);

        Gate::define('manageEnrollmentProofs', function (User $user) {
            return $user->hasRole(['coordinator', 'admin']);
        });

        // Register Eloquent observers for automatic status updates
        Registration::observe(RegistrationObserver::class);
        Payment::observe(PaymentObserver::class);
        EnrollmentProof::observe(EnrollmentProofObserver::class);

        // Register Blade directive for currency formatting
        Blade::directive('currency', function (string $expression): string {
            return "<?php echo \Illuminate\Support\Number::currency({$expression}, in: (string) config('currency.code', 'BRL'), locale: (string) config('currency.locale', 'pt_BR'), precision: (int) config('currency.precision', 2)); ?>";
        });

        // Register Blade directive for locale-aware date formatting
        Blade::directive('dateLocale', function (string $expression): string {
            return "<?php echo \Carbon\Carbon::parse({$expression})->locale((string) config('app.locale', 'en'))->format((string) config('app.locale', 'en') === 'pt_BR' ? 'd/m/Y H:i' : 'M j, Y g:i A'); ?>";
        });

        // Register Blade directive for human-readable date formatting
        Blade::directive('dateHuman', function (string $expression): string {
            return "<?php echo \Carbon\Carbon::parse({$expression})->locale((string) config('app.locale', 'en'))->diffForHumans(); ?>";
        });
    }
}
