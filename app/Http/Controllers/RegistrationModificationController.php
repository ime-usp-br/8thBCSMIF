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

        // Ensure we're only adding new events (not already registered)
        $currentEventCodes = $registration->events->pluck('code')->toArray();

        /** @var array{selected_event_codes: list<string>} $validatedData */
        $selectedEventCodes = $validatedData['selected_event_codes'];
        $newEventCodes = array_diff($selectedEventCodes, $currentEventCodes);

        if (empty($newEventCodes)) {
            return redirect()->route('registrations.my')->with('error', __('All selected events are already in your registration.'));
        }

        $feeCalculationService = app(FeeCalculationService::class);

        // Get current event codes to preserve existing events
        /** @var list<string> $allEventCodes */
        $allEventCodes = array_values(array_merge($currentEventCodes, array_values($newEventCodes)));

        $feeData = $feeCalculationService->calculateFees(
            $registration->registration_category_snapshot,
            $allEventCodes,
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

        // Only attach new events (incrementally), not sync all events
        $newEventData = [];
        foreach ($feeData['details'] as $eventDetail) {
            if (! isset($eventDetail['error']) && in_array($eventDetail['event_code'], $newEventCodes)) {
                $newEventData[$eventDetail['event_code']] = [
                    'price_at_registration' => $eventDetail['calculated_price'],
                ];
            }
        }

        if (! empty($newEventData)) {
            $registration->events()->attach($newEventData);
        }

        // Send notification to the participant (user)
        Mail::to($registration->user->email)->queue(new RegistrationModifiedNotification($registration));

        // Send notification to the coordinator
        $coordinatorEmail = RegistrationModifiedNotification::getCoordinatorEmail();
        if ($coordinatorEmail) {
            Mail::queue(new RegistrationModifiedNotification($registration, forCoordinator: true));
        }

        Log::info('Registration modified successfully', [
            'registration_id' => $registration->id,
            'user_id' => $registration->user_id,
            'amount_due' => $amountDue,
            'new_events_added' => array_keys($newEventData),
        ]);

        return redirect()->route('registrations.my')->with('success', __('Registration modified successfully'));
    }
}
