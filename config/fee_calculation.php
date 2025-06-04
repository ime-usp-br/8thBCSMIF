<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Main Conference Event Code
    |--------------------------------------------------------------------------
    |
    | This value specifies the unique code used to identify the main conference
    | event. It is used by the FeeCalculationService to determine if a
    | participant is attending the main conference, which may affect
    | pricing for associated workshops or satellite events.
    |
    */
    'main_conference_code' => env('MAIN_CONFERENCE_CODE', 'BCSMIF2025'),

    /*
    |--------------------------------------------------------------------------
    | Default Participation Type
    |--------------------------------------------------------------------------
    |
    | This value sets the default participation type (e.g., 'in-person', 'online')
    | to be used by the FeeCalculationService if no specific type is provided
    | during a fee calculation request.
    |
    */
    'default_participation_type' => 'in-person',
];
