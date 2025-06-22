<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationModifiedNotification;
use App\Models\Registration;
use App\Services\FeeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegistrationModificationController extends Controller
{
    public function store(Registration $registration, Request $request): RedirectResponse
    {
        Gate::authorize('update', $registration);

        $validatedData = $request->validate([
            'selected_event_codes' => 'required|array|min:1',
            'selected_event_codes.*' => 'required|string|exists:events,code',
        ]);

        $feeCalculationService = app(FeeCalculationService::class);

        /** @var array{selected_event_codes: list<string>} $validatedData */
        $selectedEventCodes = $validatedData['selected_event_codes'];

        $feeData = $feeCalculationService->calculateFees(
            $registration->registration_category_snapshot,
            $selectedEventCodes,
            now(),
            $registration->participation_format ?? 'in-person',
            $registration
        );

        $amountDue = $feeData['amount_due'] ?? 0.0;

        if ($amountDue > 0) {
            $registration->payments()->create([
                'amount' => $amountDue,
                'status' => 'pending',
            ]);
        }

        $eventSyncData = [];
        foreach ($feeData['details'] as $eventDetail) {
            if (! isset($eventDetail['error'])) {
                $eventSyncData[$eventDetail['event_code']] = [
                    'price_at_registration' => $eventDetail['calculated_price'],
                ];
            }
        }

        if (! empty($eventSyncData)) {
            $registration->events()->sync($eventSyncData);
        }

        // Send notification to the participant (user)
        Mail::to($registration->user->email)->send(new RegistrationModifiedNotification($registration));

        // Send notification to the coordinator
        $coordinatorEmail = RegistrationModifiedNotification::getCoordinatorEmail();
        if ($coordinatorEmail) {
            Mail::send(new RegistrationModifiedNotification($registration, forCoordinator: true));
        }

        Log::info('Registration modified successfully', [
            'registration_id' => $registration->id,
            'user_id' => $registration->user_id,
            'amount_due' => $amountDue,
            'new_events' => array_keys($eventSyncData),
        ]);

        return redirect()->route('registrations.my')->with('success', __('Registration modified successfully'));
    }
}
