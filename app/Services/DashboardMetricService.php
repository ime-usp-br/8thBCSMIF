<?php

namespace App\Services;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Metric Service - Data aggregation and caching for admin dashboard widgets
 *
 * Provides cached metrics for the admin dashboard including registration counts,
 * revenue calculations, pending approvals, and transport needs.
 */
class DashboardMetricService
{
    /**
     * Cache TTL in minutes - 5 minutes as per AC5 requirements
     */
    private const CACHE_TTL = 5;

    /**
     * Get total registration count with trend indicator
     *
     * @return array{total: int, trend: array{current_month: int, previous_month: int, percentage_change: float}}
     */
    public function getTotalRegistrations(): array
    {
        /** @phpstan-ignore-next-line */
        return Cache::remember('dashboard.total_registrations', self::CACHE_TTL * 60, function (): array {
            $currentMonth = now()->startOfMonth();
            $previousMonth = now()->subMonth()->startOfMonth();

            $total = Registration::count();

            $currentMonthCount = Registration::where('created_at', '>=', $currentMonth)->count();
            $previousMonthCount = Registration::whereBetween('created_at', [
                $previousMonth,
                $previousMonth->copy()->endOfMonth(),
            ])->count();

            $percentageChange = $previousMonthCount > 0
                ? (($currentMonthCount - $previousMonthCount) / $previousMonthCount) * 100
                : 0;

            return [
                'total' => $total,
                'trend' => [
                    'current_month' => $currentMonthCount,
                    'previous_month' => $previousMonthCount,
                    'percentage_change' => round($percentageChange, 1),
                ],
            ];
        });
    }

    /**
     * Get registration distribution by category
     *
     * @return array<string, int>
     */
    public function getRegistrationsByCategory(): array
    {
        /** @phpstan-ignore-next-line */
        return Cache::remember('dashboard.registrations_by_category', self::CACHE_TTL * 60, function (): array {
            return Registration::select('registration_category_snapshot', DB::raw('count(*) as count'))
                ->whereNotNull('registration_category_snapshot')
                ->groupBy('registration_category_snapshot')
                ->pluck('count', 'registration_category_snapshot')
                ->toArray();
        });
    }

    /**
     * Get pending approvals count
     *
     * @return array{payment_proofs: int, enrollment_proofs: int, total: int}
     */
    public function getPendingApprovals(): array
    {
        /** @phpstan-ignore-next-line */
        return Cache::remember('dashboard.pending_approvals', self::CACHE_TTL * 60, function (): array {
            $pendingPaymentProofs = Payment::where('status', Payment::STATUS_PENDING_APPROVAL)->count();
            $pendingEnrollmentProofs = EnrollmentProof::where('status', EnrollmentProof::STATUS_PENDING_APPROVAL)->count();

            return [
                'payment_proofs' => $pendingPaymentProofs,
                'enrollment_proofs' => $pendingEnrollmentProofs,
                'total' => $pendingPaymentProofs + $pendingEnrollmentProofs,
            ];
        });
    }

    /**
     * Get revenue metrics
     *
     * @return array{confirmed: float, pending: float, total: float}
     */
    public function getRevenue(): array
    {
        /** @phpstan-ignore-next-line */
        return Cache::remember('dashboard.revenue', self::CACHE_TTL * 60, function (): array {
            $confirmedRevenue = Payment::where('status', Payment::STATUS_APPROVED)
                ->sum('amount');

            $pendingRevenue = Payment::whereIn('status', [
                Payment::STATUS_PENDING,
                Payment::STATUS_PENDING_APPROVAL,
            ])->sum('amount');

            return [
                'confirmed' => (float) $confirmedRevenue,
                'pending' => (float) $pendingRevenue,
                'total' => (float) ($confirmedRevenue + $pendingRevenue),
            ];
        });
    }

    /**
     * Get transport needs statistics
     *
     * @return array{from_usp: int, from_gru: int, both: int, total: int}
     */
    public function getTransportNeeds(): array
    {
        /** @phpstan-ignore-next-line */
        return Cache::remember('dashboard.transport_needs', self::CACHE_TTL * 60, function (): array {
            $fromUSP = Registration::where('needs_transport_from_usp', true)->count();
            $fromGRU = Registration::where('needs_transport_from_gru', true)->count();
            $both = Registration::where('needs_transport_from_usp', true)
                ->where('needs_transport_from_gru', true)
                ->count();

            return [
                'from_usp' => $fromUSP,
                'from_gru' => $fromGRU,
                'both' => $both,
                'total' => $fromUSP + $fromGRU - $both, // Avoid double counting
            ];
        });
    }

    /**
     * Get all dashboard metrics at once
     *
     * @return array<string, mixed>
     */
    public function getAllMetrics(): array
    {
        return [
            'total_registrations' => $this->getTotalRegistrations(),
            'registrations_by_category' => $this->getRegistrationsByCategory(),
            'pending_approvals' => $this->getPendingApprovals(),
            'revenue' => $this->getRevenue(),
            'transport_needs' => $this->getTransportNeeds(),
        ];
    }

    /**
     * Clear all dashboard metrics cache
     */
    public function clearCache(): void
    {
        $cacheKeys = [
            'dashboard.total_registrations',
            'dashboard.registrations_by_category',
            'dashboard.pending_approvals',
            'dashboard.revenue',
            'dashboard.transport_needs',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}
