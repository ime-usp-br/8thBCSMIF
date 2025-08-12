<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency Code
    |--------------------------------------------------------------------------
    |
    | This value determines the default currency code used throughout the
    | application for formatting monetary values. This should be a valid
    | ISO 4217 currency code.
    |
    */

    'code' => env('CURRENCY_CODE', 'BRL'),

    /*
    |--------------------------------------------------------------------------
    | Currency Display Settings
    |--------------------------------------------------------------------------
    |
    | These settings control how currency values are displayed in the
    | application, including decimal places and locale-specific formatting.
    |
    */

    'precision' => 2,
    'locale' => env('APP_LOCALE', 'pt_BR'),

];
