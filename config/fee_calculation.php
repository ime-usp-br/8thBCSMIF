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

    /*
    |--------------------------------------------------------------------------
    | Registration Deadline (Early Bird)
    |--------------------------------------------------------------------------
    |
    | The early bird registration deadline date. After this date, late
    | registration fees will apply. Updated to September 5, 2025.
    |
    */
    'early_registration_deadline' => '2025-09-05',

    /*
    |--------------------------------------------------------------------------
    | Accompanying Person Restrictions
    |--------------------------------------------------------------------------
    |
    | Configuration for accompanying person category restrictions.
    | Accompanying persons cannot register for workshops.
    |
    */
    'accompanying_person' => [
        'can_register_workshops' => false,
        'participation_types' => ['in-person'], // Only in-person allowed
        'description' => 'Includes transportation, materials, reception, excursion, dinner. Excludes coffee breaks.',
    ],
];
