<?php

namespace App\Observers;

use App\Models\Registration;

class RegistrationObserver
{
    /**
     * Handle the Registration "created" event.
     * Initialize the payment status based on related models.
     */
    public function created(Registration $registration): void
    {
        // Update payment status after creation when related models exist
        $registration->updatePaymentStatusFromRelatedModels();
    }

    /**
     * Handle the Registration "updated" event.
     * Update payment status if registration category changes.
     */
    public function updated(Registration $registration): void
    {
        // If registration category changed, recalculate status
        if ($registration->wasChanged('registration_category_snapshot')) {
            $registration->updatePaymentStatusFromRelatedModels();
        }
    }

    /**
     * Handle the Registration "deleted" event.
     */
    public function deleted(Registration $registration): void
    {
        //
    }

    /**
     * Handle the Registration "restored" event.
     */
    public function restored(Registration $registration): void
    {
        //
    }

    /**
     * Handle the Registration "force deleted" event.
     */
    public function forceDeleted(Registration $registration): void
    {
        //
    }
}
