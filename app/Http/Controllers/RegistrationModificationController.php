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
        Gate::authorize('modify', $registration);

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

        // Create payments strategically based on event types and participant category
        $this->createAppropriatePayments($registration, $amountDue, $newEventCodes, $feeData);

        // Send notification to the participant (user)
        Mail::to($registration->user->email)->queue(new RegistrationModifiedNotification($registration));

        // Send notification to the coordinator
        $coordinatorEmail = RegistrationModifiedNotification::getCoordinatorEmail();
        if ($coordinatorEmail) {
            Mail::to($coordinatorEmail)->queue(new RegistrationModifiedNotification($registration, forCoordinator: true));
        }

        Log::info('Registration modified successfully', [
            'registration_id' => $registration->id,
            'user_id' => $registration->user_id,
            'amount_due' => $amountDue,
            'new_events_added' => array_keys($newEventData),
        ]);

        return redirect()->route('registrations.my')->with('success', __('Registration modified successfully'));
    }

    /**
     * Creates appropriate payments based on event types and participant category.
     * This method prevents duplicate payments by consolidating all logic.
     *
     * @param  array<string>  $newEventCodes
     * @param  array{details: list<array{event_code: string, event_name: string, calculated_price: float, error?: string}>, total_fee: float, new_total_fee?: float, total_paid?: float, amount_due?: float}  $feeData
     */
    private function createAppropriatePayments(Registration $registration, float $amountDue, array $newEventCodes, array $feeData): void
    {
        // Get all new events that were just added with pivot data
        $newEvents = $registration->events()
            ->whereIn('code', $newEventCodes)
            ->withPivot('price_at_registration')
            ->get();

        $paidEvents = [];
        $freeEvents = [];

        // Categorize new events by payment requirement using fee calculation data
        foreach ($feeData['details'] as $eventDetail) {
            if (! isset($eventDetail['error']) && in_array($eventDetail['event_code'], $newEventCodes)) {
                $calculatedPrice = (float) $eventDetail['calculated_price'];
                $eventCode = $eventDetail['event_code'];

                if ($calculatedPrice > 0) {
                    $paidEvents[] = $eventCode;
                } else {
                    $freeEvents[] = $eventCode;
                }
            }
        }

        // Create a single payment for all paid events if there's an amount due
        if ($amountDue > 0 && ! empty($paidEvents)) {
            $payment = $registration->payments()->create([
                'amount' => $amountDue,
                'status' => 'pending',
            ]);

            // Associate the payment with paid events
            foreach ($paidEvents as $eventCode) {
                $payment->events()->attach($eventCode);
            }
        }

        // Auto-approve payments for grad students in free workshops
        // but only if main conference is not registered or already approved
        if ($registration->registration_category_snapshot === 'grad_student' && ! empty($freeEvents)) {
            // Check if registration includes main conference
            $hasMainConference = $registration->events()->where('is_main_conference', true)->exists();

            // Check if main conference payment is approved (if main conference is registered)
            $mainConferencePaymentApproved = false;
            if ($hasMainConference) {
                $mainConferencePaymentApproved = $registration->payments()
                    ->whereIn('status', ['approved', 'paid', 'paid_br', 'paid_int'])
                    ->whereHas('events', function ($query) {
                        $query->where('is_main_conference', true);
                    })
                    ->exists();
            }

            // Only auto-approve workshop payments if:
            // 1. No main conference registration, OR
            // 2. Main conference payment is already approved
            $shouldAutoApproveWorkshops = ! $hasMainConference || $mainConferencePaymentApproved;

            if ($shouldAutoApproveWorkshops) {
                foreach ($freeEvents as $eventCode) {
                    // Get event to check if it's a workshop
                    $event = $newEvents->where('code', $eventCode)->first();

                    if ($event && ! $event->is_main_conference) {
                        // Check if a payment for this workshop already exists
                        $existingPayment = $registration->payments()
                            ->whereHas('events', function ($query) use ($eventCode) {
                                $query->where('event_code', $eventCode);
                            })
                            ->exists();

                        if (! $existingPayment) {
                            $payment = $registration->payments()->create([
                                'amount' => 0.00,
                                'status' => 'approved',
                                'payment_date' => now(),
                                'notes' => __('Free workshop for graduate students'),
                            ]);
                            $payment->events()->attach($eventCode);
                        }
                    }
                }
            }
        }
    }
}
