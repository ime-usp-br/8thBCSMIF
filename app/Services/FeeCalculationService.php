<?php

namespace App\Services;

use App\Exceptions\FeeCalculationException;
use App\Models\Event;
use App\Models\Fee;
use Carbon\Carbon;

/**
 * Service class for calculating registration fees.
 * This service will handle the logic for determining the correct fee
 * based on participant category, selected events, registration date,
 * and potential discounts.
 */
class FeeCalculationService
{
    /**
     * FeeCalculationService constructor.
     *
     * @param  \App\Models\Event  $eventModel  Instance of the Event model.
     * @param  \App\Models\Fee  $feeModel  Instance of the Fee model.
     */
    public function __construct(protected Event $eventModel, protected Fee $feeModel) {}

    /**
     * Calculates the total registration fee for a participant.
     *
     * This method forms the basis for calculating fees. Subsequent ACs will
     * implement the detailed logic for fetching prices, determining early/late
     * periods, and applying discounts.
     *
     * @param  string  $participantCategory  The category of the participant (e.g., 'grad_student').
     * @param  array<string>  $eventCodes  An array of event codes the participant is registering for (e.g., ['BCSMIF2025', 'RAA2025']).
     * @param  \Carbon\Carbon  $registrationDate  The date of registration.
     * @param  bool  $isMainConferenceParticipant  Indicates if the participant is also registered for the main conference (BCSMIF2025),
     *                                             used for workshop discount logic. Defaults to false.
     * @return array{
     *     details: array<int, array{event_code: string, event_name: string, calculated_price: float}>,
     *     total_fee: float
     * }
     * An associative array containing:
     * - 'details': A list of details for each event, including its code, name, and calculated price.
     * - 'total_fee': The sum of all calculated prices.
     *
     * @throws \App\Exceptions\FeeCalculationException If a fee combination is not found (to be implemented in AC8).
     */
    public function calculateFees(string $participantCategory, array $eventCodes, Carbon $registrationDate, bool $isMainConferenceParticipant = false): array
    {
        // Logic for AC3 through AC8 will be implemented here.
        // For AC2, only the method signature and basic structure are required.
        // The @throws tag for FeeCalculationException is in preparation for AC8.

        // Placeholder return structure for future ACs
        return [
            'details' => [],
            'total_fee' => 0.00,
        ];
    }
}
