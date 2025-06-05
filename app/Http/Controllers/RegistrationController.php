<?php

namespace App\Http\Controllers;

use App\Events\NewRegistrationCreated;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Registration;
use App\Services\FeeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    /**
     * Store a newly created registration in storage.
     *
     * This method handles the incoming registration request, validates the data
     * using StoreRegistrationRequest, calculates fees, saves the registration,
     * and prepares for subsequent steps like payment status update and event syncing.
     */
    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();
        Log::info('Registration data validated successfully through StoreRegistrationRequest.', $validatedData);

        // --- Determine Participant Category for Fee Calculation ---
        /** @var string $position */
        $position = $validatedData['position'];
        /** @var bool $isAbeMember */
        $isAbeMember = $validatedData['is_abe_member'] ?? false;

        $participantCategory = match ($position) {
            'undergrad_student' => 'undergrad_student',
            'grad_student' => 'grad_student',
            'professor' => $isAbeMember ? 'professor_abe' : 'professor_non_abe_professional',
            'professional', 'researcher', 'other' => 'professor_non_abe_professional',
            default => 'professor_non_abe_professional', // Fallback
        };
        if ($position === 'other' || ! in_array($position, ['undergrad_student', 'grad_student', 'professor', 'professional', 'researcher'])) {
            Log::warning(
                "Unhandled or 'other' position during participant category mapping. Defaulting to 'professor_non_abe_professional'.",
                ['position_value' => $position]
            );
        }

        // --- AC7: Calculate Fee using FeeCalculationService ---
        $feeCalculationService = app(FeeCalculationService::class);
        /** @var list<string> $eventCodesForFeeCalc */
        $eventCodesForFeeCalc = $validatedData['selected_event_codes'];
        /** @var string $participationFormatForFeeCalc */
        $participationFormatForFeeCalc = $validatedData['participation_format'];

        $feeData = $feeCalculationService->calculateFees(
            $participantCategory,
            $eventCodesForFeeCalc,
            Carbon::now(),
            $participationFormatForFeeCalc
        );
        $user = $request->user();
        if (! $user) {
            throw new \RuntimeException('User must be authenticated to create registration.');
        }

        Log::info('Fee calculation completed for registration.', [
            'participant_category' => $participantCategory,
            'fee_data' => $feeData,
            'user_id' => $user->id,
        ]);

        // --- AC8: Create and save Registration model ---
        // Prepare data for Registration creation. Eloquent's create method will only use
        // attributes that are fillable in the Registration model from $validatedData.
        $registrationPayload = array_merge(
            $validatedData,
            [
                'user_id' => $user->id,
                'registration_category_snapshot' => $participantCategory,
                'calculated_fee' => $feeData['total_fee'],
                // payment_status will be handled by AC9.
                // Other fields like payment_proof_path, payment_uploaded_at, invoice_sent_at, notes
                // will be null/default on creation or handled by other processes.
            ]
        );

        $registration = Registration::create($registrationPayload);

        Log::info('Registration created successfully.', [
            'registration_id' => $registration->id,
            'user_id' => $user->id,
            'calculated_fee' => $registration->calculated_fee,
            'category_snapshot' => $registration->registration_category_snapshot,
        ]);

        // --- AC9: Set payment_status based on calculated_fee ---
        $paymentStatus = ($feeData['total_fee'] == 0) ? 'free' : 'pending_payment';
        $registration->update(['payment_status' => $paymentStatus]);
        Log::info('Payment status set for registration.', ['registration_id' => $registration->id, 'payment_status' => $paymentStatus]);

        // --- AC10: Sync events with price_at_registration ---
        $eventSyncData = [];
        foreach ($feeData['details'] as $eventDetail) {
            if (! isset($eventDetail['error'])) { // Only sync valid events with prices
                $eventSyncData[$eventDetail['event_code']] = ['price_at_registration' => $eventDetail['calculated_price']];
            }
        }
        if (! empty($eventSyncData)) {
            $registration->events()->sync($eventSyncData);
            Log::info('Events synced for registration.', ['registration_id' => $registration->id, 'synced_events' => array_keys($eventSyncData)]);
        }

        // --- AC11: Dispatch event/notification ---
        event(new NewRegistrationCreated($registration));
        Log::info('NewRegistrationCreated event dispatched.', ['registration_id' => $registration->id]);

        // --- AC12: Redirect to dashboard with success message ---
        return redirect()->route('dashboard')->with('success', __('registrations.created_successfully'));
    }
}
