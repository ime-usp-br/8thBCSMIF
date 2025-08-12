<?php

namespace App\Helpers;

use Illuminate\Support\Number;

class CurrencyHelper
{
    /**
     * Format a currency amount using Laravel's Number helper
     * with application-configured currency and locale.
     */
    public static function format(
        float $amount,
        ?string $currency = null,
        ?string $locale = null,
        ?int $precision = null
    ): string {
        $currency = $currency ?? config('currency.code') ?? 'BRL';
        $locale = $locale ?? config('currency.locale') ?? 'pt_BR';
        $precision = $precision ?? config('currency.precision') ?? 2;

        // Ensure proper types
        if (! is_string($currency)) {
            $currency = 'BRL';
        }
        if (! is_string($locale)) {
            $locale = 'pt_BR';
        }
        if (! is_int($precision)) {
            $precision = 2;
        }

        $result = Number::currency(
            $amount,
            in: $currency,
            locale: $locale,
            precision: $precision
        );

        return $result !== false ? $result : $currency.' '.number_format($amount, $precision);
    }

    /**
     * Get the configured currency symbol for display.
     */
    public static function getSymbol(?string $currency = null): string
    {
        $currency = $currency ?? config('currency.code') ?? 'BRL';

        // Ensure proper type
        if (! is_string($currency)) {
            $currency = 'BRL';
        }

        // Common currency symbols mapping
        return match ($currency) {
            'BRL' => 'R$',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            default => $currency.' ',
        };
    }
}
