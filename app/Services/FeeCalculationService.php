<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Fee;
use App\Models\Registration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service class for calculating registration fees.
 *
 * This service handles the logic for determining the correct fee based on
 * participant category, selected events, registration date, and potential discounts.
 */
class FeeCalculationService
{
    protected Event $eventModel;

    protected Fee $feeModel;

    /**
     * FeeCalculationService constructor.
     */
    public function __construct(Event $eventModel, Fee $feeModel)
    {
        $this->eventModel = $eventModel;
        $this->feeModel = $feeModel;
    }

    /**
     * Calculates the total registration fee for a participant.
     *
     * If a Registration object is provided, this method will calculate fees for
     * modification scenarios, considering already paid amounts and applying
     * retroactive discounts.
     *
     * @param  string  $participantCategory  The category of the participant (e.g., 'undergrad_student').
     * @param  list<string>  $eventCodes  An array of event codes the participant is registering for.
     * @param  \Illuminate\Support\Carbon  $registrationDate  The date of registration.
     * @param  string  $participationType  The type of participation (e.g., 'in-person', 'online').
     * @param  \App\Models\Registration|null  $registration  Optional existing registration for modification scenarios.
     * @return array{details: list<array{event_code: string, event_name: string, calculated_price: float, error?: string, query_details?: array<string, mixed>, fee_object_retrieved?: array<string, mixed>}>, total_fee: float, new_total_fee?: float, total_paid?: float, amount_due?: float}
     *                                                                                                                                                                                                                                                                                          An array containing detailed fee breakdown and the total fee.
     *                                                                                                                                                                                                                                                                                          - 'details': An array of arrays, each with 'event_code', 'event_name', and 'calculated_price'.
     *                                                                                                                                                                                                                                                                                          May also include 'error' if an issue occurred for a specific event.
     *                                                                                                                                                                                                                                                                                          - 'total_fee': The sum total of all calculated fees.
     *                                                                                                                                                                                                                                                                                          For modification scenarios (when $registration is provided):
     *                                                                                                                                                                                                                                                                                          - 'new_total_fee': The total fee for the new event selection.
     *                                                                                                                                                                                                                                                                                          - 'total_paid': The amount already paid for the registration.
     *                                                                                                                                                                                                                                                                                          - 'amount_due': The difference to be paid (new_total_fee - total_paid).
     */
    public function calculateFees(
        string $participantCategory,
        array $eventCodes,
        Carbon $registrationDate,
        string $participationType = 'in-person',
        ?Registration $registration = null
    ): array {
        $feeDetails = [];
        $totalFee = 0.0;

        $mainConferenceCode = config('fee_calculation.main_conference_code', 'BCSMIF2025');
        $isAttendingMainConference = in_array($mainConferenceCode, $eventCodes);

        // AC2: Calculate total paid for existing registration if provided
        $totalPaid = 0.0;
        if ($registration) {
            $totalPaid = (float) $registration->payments()
                ->where('status', 'paid')
                ->sum('amount');
        }

        foreach ($eventCodes as $eventCode) {
            $event = $this->eventModel->where('code', $eventCode)->first();

            if (! $event) {
                Log::warning("FeeCalculationService: Event not found for code {$eventCode}.");
                $feeDetails[] = [
                    'event_code' => $eventCode,
                    'event_name' => __('fees.event_not_found'),
                    'calculated_price' => 0.00,
                    'error' => __('fees.event_not_found'),
                ];

                continue;
            }

            // Determine period based on registration date and event's early deadline
            $period = ($event->registration_deadline_early && $registrationDate->lte($event->registration_deadline_early))
                ? 'early'
                : 'late';

            $feeQuery = $this->feeModel
                    ->where('event_code', $eventCode)
                    ->where('participant_category', $participantCategory)
                    ->where('type', $participationType)
                    ->where('period', $period);

            $foundFee = null;

            // AC5: Discount logic for workshops if attending main conference (retroactive discount logic)
            // This applies the same discount logic as for new registrations, effectively providing retroactive discounts
            // when the main conference is added to an existing registration with workshops
            if (! $event->is_main_conference && $isAttendingMainConference) {
                $feeWithDiscount = (clone $feeQuery)->where('is_discount_for_main_event_participant', true)->first();
                if ($feeWithDiscount) {
                    $foundFee = $feeWithDiscount;
                }
            }

            if (! $foundFee) {
                    // Fetch standard fee (or workshop fee if not discounted, or main conference fee)
                    $feeWithoutDiscount = (clone $feeQuery)->where('is_discount_for_main_event_participant', false)->first();
                    Log::debug('FeeCalculationService: Attempting to find standard fee.', [
                        'query' => (clone $feeQuery)->where('is_discount_for_main_event_participant', false)->toSql(),
                        'bindings' => (clone $feeQuery)->where('is_discount_for_main_event_participant', false)->getBindings(),
                        'found' => $feeWithoutDiscount ? $feeWithoutDiscount->toArray() : null,
                    ]);
                    if ($feeWithoutDiscount) {
                        $foundFee = $feeWithoutDiscount;
                    }
                }

            if ($foundFee) {
                $calculatedPrice = (float) $foundFee->price; // Fee model casts price to string, so cast back.
                $totalFee += $calculatedPrice;
                $feeDetails[] = [
                    'event_code' => $eventCode,
                    'event_name' => $event->name,
                    'calculated_price' => $calculatedPrice,
                    // 'fee_object_retrieved' => $foundFee->toArray(), // Useful for debugging
                ];
                Log::debug("FeeCalculationService: Fee found for {$eventCode}: ", ['calculated_price' => $calculatedPrice, 'fee_details' => $foundFee->toArray()]);
            } else {
                Log::warning('FeeCalculationService: Fee configuration not found.', [
                    'event_code' => $eventCode,
                    'participant_category' => $participantCategory,
                    'type' => $participationType,
                    'period' => $period,
                    'is_main_conference_event' => $event->is_main_conference,
                    'is_attending_main_conference' => $isAttendingMainConference,
                ]);
                $feeDetails[] = [
                    'event_code' => $eventCode,
                    'event_name' => $event->name,
                    'calculated_price' => 0.00,
                    'error' => __('fees.fee_config_not_found'),
                ];
            }
        }

        // AC4: Return detailed response structure based on whether this is a modification scenario
        $result = [
            'details' => $feeDetails,
            'total_fee' => $totalFee,
        ];

        // AC4: Add modification-specific fields when registration is provided
        if ($registration) {
            $result['new_total_fee'] = $totalFee;
            $result['total_paid'] = $totalPaid;
            $result['amount_due'] = $totalFee - $totalPaid;
        }

        return $result;
    }
}
