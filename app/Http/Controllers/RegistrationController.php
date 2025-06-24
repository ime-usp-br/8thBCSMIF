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
        $validatedData = $request->validated();
        Log::info('Registration data validated successfully through StoreRegistrationRequest.', $validatedData);

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

        $feeData = $feeCalculationService->calculateFees(
            $participantCategory,
            $eventCodesForFeeCalc,
            Carbon::now(),
            $participationFormatForFeeCalc
        );
        $user = $request->user();
        if (! $user) {
            throw new \RuntimeException('User must be authenticated to create registration.');
        }

        Log::info('Fee calculation completed for registration.', [
            'participant_category' => $participantCategory,
            'fee_data' => $feeData,
            'user_id' => $user->id,
        ]);

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

        Log::info('Registration created successfully.', [
            'registration_id' => $registration->id,
            'user_id' => $user->id,
            'total_fee' => $feeData['total_fee'],
            'category_snapshot' => $registration->registration_category_snapshot,
        ]);

        // --- AC9: Set payment_status based on calculated_fee ---
        $paymentStatus = ($feeData['total_fee'] == 0) ? 'free' : 'pending_payment';
        $registration->update(['payment_status' => $paymentStatus]);
        Log::info('Payment status set for registration.', ['registration_id' => $registration->id, 'payment_status' => $paymentStatus]);

        // Create individual Payment record for non-free registrations
        if ($feeData['total_fee'] > 0) {
            try {
                $payment = $registration->payments()->create([
                    'amount' => $feeData['total_fee'],
                    'status' => 'pending',
                ]);

                // Validate payment was actually created
                if ($payment === null || $payment->id === null) {
                    throw new \RuntimeException('Payment creation failed - record not persisted');
                }

                Log::info('Payment record created for registration.', [
                    'registration_id' => $registration->id,
                    'payment_id' => $payment->id,
                    'amount' => $feeData['total_fee'],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create payment record for registration.', [
                    'registration_id' => $registration->id,
                    'amount' => $feeData['total_fee'],
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                ]);

                // Rollback registration and return error to user
                $registration->delete();

                return redirect()->back()
                    ->withInput()
                    ->with('error', __('Failed to process payment information. Please try again or contact support.'));
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
            $registration->events()->sync($eventSyncData);
            Log::info('Events synced for registration.', ['registration_id' => $registration->id, 'synced_events' => array_keys($eventSyncData)]);
        }

        // --- AC11: Dispatch event/notification ---
        event(new NewRegistrationCreated($registration));
        Log::info('NewRegistrationCreated event dispatched.', ['registration_id' => $registration->id]);

        // --- AC12: Redirect to registrations page with success message ---
        return redirect()->route('registrations.my')->with('success', __('registrations.created_successfully'));
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
            Log::info(__('Payment proof uploaded successfully'), [
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
                Mail::to($coordinatorEmail)->send(new ProofUploadedNotification($registration));
                Log::info(__('Proof upload notification sent to coordinator'), [
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
