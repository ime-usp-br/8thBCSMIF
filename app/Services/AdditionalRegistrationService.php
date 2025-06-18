<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling additional event registrations for users who already have completed registrations.
 */
class AdditionalRegistrationService
{
    protected FeeCalculationService $feeCalculationService;

    public function __construct(FeeCalculationService $feeCalculationService)
    {
        $this->feeCalculationService = $feeCalculationService;
    }

    /**
     * Calculate fees for additional events, considering already paid events.
     *
     * @param  list<string>  $newEventCodes
     * @return array{can_register: bool, total_new_fee: float, difference_to_pay: float, details: list<array{event_code: string, event_name: string, calculated_price: float}>, existing_payments: list<mixed>, message?: string}
     */
    public function calculateAdditionalEventsFees(
        User $user,
        array $newEventCodes,
        string $participantCategory,
        string $participationType = 'in-person'
    ): array {
        // Get user's existing registration
        $existingRegistration = $user->registration;
        if (! $existingRegistration) {
            return [
                'can_register' => false,
                'total_new_fee' => 0.0,
                'difference_to_pay' => 0.0,
                'details' => [],
                'existing_payments' => [],
                'message' => __('No existing registration found'),
            ];
        }

        // Get all existing registered events (regardless of payment status)
        $existingEventCodes = $existingRegistration->events->pluck('code')->toArray();

        // Get existing paid events for payment calculation
        $existingPaidPayments = $user->payments()
            ->where('payment_status', 'like', 'paid_%')
            ->with('events')
            ->get();

        $existingPaidEventCodes = $existingPaidPayments
            ->flatMap(fn ($payment) => $payment->events->pluck('code'))
            ->unique()
            ->toArray();

        // Filter out events already registered (whether paid or not)
        $uniqueNewEventCodes = array_diff($newEventCodes, $existingEventCodes);

        // Check if user is trying to add events that are already paid
        $attemptingToAddPaidEvents = array_intersect($uniqueNewEventCodes, $existingPaidEventCodes);
        if (! empty($attemptingToAddPaidEvents)) {
            return [
                'can_register' => false,
                'total_new_fee' => 0.0,
                'difference_to_pay' => 0.0,
                'details' => [],
                'existing_payments' => array_values($existingPaidPayments->toArray()),
                'message' => __('Some events are already paid and cannot be modified. Paid events are non-refundable.'),
            ];
        }

        if (empty($uniqueNewEventCodes)) {
            return [
                'can_register' => false,
                'total_new_fee' => 0.0,
                'difference_to_pay' => 0.0,
                'details' => [],
                'existing_payments' => array_values($existingPaidPayments->toArray()),
                'message' => __('All selected events are already registered'),
            ];
        }

        // Calculate fees for new events, including potential discounts
        $allEventCodes = array_values(array_merge($existingEventCodes, $uniqueNewEventCodes));
        /** @var list<string> $allEventCodes */
        $feeCalculation = $this->feeCalculationService->calculateFees(
            $participantCategory,
            $allEventCodes,
            now(),
            $participationType
        );

        // Calculate total fee for all events (existing + new)
        $totalFeeForAllEvents = $feeCalculation['total_fee'];

        // Calculate what was already paid by event
        $paidByEvent = [];
        foreach ($existingPaidPayments as $payment) {
            foreach ($payment->events as $event) {
                $eventCode = $event->code;
                /** @phpstan-ignore-next-line property.nonObject */
                $eventPrice = $event->pivot->individual_price ?? 0;
                /** @phpstan-ignore-next-line argument.type */
                $paidByEvent[$eventCode] = ($paidByEvent[$eventCode] ?? 0) + floatval($eventPrice);
            }
        }

        // Calculate amount still owed considering what was already paid per event
        $amountStillOwed = 0;
        foreach ($feeCalculation['details'] as $detail) {
            $eventCode = $detail['event_code'];
            $eventPrice = $detail['calculated_price'];
            $paidForThisEvent = $paidByEvent[$eventCode] ?? 0;

            // Only count as owed if less was paid than the current price
            if ($paidForThisEvent < $eventPrice) {
                $amountStillOwed += ($eventPrice - $paidForThisEvent);
            }
        }

        // Get fees for new events only (for details display)
        $newEventsFees = array_values(array_filter(
            $feeCalculation['details'],
            fn ($detail) => in_array($detail['event_code'], $uniqueNewEventCodes)
        ));

        $totalNewFee = array_sum(array_column($newEventsFees, 'calculated_price'));

        return [
            'can_register' => true,
            'total_new_fee' => $totalNewFee,
            'difference_to_pay' => $amountStillOwed, // Amount still owed considering existing payments
            'details' => $newEventsFees,
            'existing_payments' => array_values($existingPaidPayments->toArray()),
            'message' => $amountStillOwed > 0
                ? __('Additional payment required for new events')
                : __('Selected events are free'),
        ];
    }

    /**
     * Create additional registration for new events.
     *
     * @param  list<string>  $newEventCodes
     * @return array{success: bool, payment?: Payment, message: string}
     */
    public function createAdditionalRegistration(
        User $user,
        array $newEventCodes,
        string $participantCategory,
        string $participationType = 'in-person',
        string $paymentMethod = 'bank_transfer'
    ): array {
        return DB::transaction(function () use ($user, $newEventCodes, $participantCategory, $participationType, $paymentMethod) {
            // Calculate fees for additional events
            $feeCalculation = $this->calculateAdditionalEventsFees(
                $user,
                $newEventCodes,
                $participantCategory,
                $participationType
            );

            if (! $feeCalculation['can_register']) {
                return [
                    'success' => false,
                    'message' => $feeCalculation['message'] ?? __('Cannot register for additional events'),
                ];
            }

            // Get user's existing registration
            $registration = $user->registration;
            if (! $registration) {
                return [
                    'success' => false,
                    'message' => __('No existing registration found'),
                ];
            }

            // Create new payment if there's an amount to pay
            $payment = null;
            if ($feeCalculation['difference_to_pay'] > 0) {
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_reference' => Payment::generatePaymentReference(),
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'pending_payment',
                    'total_amount' => $feeCalculation['difference_to_pay'],
                ]);

                // Associate events with payment
                foreach ($feeCalculation['details'] as $eventDetail) {
                    $payment->events()->attach($eventDetail['event_code'], [
                        'individual_price' => $eventDetail['calculated_price'],
                        'registration_id' => $registration->id,
                    ]);
                }
            } else {
                // If no payment needed, create a zero-amount payment for tracking
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_reference' => Payment::generatePaymentReference(),
                    'payment_method' => 'none',
                    'payment_status' => 'paid_br', // Mark as paid since no payment needed
                    'total_amount' => 0.00,
                ]);

                // Associate free events with payment
                foreach ($feeCalculation['details'] as $eventDetail) {
                    $payment->events()->attach($eventDetail['event_code'], [
                        'individual_price' => 0.00,
                        'registration_id' => $registration->id,
                    ]);
                }
            }

            // Also add events to the existing registration for compatibility
            foreach ($newEventCodes as $eventCode) {
                $eventDetail = collect($feeCalculation['details'])
                    ->firstWhere('event_code', $eventCode);
                $eventPrice = $eventDetail ? $eventDetail['calculated_price'] : 0.00;

                $registration->events()->syncWithoutDetaching([
                    $eventCode => ['price_at_registration' => $eventPrice],
                ]);
            }

            Log::info('Additional registration created', [
                'user_id' => $user->id,
                'new_events' => $newEventCodes,
                'payment_id' => $payment->id,
                'amount' => $feeCalculation['difference_to_pay'],
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'message' => $feeCalculation['difference_to_pay'] > 0
                    ? __('Additional registration created. Payment required.')
                    : __('Additional registration completed successfully.'),
            ];
        });
    }

    /**
     * Get all events a user has access to (paid or free).
     *
     * @return list<mixed>
     */
    public function getUserAccessibleEvents(User $user): array
    {
        $paidEvents = $user->payments()
            ->where('payment_status', 'like', 'paid_%')
            ->with('events')
            ->get()
            ->flatMap(fn ($payment) => $payment->events)
            ->unique('code');

        return array_values($paidEvents->toArray());
    }

    /**
     * Check if user can register for specific events.
     * Paid events are immutable and cannot be registered again.
     *
     * @param  list<string>  $eventCodes
     * @return array{can_register: bool, message: string, blocked_events?: list<string>}
     */
    public function canUserRegisterForEvents(User $user, array $eventCodes): array
    {
        // Get events that are already paid (immutable)
        $immutableEventCodes = $user->getImmutableEventCodes();
        $alreadyPaidEvents = array_intersect($eventCodes, $immutableEventCodes);

        if (! empty($alreadyPaidEvents)) {
            return [
                'can_register' => false,
                'message' => __('Some events are already paid and cannot be modified. Paid events are non-refundable.'),
                'blocked_events' => array_values($alreadyPaidEvents),
            ];
        }

        // Also check for any other accessible events (for completeness)
        $accessibleEvents = collect($this->getUserAccessibleEvents($user))
            ->pluck('code')
            ->toArray();

        $alreadyRegisteredEvents = array_intersect($eventCodes, $accessibleEvents);

        if (! empty($alreadyRegisteredEvents)) {
            return [
                'can_register' => false,
                'message' => __('Some events are already registered'),
                'blocked_events' => array_values($alreadyRegisteredEvents),
            ];
        }

        return [
            'can_register' => true,
            'message' => __('Can register for all selected events'),
        ];
    }
}
