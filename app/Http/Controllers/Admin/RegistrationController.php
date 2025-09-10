<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentRejectedNotification;
use App\Mail\PaymentStatusUpdatedNotification;
use App\Models\Registration;
use App\Services\RegistrationExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

    public function downloadProof(Registration $registration): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Get the most recent payment with a proof
        $payment = $registration->payments()
            ->whereNotNull('payment_proof_path')
            ->latest()
            ->first();

        if (! $payment || ! $payment->payment_proof_path) {
            abort(404, __('No payment proof found for this registration.'));
        }

        // Check if file exists in storage
        if (! Storage::disk('private')->exists($payment->payment_proof_path)) {
            abort(404, __('Payment proof file not found in storage.'));
        }

        // Generate a user-friendly filename for admin download
        $extension = pathinfo($payment->payment_proof_path, PATHINFO_EXTENSION);
        $friendlyFilename = 'payment_proof_reg_'.$registration->id.'_'.time().'.'.($extension ?: 'pdf');

        return Storage::disk('private')->download(
            $payment->payment_proof_path,
            $friendlyFilename
        );
    }

    public function updateStatus(Request $request, Registration $registration): RedirectResponse
    {
        /** @var array{status: string, send_notification?: string} $validated */
        $validated = $request->validate([
            'status' => [
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
        $oldStatus = $registration->status;
        $newStatus = $validated['status'];

        // Create log entry with admin info, timestamps, and status change details
        $user = $request->user();
        $adminName = (! empty($user->name)) ? $user->name : ($user->email ?? 'Unknown Admin');
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] Status changed by {$adminName}: '{$oldStatus}' -> '{$newStatus}'";

        // Append to existing notes or create new notes
        $existingNotes = $registration->notes ? $registration->notes."\n" : '';
        $updatedNotes = $existingNotes.$logEntry;

        $registration->update([
            'status' => $newStatus,
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
            ->with('success', __('Status updated successfully.'));
    }

    public function downloadEnrollmentProof(Registration $registration): \Symfony\Component\HttpFoundation\StreamedResponse
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

    /**
     * AC2: Approve payment proof for a registration.
     * Returns JSON response for async handling.
     */
    public function approvePayment(Registration $registration): JsonResponse
    {
        // Get the most recent payment with proof
        $payment = $registration->payments()
            ->whereNotNull('payment_proof_path')
            ->where('status', 'pending_approval')
            ->latest()
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => __('No pending payment proof found for this registration.'),
            ], 404);
        }

        // Update payment status
        $payment->update([
            'status' => 'approved',
        ]);

        // Update registration status if needed
        $registration->updateStatusFromRelatedModels();

        // Create log entry
        $user = request()->user();
        $adminName = (! empty($user->name)) ? $user->name : ($user->email ?? 'Unknown Admin');
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] Payment proof approved by {$adminName}";

        // Append to existing notes
        $existingNotes = $registration->notes ? $registration->notes."\n" : '';
        $registration->update(['notes' => $existingNotes.$logEntry]);

        return response()->json([
            'success' => true,
            'message' => __('Payment proof approved successfully.'),
        ]);
    }

    /**
     * AC2: Reject payment proof for a registration with reason.
     * Returns JSON response for async handling.
     */
    public function rejectPayment(Request $request, Registration $registration): JsonResponse
    {
        // Validate rejection reason
        /** @var array{reason: string} $validated */
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        // Get the most recent payment with proof
        $payment = $registration->payments()
            ->whereNotNull('payment_proof_path')
            ->where('status', 'pending_approval')
            ->latest()
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => __('No pending payment proof found for this registration.'),
            ], 404);
        }

        // Update payment status and add rejection reason to dedicated field
        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
        ]);

        // Update registration status if needed
        $registration->updateStatusFromRelatedModels();

        // Create log entry
        $user = $request->user();
        $adminName = (! empty($user->name)) ? $user->name : ($user->email ?? 'Unknown Admin');
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] Payment proof rejected by {$adminName}: {$validated['reason']}";

        // Append to existing notes
        $existingNotes = $registration->notes ? $registration->notes."\n" : '';
        $registration->update(['notes' => $existingNotes.$logEntry]);

        // Send rejection notification email
        $userEmail = $registration->user->email ?? $registration->email;
        Mail::to($userEmail)->queue(
            new PaymentRejectedNotification($registration, $validated['reason'])
        );

        return response()->json([
            'success' => true,
            'message' => __('Payment proof rejected successfully.'),
        ]);
    }

    /**
     * AC1: Show the approvals queue page with pending validations.
     * Displays all payment and enrollment proofs that require admin approval.
     */
    public function approvals(): View
    {
        return view('admin.approvals');
    }

    /**
     * Export filtered registrations to CSV with selected columns
     */
    public function exportCsv(Request $request, RegistrationExportService $exportService): Response
    {
        // Validate request
        /** @var array{columns: array<string>, filters?: array<string, string>} $validated */
        $validated = $request->validate([
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string'],
            'filters' => ['sometimes', 'array'],
            'filters.*' => ['nullable', 'string'],
        ]);

        // Build the same query as RegistrationsList component
        $query = Registration::query()
            ->with(['user', 'events', 'payments', 'enrollmentProof']);

        // Apply filters if provided
        if (isset($validated['filters'])) {
            /** @var array<string, string> $filters */
            $filters = $validated['filters'];

            // Apply the same filtering logic as RegistrationsList
            if (! empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            if (! empty($filters['filterEventCode'])) {
                $query->whereHas('events', function ($eventQuery) use ($filters) {
                    $eventQuery->where('code', $filters['filterEventCode']);
                });
            }

            if (! empty($filters['filterEnrollmentProofStatus'])) {
                $status = $filters['filterEnrollmentProofStatus'];
                if ($status === 'none') {
                    $query->whereDoesntHave('enrollmentProof');
                } else {
                    $query->whereHas('enrollmentProof', function ($proofQuery) use ($status) {
                        $proofQuery->where('status', $status);
                    });
                }
            }

            if (! empty($filters['filterPaymentStatus'])) {
                $query->where('status', $filters['filterPaymentStatus']);
            }

            if (! empty($filters['filterDateFrom'])) {
                $query->whereDate('created_at', '>=', $filters['filterDateFrom']);
            }

            if (! empty($filters['filterDateTo'])) {
                $query->whereDate('created_at', '<=', $filters['filterDateTo']);
            }

            if (! empty($filters['filterFeeMin'])) {
                $query->where('fee', '>=', $filters['filterFeeMin']);
            }

            if (! empty($filters['filterFeeMax'])) {
                $query->where('fee', '<=', $filters['filterFeeMax']);
            }

            if (! empty($filters['filterStudentCategory'])) {
                $category = $filters['filterStudentCategory'];
                if ($category === 'student') {
                    $query->whereIn('registration_category_snapshot', ['undergrad_student', 'grad_student']);
                } else {
                    $query->where('registration_category_snapshot', $category);
                }
            }

            if (! empty($filters['filterCountry'])) {
                $country = $filters['filterCountry'];
                if ($country === 'Brazil') {
                    $query->where('address_country', 'Brazil');
                } elseif ($country === 'OTHER') {
                    $query->where('address_country', '!=', 'Brazil');
                }
            }

            if (! empty($filters['filterTransport'])) {
                $transport = $filters['filterTransport'];
                switch ($transport) {
                    case 'gru':
                        $query->where('needs_transport_from_gru', true);
                        break;
                    case 'usp':
                        $query->where('needs_transport_from_usp', true);
                        break;
                    case 'both':
                        $query->where('needs_transport_from_gru', true)
                            ->where('needs_transport_from_usp', true);
                        break;
                    case 'none':
                        $query->where('needs_transport_from_gru', false)
                            ->where('needs_transport_from_usp', false);
                        break;
                }
            }

            if (! empty($filters['filterMinFee'])) {
                $query->whereRaw('(SELECT SUM(CAST(price AS DECIMAL(10,2))) FROM fees WHERE fees.event_code IN (SELECT event_code FROM event_registration WHERE event_registration.registration_id = registrations.id)) >= ?', [$filters['filterMinFee']]);
            }

            if (! empty($filters['filterMaxFee'])) {
                $query->whereRaw('(SELECT SUM(CAST(price AS DECIMAL(10,2))) FROM fees WHERE fees.event_code IN (SELECT event_code FROM event_registration WHERE event_registration.registration_id = registrations.id)) <= ?', [$filters['filterMaxFee']]);
            }
        }

        // Order by creation date (same as RegistrationsList)
        $query->orderBy('created_at', 'desc');

        // Generate and return CSV
        return $exportService->exportToCsv($query, $validated['columns']);
    }
}
