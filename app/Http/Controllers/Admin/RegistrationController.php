<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentStatusUpdatedNotification;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationController extends Controller
{
    public function index(): View
    {
        return view('admin.registrations.index');
    }

    public function show(Registration $registration): View
    {
        $registration->load(['user', 'events', 'enrollmentProof']);

        return view('admin.registrations.show', compact('registration'));
    }

    public function downloadProof(Registration $registration): BinaryFileResponse|StreamedResponse|Response
    {
        // TODO: This method needs to be refactored to work with the new Payment model structure
        // where payment_proof_path is now stored in the payments table, not registrations
        abort(501, __('Payment proof download not yet implemented for new payment structure'));
    }

    public function updateStatus(Request $request, Registration $registration): RedirectResponse
    {
        /** @var array{payment_status: string, send_notification?: string} $validated */
        $validated = $request->validate([
            'payment_status' => [
                'required',
                Rule::in([
                    'pending',
                    'pending_approval',
                    'approved',
                    'rejected',
                ]),
            ],
            'send_notification' => ['nullable', 'string', 'in:1'],
        ]);

        // Store the old status for logging
        $oldStatus = $registration->payment_status;
        $newStatus = $validated['payment_status'];

        // Create log entry with admin info, timestamps, and status change details
        $user = $request->user();
        $adminName = (! empty($user->name)) ? $user->name : ($user->email ?? 'Unknown Admin');
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] Payment status changed by {$adminName}: '{$oldStatus}' -> '{$newStatus}'";

        // Append to existing notes or create new notes
        $existingNotes = $registration->notes ? $registration->notes."\n" : '';
        $updatedNotes = $existingNotes.$logEntry;

        $registration->update([
            'payment_status' => $newStatus,
            'notes' => $updatedNotes,
        ]);

        // AC6: Update individual Payment records to automatically remove blocks
        if ($newStatus === 'approved') {
            // When payment is approved, update all pending_approval payments to approved
            $registration->payments()
                ->where('status', 'pending_approval')
                ->update(['status' => 'approved']);
        } elseif ($newStatus === 'rejected') {
            // When payment is rejected, update all pending_approval payments to rejected
            $registration->payments()
                ->where('status', 'pending_approval')
                ->update(['status' => 'rejected']);
        } elseif ($newStatus === 'pending') {
            // When payment is reset to pending, update payments accordingly
            $registration->payments()
                ->whereIn('status', ['pending_approval', 'rejected'])
                ->update(['status' => 'pending']);
        }

        // Send email notification if requested, especially for confirmations
        $sendNotification = isset($validated['send_notification']) && $validated['send_notification'] === '1';
        // @phpstan-ignore-next-line
        if ($sendNotification && $registration->user) {
            $userEmail = $registration->user->email;
            if (! empty($userEmail)) {
                Mail::to($userEmail)->queue(
                    new PaymentStatusUpdatedNotification($registration, $oldStatus, $newStatus)
                );
            }
        }

        return redirect()->route('admin.registrations.show', $registration)
            ->with('success', __('Payment status updated successfully.'));
    }

    public function downloadEnrollmentProof(Registration $registration): BinaryFileResponse|StreamedResponse|Response
    {
        // Check authorization
        Gate::authorize('manageEnrollmentProofs');

        // Check if registration has enrollment proof
        if (! $registration->enrollmentProof) {
            abort(404, __('No enrollment proof found for this registration.'));
        }

        $enrollmentProof = $registration->enrollmentProof;

        // Validate that enrollment proof has a file
        if (! $enrollmentProof->file_path) {
            abort(404, __('Enrollment proof file not found.'));
        }

        // Check if file exists in storage
        if (! Storage::disk('private')->exists($enrollmentProof->file_path)) {
            abort(404, __('Enrollment proof file not found in storage.'));
        }

        // Get original filename for download
        $originalFilename = $enrollmentProof->original_filename ?: basename($enrollmentProof->file_path);

        // Generate a user-friendly filename for admin download
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $friendlyFilename = 'enrollment_proof_reg_'.$registration->id.'_'.time().'.'.($extension ?: 'pdf');

        return Storage::disk('private')->download(
            $enrollmentProof->file_path,
            $friendlyFilename
        );
    }

    public function approveEnrollmentProof(Registration $registration): RedirectResponse
    {
        // Check authorization
        Gate::authorize('manageEnrollmentProofs');

        // Check if registration has enrollment proof
        if (! $registration->enrollmentProof) {
            abort(404, __('No enrollment proof found for this registration.'));
        }

        $enrollmentProof = $registration->enrollmentProof;

        // Update enrollment proof status
        $enrollmentProof->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_reason' => null,
        ]);

        return redirect()->route('admin.registrations.show', $registration)
            ->with('success', __('Enrollment proof approved successfully.'));
    }

    public function rejectEnrollmentProof(Request $request, Registration $registration): RedirectResponse
    {
        // Check authorization
        Gate::authorize('manageEnrollmentProofs');

        // Validate rejection reason
        /** @var array{reason: string} $validated */
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        // Check if registration has enrollment proof
        if (! $registration->enrollmentProof) {
            abort(404, __('No enrollment proof found for this registration.'));
        }

        $enrollmentProof = $registration->enrollmentProof;

        // Update enrollment proof status
        $enrollmentProof->update([
            'status' => 'rejected',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.registrations.show', $registration)
            ->with('success', __('Enrollment proof rejected successfully.'));
    }
}
