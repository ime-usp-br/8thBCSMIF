<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Services\FeeCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    /**
     * Store a newly created registration in storage.
     *
     * This method handles the incoming registration request, validates the data
     * using StoreRegistrationRequest, and then (in future ACs) will proceed
     * to calculate fees, save the registration, and notify the user.
     * For AC6, it primarily ensures that StoreRegistrationRequest is used for validation.
     * For AC7, it calculates the fee using FeeCalculationService.
     */
    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        // AC6: Ensure StoreRegistrationRequest is used for validation.
        // The $request object is already validated at this point if this method is reached.
        $validatedData = $request->validated();

        Log::info('Registration data validated successfully through StoreRegistrationRequest.', $validatedData);

        // --- AC7: Calculate Fee using FeeCalculationService ---
        /** @var string $position */
        $position = $validatedData['position'];
        // 'is_abe_member' is validated and cast to boolean by StoreRegistrationRequest's 'boolean' rule.
        // If the field is not present in the request, it might not be in $validatedData.
        // Default to false if not present.
        /** @var bool $isAbeMember */
        $isAbeMember = $validatedData['is_abe_member'] ?? false;

        $participantCategory = '';
        switch ($position) {
            case 'undergrad_student':
                $participantCategory = 'undergrad_student';
                break;
            case 'grad_student':
                $participantCategory = 'grad_student';
                break;
            case 'professor':
                $participantCategory = $isAbeMember ? 'professor_abe' : 'professor_non_abe_professional';
                break;
            case 'professional':
            case 'researcher':
                // Map 'other' to 'professor_non_abe_professional' as a general fallback.
                // This aligns with the structure of FeesTableSeeder where this category
                // often serves as a catch-all for non-student, non-ABE-member professors.
                // If 'other' requires distinct fee configurations, FeesTableSeeder and this mapping
                // would need to be updated accordingly.
            case 'other':
                $participantCategory = 'professor_non_abe_professional';
                break;
            default:
                // This case should ideally not be reached if 'position' validation is exhaustive
                // and only allows predefined values.
                Log::warning(
                    "Unhandled position during participant category mapping. Defaulting to 'professor_non_abe_professional'.",
                    ['position_value' => $position] // Structured logging for PHPStan
                );
                $participantCategory = 'professor_non_abe_professional'; // Fallback to a known category
        }

        // Resolve FeeCalculationService from the container.
        // Its dependencies (Event, Fee models) will be auto-resolved by Laravel's service container.
        $feeCalculationService = app(FeeCalculationService::class);

        // Prepare parameters for FeeCalculationService with explicit types for PHPStan
        /** @var list<string> $eventCodesForFeeCalc */
        // StoreRegistrationRequest validates selected_event_codes as an array (list) of strings.
        $eventCodesForFeeCalc = $validatedData['selected_event_codes'];

        /** @var string $participationFormatForFeeCalc */
        // StoreRegistrationRequest validates participation_format as a string.
        $participationFormatForFeeCalc = $validatedData['participation_format'];

        $feeData = $feeCalculationService->calculateFees(
            $participantCategory,
            $eventCodesForFeeCalc,
            Carbon::now(), // Use current date for registration date
            $participationFormatForFeeCalc
        );

        Log::info('Fee calculation completed for registration.', [
            'participant_category' => $participantCategory,
            'fee_data' => $feeData,
            'user_id' => auth()->id(), // Log user context
        ]);
        // --- End AC7 ---

        // Placeholder for subsequent ACs (AC8-AC12) which will handle:
        // - Creating and saving Registration model (AC8)
        // - Setting payment_status (AC9)
        // - Syncing events (AC10)
        // - Dispatching notifications/events (AC11 - covered by Issue #23)
        // - Redirecting with success message (AC12)

        // For AC7, return feeData along with validatedData.
        // This response will be replaced by a RedirectResponse in AC12.
        return response()->json([
            'message' => __('registrations.validation_successful'),
            'data' => $validatedData,
            'fee_data' => $feeData, // Added for AC7 verification
        ]);
    }
}
