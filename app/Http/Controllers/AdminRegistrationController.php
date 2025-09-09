<?php

namespace App\Http\Controllers;

use App\Mail\PaymentApprovedNotification;
use App\Mail\PaymentRejectedNotification;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * AdminRegistrationController
 *
 * Handles administrative actions for registration approvals, including fee exemptions.
 * AC3: Implements fee exemption flow with administrative logging.
 */
class AdminRegistrationController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:admin'),
        ];
    }

    /**
     * AC1: Show the approval queue page
     */
    public function approvals(): View
    {
        return view('admin.approvals');
    }

    /**
     * AC3: Approve a registration (with or without payment proof - fee exemption)
     *
     * This method handles both regular payment approvals and fee exemptions.
     * For exemptions, it approves registrations with 'pending' status that don't have payment proof.
     */
    public function approve(Request $request, Registration $registration): JsonResponse
    {
        try {
            // Verify that the registration is in a state that can be approved
            if (! in_array($registration->status, [Registration::STATUS_PENDING, Registration::STATUS_PENDING_APPROVAL])) {
                return response()->json([
                    'success' => false,
                    'message' => __('Registration cannot be approved from current status: :status', [
                        'status' => $registration->status,
                    ]),
                ], 422);
            }

            // Get current admin user
            $admin = Auth::user();
            $adminName = $admin->name ?? $admin->email ?? 'Unknown Admin';

            // AC3: Fee exemption only applies to STATUS_PENDING registrations without payments
            // STATUS_PENDING_APPROVAL registrations are always regular approvals regardless of payments
            $isExemption = ($registration->status === Registration::STATUS_PENDING) &&
                          ($registration->payments()->count() === 0);

            if ($isExemption) {
                // AC3: Fee exemption - approve registration without payment proof
                $exemptionNote = __('Fee exemption granted by :admin_name on :date', [
                    'admin_name' => $adminName,
                    'date' => now()->format('Y-m-d H:i:s'),
                ]);

                // Add exemption note to existing notes
                $currentNotes = $registration->notes ? $registration->notes."\n\n" : '';
                $updatedNotes = $currentNotes.$exemptionNote;

                $registration->update([
                    'status' => Registration::STATUS_APPROVED,
                    'notes' => $updatedNotes,
                ]);

                // Send approval notification for exemption
                $userEmail = $registration->user->email ?? $registration->email;
                Mail::to($userEmail)->queue(
                    new PaymentApprovedNotification($registration, 'exemption')
                );

                return response()->json([
                    'success' => true,
                    'message' => __('Fee exemption approved successfully for :name', [
                        'name' => $registration->full_name,
                    ]),
                    'type' => 'exemption',
                ]);
            } else {
                // Regular approval for STATUS_PENDING_APPROVAL or STATUS_PENDING with payments
                $approvalNote = __('Registration approved by :admin_name on :date', [
                    'admin_name' => $adminName,
                    'date' => now()->format('Y-m-d H:i:s'),
                ]);

                // Add approval note to existing notes
                $currentNotes = $registration->notes ? $registration->notes."\n\n" : '';
                $updatedNotes = $currentNotes.$approvalNote;

                $registration->update([
                    'status' => Registration::STATUS_APPROVED,
                    'notes' => $updatedNotes,
                ]);

                // Send regular approval notification
                $userEmail = $registration->user->email ?? $registration->email;
                Mail::to($userEmail)->queue(
                    new PaymentApprovedNotification($registration, 'approval')
                );

                return response()->json([
                    'success' => true,
                    'message' => __('Registration approved successfully for :name', [
                        'name' => $registration->full_name,
                    ]),
                    'type' => 'approval',
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error approving registration: :message', [
                    'message' => $e->getMessage(),
                ]),
            ], 500);
        }
    }

    /**
     * AC2: Reject a registration with reason
     */
    public function reject(Request $request, Registration $registration): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            // Get current admin user
            $admin = Auth::user();
            $adminName = $admin->name ?? $admin->email ?? 'Unknown Admin';

            // Create rejection note with reason
            $rejectionNote = __('Registration rejected by :admin_name on :date. Reason: :reason', [
                'admin_name' => $adminName,
                'date' => now()->format('Y-m-d H:i:s'),
                'reason' => $request->string('reason')->toString(),
            ]);

            // Add rejection note to existing notes
            $currentNotes = $registration->notes ? $registration->notes."\n\n" : '';
            $updatedNotes = $currentNotes.$rejectionNote;

            $registration->update([
                'status' => Registration::STATUS_REJECTED,
                'notes' => $updatedNotes,
            ]);

            // AC4: Send PaymentRejectedNotification with rejection reason
            $userEmail = $registration->user->email ?? $registration->email;
            Mail::to($userEmail)->queue(
                new PaymentRejectedNotification($registration, $request->string('reason')->toString())
            );

            return response()->json([
                'success' => true,
                'message' => __('Registration rejected successfully for :name', [
                    'name' => $registration->full_name,
                ]),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error rejecting registration: :message', [
                    'message' => $e->getMessage(),
                ]),
            ], 500);
        }
    }
}
