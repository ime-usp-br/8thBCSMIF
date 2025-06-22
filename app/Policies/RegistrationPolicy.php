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
}
