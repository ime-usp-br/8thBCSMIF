<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    /**
     * Determine whether the user can upload a payment proof for the registration.
     */
    public function uploadProof(User $user, Registration $registration): bool
    {
        return $user->id === $registration->user_id;
    }

    /**
     * Determine whether the user can update/modify the registration.
     */
    public function update(User $user, Registration $registration): bool
    {
        return $user->id === $registration->user_id;
    }

    /**
     * Determine whether the user can modify the registration (add events).
     * Blocked when any payment has status 'pending_br_proof_approval'.
     */
    public function modify(User $user, Registration $registration): bool
    {
        // AC7: Administrators can modify registrations regardless of block status
        if ($user->hasRole('admin')) {
            return true;
        }

        // User must own the registration
        if ($user->id !== $registration->user_id) {
            return false;
        }

        // Check if any payment has status 'pending_br_proof_approval'
        return ! $registration->payments()
            ->where('status', 'pending_br_proof_approval')
            ->exists();
    }
}
