<?php

namespace App\Http\Controllers;

use App\Mail\ProofUploadedNotification;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    /**
     * Upload payment proof for a specific payment.
     */
    public function uploadProof(Request $request, Payment $payment): RedirectResponse
    {
        // Validate that user owns this payment (through registration)
        Gate::authorize('uploadProof', $payment->registration);

        // Validate the uploaded file
        $request->validate([
            'payment_proof' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:10240', // 10MB max
            ],
        ], [
            'payment_proof.required' => __('Payment proof file is required. Please contact the organization for assistance if you are unable to upload.'),
            'payment_proof.file' => __('Payment proof must be a valid file. Please contact the organization for assistance if you continue to experience issues.'),
            'payment_proof.mimes' => __('Payment proof must be a JPG, JPEG, PNG, or PDF file. Please contact the organization for assistance if your file format is not supported.'),
            'payment_proof.max' => __('Payment proof file size must not exceed 10MB. Please contact the organization for assistance if you need to upload a larger file.'),
        ]);

        // Validate that payment is in correct status for proof upload
        // AC5: Allow re-upload for rejected payments
        if (! in_array($payment->status, ['pending', 'rejected'])) {
            return redirect()->back()->with('error', __('Payment proof can only be uploaded for pending or rejected payments.'));
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

            $path = $uploadedFile->storeAs("proofs/{$payment->registration->id}", $filename, 'private');

            // Update payment with proof details and status
            // AC5: Handle re-upload case for rejected payments
            $isReupload = $payment->status === 'rejected';
            $notes = $isReupload
                ? __('Payment proof re-uploaded by user after rejection')
                : __('Payment proof uploaded by user');

            $payment->update([
                'payment_proof_path' => $path,
                'payment_date' => Carbon::now(),
                'status' => 'pending_approval',
                'notes' => $notes,
            ]);

            $user = $request->user();
            // AC5: Log re-upload status for better tracking
            $logMessage = $isReupload
                ? __('Payment proof re-uploaded successfully after rejection')
                : __('Payment proof uploaded successfully');

            Log::info($logMessage, [
                'registration_id' => $payment->registration->id,
                'payment_id' => $payment->id,
                'file_path' => $path,
                'user_id' => $user?->id,
                'is_reupload' => $isReupload,
            ]);

            // Check if all payments for this registration have proof uploaded
            $pendingPayments = $payment->registration->payments()->where('status', 'pending')->count();
            if ($pendingPayments === 0) {
                // Update registration status if all payments have been submitted for approval
                $payment->registration->update([
                    'payment_uploaded_at' => Carbon::now(),
                    'status' => 'pending_approval',
                ]);
            }

            // Dispatch ProofUploadedNotification to coordinator
            $coordinatorEmail = ProofUploadedNotification::getCoordinatorEmail();
            if ($coordinatorEmail) {
                Mail::to($coordinatorEmail)->queue(new ProofUploadedNotification($payment->registration));
                Log::info(__('Proof upload notification sent to coordinator'), [
                    'registration_id' => $payment->registration->id,
                    'payment_id' => $payment->id,
                    'coordinator_email' => $coordinatorEmail,
                ]);
            }

            // AC5: Different success message for re-uploads
            $successMessage = $isReupload
                ? __('Payment proof re-uploaded successfully. The coordinator will review your new submission.')
                : __('Payment proof uploaded successfully. The coordinator will review your submission.');

            return redirect()->back()->with('success', $successMessage);

        } catch (\Exception $e) {
            $user = $request->user();
            Log::error(__('Failed to upload payment proof'), [
                'registration_id' => $payment->registration->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
            ]);

            return redirect()->back()->with('error', __('Failed to upload payment proof. Please contact the organization for assistance.'));
        }
    }

    /**
     * Download payment proof for a specific payment.
     * Only the owner of the payment can download their proof.
     */
    public function downloadProof(Payment $payment): BinaryFileResponse|StreamedResponse
    {
        // Validate that user owns this payment (through registration)
        Gate::authorize('uploadProof', $payment->registration);

        // Validate that payment has a proof file
        if (! $payment->payment_proof_path) {
            abort(404, __('Payment proof not found.'));
        }

        // Check if file exists in storage
        if (! Storage::disk('private')->exists($payment->payment_proof_path)) {
            abort(404, __('Payment proof file not found in storage.'));
        }

        $user = request()->user();
        Log::info(__('Payment proof downloaded'), [
            'registration_id' => $payment->registration->id,
            'payment_id' => $payment->id,
            'file_path' => $payment->payment_proof_path,
            'user_id' => $user?->id,
        ]);

        // Get original filename for download
        $originalFilename = basename($payment->payment_proof_path);

        // Generate a user-friendly filename
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $friendlyFilename = 'payment_proof_'.$payment->id.'.'.($extension ?: 'pdf');

        return Storage::disk('private')->download(
            $payment->payment_proof_path,
            $friendlyFilename
        );
    }
}
