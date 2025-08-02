<?php

namespace App\Observers;

use App\Models\EnrollmentProof;

class EnrollmentProofObserver
{
    /**
     * Handle the EnrollmentProof "created" event.
     * Update related registration status when a new enrollment proof is created.
     */
    public function created(EnrollmentProof $enrollmentProof): void
    {
        $this->updateRegistrationStatus($enrollmentProof);
    }

    /**
     * Handle the EnrollmentProof "updated" event.
     * Update related registration status when enrollment proof status changes.
     */
    public function updated(EnrollmentProof $enrollmentProof): void
    {
        // Only update if status was changed
        if ($enrollmentProof->wasChanged('status')) {
            $this->updateRegistrationStatus($enrollmentProof);
        }
    }

    /**
     * Handle the EnrollmentProof "deleted" event.
     * Update related registration status when an enrollment proof is deleted.
     */
    public function deleted(EnrollmentProof $enrollmentProof): void
    {
        $this->updateRegistrationStatus($enrollmentProof);
    }

    /**
     * Handle the EnrollmentProof "restored" event.
     * Update related registration status when an enrollment proof is restored.
     */
    public function restored(EnrollmentProof $enrollmentProof): void
    {
        $this->updateRegistrationStatus($enrollmentProof);
    }

    /**
     * Handle the EnrollmentProof "force deleted" event.
     */
    public function forceDeleted(EnrollmentProof $enrollmentProof): void
    {
        //
    }

    /**
     * Update the registration status based on enrollment proof changes.
     */
    private function updateRegistrationStatus(EnrollmentProof $enrollmentProof): void
    {
        // Load the registration if not already loaded
        if (! $enrollmentProof->relationLoaded('registration')) {
            $enrollmentProof->load('registration');
        }

        // Update the registration status
        $enrollmentProof->registration->updateStatusFromRelatedModels();
    }
}
