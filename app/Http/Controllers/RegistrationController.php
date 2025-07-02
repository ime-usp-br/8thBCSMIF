<?php

namespace App\Http\Controllers;

use App\Events\NewRegistrationCreated;
use App\Http\Requests\StoreRegistrationRequest;
use App\Mail\ProofUploadedNotification;
use App\Models\Registration;
use App\Services\FeeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    /**
     * Store a newly created registration in storage.
     *
     * This method handles the incoming registration request, validates the data
     * using StoreRegistrationRequest, calculates fees, saves the registration,
     * and prepares for subsequent steps like payment status update and event syncing.
     */
    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        if (config('app.debug')) {
            Log::debug('Starting registration creation process.');
        }
        $validatedData = $request->validated();
        if (config('app.debug')) {
            Log::debug('Registration data validated successfully through StoreRegistrationRequest.', ['validated_data_keys' => array_keys($validatedData)]);
        }

        try {
            return DB::transaction(function () use ($request, $validatedData) {
                // --- Determine Participant Category for Fee Calculation ---
                /** @var string $position */
                $position = $validatedData['position'];
                /** @var bool $isAbeMember */
                $isAbeMember = $validatedData['is_abe_member'] ?? false;

                $participantCategory = match ($position) {
                    'undergraduate_student' => 'undergrad_student',
                    'graduate_student' => 'grad_student',
                    'professor' => $isAbeMember ? 'professor_abe' : 'professor_non_abe_professional',
                    'professional', 'researcher', 'other' => 'professor_non_abe_professional',
                    default => 'professor_non_abe_professional', // Fallback
                };
                if ($position === 'other' || ! in_array($position, ['undergraduate_student', 'graduate_student', 'professor', 'professional', 'researcher'])) {
                    Log::warning(
                        "Unhandled or 'other' position during participant category mapping. Defaulting to 'professor_non_abe_professional'.",
                        ['position_value' => $position]
                    );
                }

                // --- AC7: Calculate Fee using FeeCalculationService ---
                $feeCalculationService = app(FeeCalculationService::class);
                /** @var list<string> $eventCodesForFeeCalc */
                $eventCodesForFeeCalc = $validatedData['selected_event_codes'];
                /** @var string $participationFormatForFeeCalc */
                $participationFormatForFeeCalc = $validatedData['participation_format'];

                if (config('app.debug')) {
                    Log::debug('Preparing to calculate fees.', [
                        'participant_category' => $participantCategory,
                        'event_codes' => $eventCodesForFeeCalc,
                        'participation_format' => $participationFormatForFeeCalc,
                    ]);
                }

                try {
                    $feeData = $feeCalculationService->calculateFees(
                        $participantCategory,
                        $eventCodesForFeeCalc,
                        Carbon::now(),
                        $participationFormatForFeeCalc
                    );
                } catch (\Exception $e) {
                    if (config('app.debug')) {
                        Log::error('Fee calculation failed during registration', [
                            'participant_category' => $participantCategory,
                            'event_codes' => $eventCodesForFeeCalc,
                            'participation_format' => $participationFormatForFeeCalc,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                    throw new \RuntimeException(__('Unable to calculate registration fees. Please try again or contact support.'), 0, $e);
                }
                $user = $request->user();
                if (! $user) {
                    if (config('app.debug')) {
                        Log::error('Unauthenticated user attempted registration creation');
                    }
                    throw new \RuntimeException(__('You must be logged in to create a registration. Please log in and try again.'));
                }

                if (config('app.debug')) {
                    Log::debug('Fee calculation completed for registration.', [
                        'participant_category' => $participantCategory,
                        'fee_data' => $feeData,
                        'user_id' => $user->id,
                    ]);
                }

                // --- AC8: Create and save Registration model ---
                // Prepare data for Registration creation. Eloquent's create method will only use
                // attributes that are fillable in the Registration model from $validatedData.
                $registrationPayload = array_merge(
                    $validatedData,
                    [
                        'user_id' => $user->id,
                        'registration_category_snapshot' => $participantCategory,
                        // payment_status will be handled by AC9.
                        // Other fields like invoice_sent_at, notes
                        // will be null/default on creation or handled by other processes.
                    ]
                );

                $registration = Registration::create($registrationPayload);

                if (config('app.debug')) {
                    Log::debug('Registration created successfully.', [
                        'registration_id' => $registration->id,
                        'user_id' => $user->id,
                        'total_fee' => $feeData['total_fee'],
                        'category_snapshot' => $registration->registration_category_snapshot,
                    ]);
                }

                // --- AC9: Set payment_status based on calculated_fee ---
                $paymentStatus = ($feeData['total_fee'] == 0) ? 'free' : 'pending_payment';
                $registration->update(['payment_status' => $paymentStatus]);
                if (config('app.debug')) {
                    Log::debug('Payment status set for registration.', ['registration_id' => $registration->id, 'payment_status' => $paymentStatus]);
                }

                // Create individual Payment record for non-free registrations
                if ($feeData['total_fee'] > 0) {
                    if (config('app.debug')) {
                        Log::debug('Registration requires payment. Creating payment record.', [
                            'registration_id' => $registration->id,
                            'total_fee' => $feeData['total_fee'],
                        ]);
                    }
                    try {
                        $payment = $registration->payments()->create([
                            'amount' => $feeData['total_fee'],
                            'status' => 'pending',
                        ]);

                        // AC3: Add post-creation validation to confirm payment was created
                        // The create method on a relationship will return the model instance or throw an exception.
                        // So, if we reach here, $payment is guaranteed to be an object.
                        // This check is redundant and removed to satisfy static analysis.
                        // if (! $payment) {
                        //     Log::error('Payment record was not returned after creation attempt.', [
                        //         'registration_id' => $registration->id,
                        //         'amount' => $feeData['total_fee'],
                        //     ]);
                        //     throw new \RuntimeException('Failed to create payment record: Payment object is null.');
                        // }

                        if (config('app.debug')) {
                            Log::debug('Payment record created for registration.', [
                                'registration_id' => $registration->id,
                                'payment_id' => $payment->id,
                                'amount' => $feeData['total_fee'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        // AC2: Implement robust error handling for payment creation failures
                        Log::error('Failed to create payment record for registration.', [
                            'registration_id' => $registration->id,
                            'amount' => $feeData['total_fee'],
                            'error_message' => $e->getMessage(),
                            'error_trace' => $e->getTraceAsString(),
                        ]);
                        // AC4: If payment not created, rollback registration and return clear error
                        throw new \RuntimeException('Failed to create payment record.', 0, $e);
                    }
                } else {
                    if (config('app.debug')) {
                        Log::debug('Registration is free. No payment record needed.', [
                            'registration_id' => $registration->id,
                            'total_fee' => $feeData['total_fee'],
                        ]);
                    }
                }

                // --- AC10: Sync events with price_at_registration ---
                $eventSyncData = [];
                foreach ($feeData['details'] as $eventDetail) {
                    if (! isset($eventDetail['error'])) { // Only sync valid events with prices
                        $eventSyncData[$eventDetail['event_code']] = ['price_at_registration' => $eventDetail['calculated_price']];
                    }
                }
                if (! empty($eventSyncData)) {
                    try {
                        $registration->events()->sync($eventSyncData);
                        if (config('app.debug')) {
                            Log::debug('Events synced for registration.', ['registration_id' => $registration->id, 'synced_events' => array_keys($eventSyncData)]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to sync events for registration.', [
                            'registration_id' => $registration->id,
                            'synced_events' => array_keys($eventSyncData),
                            'error_message' => $e->getMessage(),
                            'error_trace' => $e->getTraceAsString(),
                        ]);
                        throw new \RuntimeException('Failed to sync events.', 0, $e);
                    }
                }

                // --- AC11: Dispatch event/notification ---
                event(new NewRegistrationCreated($registration));
                if (config('app.debug')) {
                    Log::debug('NewRegistrationCreated event dispatched.', ['registration_id' => $registration->id]);
                }

                // --- AC12: Redirect to registrations page with success message ---
                if (config('app.debug')) {
                    Log::debug('Registration transaction completed successfully. Redirecting user.', ['registration_id' => $registration->id]);
                }

                return redirect()->route('registrations.my')->with('success', __('registrations.created_successfully'));
            });
        } catch (\Exception $e) {
            Log::error('Failed to create registration or payment due to a transaction error.', [
                'user_id' => $request->user()?->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = __('Failed to process your registration. Please try again or contact support.');
            if ($e instanceof \RuntimeException) {
                if (str_contains($e->getMessage(), 'Failed to create payment record.')) {
                    $errorMessage = __('Failed to create payment record for your registration. Please try again or contact support.');
                } elseif (str_contains($e->getMessage(), 'Failed to sync events.')) {
                    $errorMessage = __('Failed to associate events with your registration. Please try again or contact support.');
                } elseif (str_contains($e->getMessage(), 'Unable to calculate registration fees')) {
                    $errorMessage = __('Unable to calculate registration fees. Please verify your event selections and try again.');
                } elseif (str_contains($e->getMessage(), 'You must be logged in')) {
                    $errorMessage = __('You must be logged in to create a registration. Please log in and try again.');
                } else {
                    // Use the localized message from the exception if it's already localized
                    $errorMessage = $e->getMessage();
                }
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Upload payment proof for a specific payment.
     *
     * Handles the upload of payment proof files for Brazilian participants,
     * updates the individual payment status, and dispatches notification to coordinator.
     */
    public function uploadProof(Request $request, Registration $registration): RedirectResponse
    {
        // Validate that user owns this registration
        Gate::authorize('uploadProof', $registration);

        // Validate that we have a payment_id in the request
        $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
            'payment_proof' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:10240', // 10MB max
            ],
        ]);

        // Get the specific payment and validate it belongs to this registration
        $payment = $registration->payments()->where('id', $request->payment_id)->first();
        if (! $payment) {
            return redirect()->back()->with('error', __('Payment not found or does not belong to this registration.'));
        }

        // Validate that payment is in correct status for proof upload
        if ($payment->status !== 'pending') {
            return redirect()->back()->with('error', __('Payment proof can only be uploaded for pending payments.'));
        }

        try {
            // Store the uploaded file with sanitized filename
            $uploadedFile = $request->file('payment_proof');
            if (! $uploadedFile instanceof \Illuminate\Http\UploadedFile) {
                throw new \RuntimeException('No file was uploaded.');
            }

            // Generate sanitized and unique filename
            $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $uploadedFile->getClientOriginalExtension();
            $sanitizedName = Str::slug($originalName);
            $filename = time().'_payment_'.$payment->id.'_'.$sanitizedName.'.'.$extension;

            $path = $uploadedFile->storeAs("proofs/{$registration->id}", $filename, 'private');

            // Update payment with proof details
            $payment->update([
                'payment_proof_path' => $path,
                'payment_date' => Carbon::now(),
                'notes' => __('Payment proof uploaded by user'),
            ]);

            $user = $request->user();
            Log::debug(__('Payment proof uploaded successfully'), [
                'registration_id' => $registration->id,
                'payment_id' => $payment->id,
                'file_path' => $path,
                'user_id' => $user?->id,
            ]);

            // Check if all payments for this registration have proof uploaded
            $pendingPayments = $registration->payments()->where('status', 'pending')->count();
            if ($pendingPayments === 0) {
                // Update registration status if all payments have been submitted
                $registration->update([
                    'payment_uploaded_at' => Carbon::now(),
                    'payment_status' => 'pending_br_proof_approval',
                ]);
            }

            // Dispatch ProofUploadedNotification to coordinator
            $coordinatorEmail = ProofUploadedNotification::getCoordinatorEmail();
            if ($coordinatorEmail) {
                Mail::to($coordinatorEmail)->queue(new ProofUploadedNotification($registration));
                Log::debug(__('Proof upload notification sent to coordinator'), [
                    'registration_id' => $registration->id,
                    'payment_id' => $payment->id,
                    'coordinator_email' => $coordinatorEmail,
                ]);
            }

            return redirect()->back()->with('success', __('Payment proof uploaded successfully. The coordinator will review your submission.'));

        } catch (\Exception $e) {
            $user = $request->user();
            Log::error(__('Failed to upload payment proof'), [
                'registration_id' => $registration->id,
                'payment_id' => $request->payment_id,
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
            ]);

            return redirect()->back()->with('error', __('Failed to upload payment proof. Please try again.'));
        }
    }
}
