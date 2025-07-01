<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentStatusUpdatedNotification;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
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
        $registration->load(['user', 'events']);

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
                    'pending_payment',
                    'pending_br_proof_approval',
                    'paid_br',
                    'invoice_sent_int',
                    'paid_int',
                    'free',
                    'cancelled',
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
}
