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
use Illuminate\Support\Str;

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
        ]);

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

            $path = $uploadedFile->storeAs("proofs/{$payment->registration->id}", $filename, 'private');

            // Update payment with proof details
            $payment->update([
                'payment_proof_path' => $path,
                'payment_date' => Carbon::now(),
                'notes' => __('Payment proof uploaded by user'),
            ]);

            $user = $request->user();
            Log::info(__('Payment proof uploaded successfully'), [
                'registration_id' => $payment->registration->id,
                'payment_id' => $payment->id,
                'file_path' => $path,
                'user_id' => $user?->id,
            ]);

            // Check if all payments for this registration have proof uploaded
            $pendingPayments = $payment->registration->payments()->where('status', 'pending')->count();
            if ($pendingPayments === 0) {
                // Update registration status if all payments have been submitted
                $payment->registration->update([
                    'payment_uploaded_at' => Carbon::now(),
                    'payment_status' => 'pending_br_proof_approval',
                ]);
            }

            // Dispatch ProofUploadedNotification to coordinator
            $coordinatorEmail = ProofUploadedNotification::getCoordinatorEmail();
            if ($coordinatorEmail) {
                Mail::to($coordinatorEmail)->send(new ProofUploadedNotification($payment->registration));
                Log::info(__('Proof upload notification sent to coordinator'), [
                    'registration_id' => $payment->registration->id,
                    'payment_id' => $payment->id,
                    'coordinator_email' => $coordinatorEmail,
                ]);
            }

            return redirect()->back()->with('success', __('Payment proof uploaded successfully. The coordinator will review your submission.'));

        } catch (\Exception $e) {
            $user = $request->user();
            Log::error(__('Failed to upload payment proof'), [
                'registration_id' => $payment->registration->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
            ]);

            return redirect()->back()->with('error', __('Failed to upload payment proof. Please try again.'));
        }
    }
}
