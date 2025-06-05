<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    /**
     * Store a newly created registration in storage.
     *
     * This method handles the incoming registration request, validates the data
     * using StoreRegistrationRequest, and then (in future ACs) will proceed
     * to calculate fees, save the registration, and notify the user.
     * For AC6, it primarily ensures that StoreRegistrationRequest is used for validation.
     */
    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        // AC6: Ensure StoreRegistrationRequest is used for validation.
        // The $request object is already validated at this point if this method is reached.
        // If validation fails, StoreRegistrationRequest will automatically throw a
        // ValidationException, and this method's execution will stop before this line.
        $validatedData = $request->validated();

        Log::info('Registration data validated successfully through StoreRegistrationRequest.', $validatedData);

        // Placeholder for subsequent ACs (AC7-AC12) which will handle:
        // - Fee calculation (AC7)
        // - Creating and saving Registration model (AC8)
        // - Setting payment_status (AC9)
        // - Syncing events (AC10)
        // - Dispatching notifications/events (AC11 - covered by Issue #23)
        // - Redirecting with success message (AC12)

        // For AC6, a simple JSON response confirms validation passed.
        // This will be replaced by a RedirectResponse in AC12.
        return response()->json([
            'message' => __('registrations.validation_successful'), // Placeholder message
            'data' => $validatedData,
        ]);
    }
}
