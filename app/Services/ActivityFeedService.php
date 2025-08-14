<?php

namespace App\Services;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Activity Feed Service
 *
 * Provides recent activity data for the admin dashboard, including:
 * - Latest registration submissions
 * - Recent payment proof uploads
 * - Recent enrollment proof submissions
 */
class ActivityFeedService
{
    /**
     * Get recent activity feed data with efficient queries.
     *
     * @param  int  $limit  Maximum number of activity items to return
     * @return \Illuminate\Support\Collection<int, array<string, mixed>> Collection of activity items
     */
    public function getRecentActivity(int $limit = 10): Collection
    {
        // Collect different activity types with minimal queries
        $activities = collect();

        // Recent registration submissions (based on created_at)
        $recentRegistrations = Registration::query()
            ->with('user:id,name,email')
            ->select('id', 'user_id', 'full_name', 'status', 'created_at')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($registration) {
                return [
                    'id' => 'registration_'.$registration->id,
                    'type' => 'registration_submission',
                    'title' => __('New Registration Submission'),
                    'description' => __('Registration by :name', ['name' => $registration->full_name]),
                    'timestamp' => $registration->created_at,
                    'user_name' => $registration->user->name ?? $registration->full_name,
                    'user_email' => $registration->user->email ?? __('N/A'),
                    'status' => $registration->status,
                    'link_url' => route('admin.registrations.show', $registration->id),
                    'link_text' => __('View Registration'),
                ];
            });

        // Recent payment proof uploads (where payment_proof_path is not null)
        $recentPayments = Payment::query()
            ->whereNotNull('payment_proof_path')
            ->with(['registration.user:id,name,email', 'registration:id,user_id,full_name'])
            ->select('id', 'registration_id', 'amount', 'status', 'payment_proof_path', 'updated_at')
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => 'payment_'.$payment->id,
                    'type' => 'payment_proof_upload',
                    'title' => __('Payment Proof Uploaded'),
                    'description' => __('Payment proof for :name (R$ :amount)', [
                        'name' => $payment->registration->full_name,
                        'amount' => number_format((float) $payment->amount, 2, ',', '.'),
                    ]),
                    'timestamp' => $payment->updated_at,
                    'user_name' => $payment->registration->user->name ?? $payment->registration->full_name,
                    'user_email' => $payment->registration->user->email ?? __('N/A'),
                    'status' => $payment->status,
                    'link_url' => route('admin.registrations.show', $payment->registration_id),
                    'link_text' => __('View Registration'),
                ];
            });

        // Recent enrollment proof submissions (for students)
        $recentEnrollmentProofs = EnrollmentProof::query()
            ->whereNotNull('uploaded_at')
            ->with(['registration.user:id,name,email', 'registration:id,user_id,full_name'])
            ->select('id', 'registration_id', 'status', 'uploaded_at', 'original_filename')
            ->latest('uploaded_at')
            ->limit($limit)
            ->get()
            ->map(function ($proof) {
                return [
                    'id' => 'enrollment_'.$proof->id,
                    'type' => 'enrollment_proof_submission',
                    'title' => __('Enrollment Proof Submitted'),
                    'description' => __('Enrollment proof by :name', [
                        'name' => $proof->registration->full_name,
                    ]),
                    'timestamp' => $proof->uploaded_at,
                    'user_name' => $proof->registration->user->name ?? $proof->registration->full_name,
                    'user_email' => $proof->registration->user->email ?? __('N/A'),
                    'status' => $proof->status,
                    'link_url' => route('admin.registrations.show', $proof->registration_id),
                    'link_text' => __('View Registration'),
                ];
            });

        // Combine all activities
        $activities = $activities
            ->concat($recentRegistrations)
            ->concat($recentPayments)
            ->concat($recentEnrollmentProofs);

        // Sort by timestamp (most recent first) and limit
        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $result */
        $result = $activities
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values(); // Re-index the collection

        return $result;
    }

    /**
     * Get activity count by type for the last 24 hours.
     *
     * @return array<string, int> Activity counts by type
     */
    public function getActivityCounts(): array
    {
        $yesterday = Carbon::now()->subDay();

        return [
            'registrations' => Registration::where('created_at', '>=', $yesterday)->count(),
            'payments' => Payment::whereNotNull('payment_proof_path')
                ->where('updated_at', '>=', $yesterday)
                ->count(),
            'enrollment_proofs' => EnrollmentProof::whereNotNull('uploaded_at')
                ->where('uploaded_at', '>=', $yesterday)
                ->count(),
        ];
    }

    /**
     * Get the activity type icon class for display.
     *
     * @param  string  $type  Activity type
     * @return string CSS class for the icon
     */
    public function getActivityIcon(string $type): string
    {
        return match ($type) {
            'registration_submission' => 'fas fa-user-plus text-blue-500',
            'payment_proof_upload' => 'fas fa-credit-card text-green-500',
            'enrollment_proof_submission' => 'fas fa-graduation-cap text-purple-500',
            default => 'fas fa-bell text-gray-500',
        };
    }

    /**
     * Get the status badge class for display.
     *
     * @param  string  $status  Status value
     * @return string CSS class for the status badge
     */
    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'pending_approval' => 'bg-orange-100 text-orange-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get the localized status text.
     *
     * @param  string  $status  Status value
     * @return string Localized status text
     */
    public function getStatusText(string $status): string
    {
        return match ($status) {
            'pending' => __('Pending'),
            'pending_approval' => __('Pending Approval'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            default => ucfirst($status),
        };
    }
}
