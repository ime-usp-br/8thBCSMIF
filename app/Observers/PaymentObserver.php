<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     * Update related registration status when a new payment is created.
     */
    public function created(Payment $payment): void
    {
        $this->updateRegistrationStatus($payment);
    }

    /**
     * Handle the Payment "updated" event.
     * Update related registration status when payment status changes.
     */
    public function updated(Payment $payment): void
    {
        // Only update if status was changed
        if ($payment->wasChanged('status')) {
            $this->updateRegistrationStatus($payment);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     * Update related registration status when a payment is deleted.
     */
    public function deleted(Payment $payment): void
    {
        $this->updateRegistrationStatus($payment);
    }

    /**
     * Handle the Payment "restored" event.
     * Update related registration status when a payment is restored.
     */
    public function restored(Payment $payment): void
    {
        $this->updateRegistrationStatus($payment);
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }

    /**
     * Update the registration status based on payment changes.
     */
    private function updateRegistrationStatus(Payment $payment): void
    {
        // Load the registration if not already loaded
        if (! $payment->relationLoaded('registration')) {
            $payment->load('registration');
        }

        // Update the registration status
        $payment->registration->updatePaymentStatusFromRelatedModels();
    }
}
