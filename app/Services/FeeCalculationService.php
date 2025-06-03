<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Fee;
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
     * @param  string  $participantCategory  The category of the participant (e.g., 'undergrad_student').
     * @param  list<string>  $eventCodes  An array of event codes the participant is registering for.
     * @param  \Illuminate\Support\Carbon  $registrationDate  The date of registration.
     * @param  string  $participationType  The type of participation (e.g., 'in-person', 'online').
     * @return array{details: list<array{event_code: string, event_name: string, calculated_price: float, error?: string, query_details?: array<string, mixed>, fee_object_retrieved?: array<string, mixed>}>, total_fee: float}
     *                                                                                                                                                                                                                           An array containing detailed fee breakdown and the total fee.
     *                                                                                                                                                                                                                           - 'details': An array of arrays, each with 'event_code', 'event_name', and 'calculated_price'.
     *                                                                                                                                                                                                                           May also include 'error' if an issue occurred for a specific event.
     *                                                                                                                                                                                                                           - 'total_fee': The sum total of all calculated fees.
     */
    public function calculateFees(
        string $participantCategory,
        array $eventCodes,
        Carbon $registrationDate,
        string $participationType = 'in-person'
    ): array {
        $feeDetails = [];
        $totalFee = 0.0;

        $mainConferenceCode = config('fee_calculation.main_conference_code', 'BCSMIF2025');
        $isAttendingMainConference = in_array($mainConferenceCode, $eventCodes);

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

            // Determine period based on registration date and event's early deadline (AC4 & AC5 logic placeholder)
            // For AC3, this logic is simplified but present to allow fee fetching.
            $period = ($event->registration_deadline_early && $registrationDate->lte($event->registration_deadline_early))
                ? 'early'
                : 'late';

            $feeQuery = $this->feeModel
                ->where('event_code', $eventCode)
                ->where('participant_category', $participantCategory)
                ->where('type', $participationType)
                ->where('period', $period);

            $foundFee = null;

            // Discount logic for workshops if attending main conference (AC6 logic placeholder for fetching)
            if (! $event->is_main_conference && $isAttendingMainConference) {
                $feeWithDiscount = (clone $feeQuery)->where('is_discount_for_main_event_participant', true)->first();
                if ($feeWithDiscount) {
                    $foundFee = $feeWithDiscount;
                }
            }

            if (! $foundFee) {
                // Fetch standard fee (or workshop fee if not discounted, or main conference fee)
                $feeWithoutDiscount = (clone $feeQuery)->where('is_discount_for_main_event_participant', false)->first();
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
                    // 'fee_object_retrieved' => $foundFee->toArray(), // Useful for debugging AC3
                ];
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
                    /*
                    'query_details' => [ // Useful for debugging AC3
                        'event_code' => $eventCode,
                        'participant_category' => $participantCategory,
                        'type' => $participationType,
                        'period' => $period,
                        'is_main_conference_event' => $event->is_main_conference,
                        'is_attending_main_conference' => $isAttendingMainConference,
                    ]
                    */
                ];
            }
        }

        return [
            'details' => $feeDetails,
            'total_fee' => $totalFee,
        ];
    }
}
