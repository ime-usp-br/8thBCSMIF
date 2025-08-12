<?php

namespace App\Providers;

use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set global currency and locale for the Number facade
        $currency = config('currency.code') ?? 'BRL';
        $locale = config('currency.locale') ?? 'pt_BR';

        // Ensure proper types
        if (! is_string($currency)) {
            $currency = 'BRL';
        }
        if (! is_string($locale)) {
            $locale = 'pt_BR';
        }

        Number::useCurrency($currency);
        Number::useLocale($locale);
    }
}
