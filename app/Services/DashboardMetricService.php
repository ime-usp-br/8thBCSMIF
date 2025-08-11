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
     * Get revenue metrics - optimized with single query
     *
     * @return array{confirmed: float, pending: float, total: float}
     */
    public function getRevenue(): array
    {
        /** @phpstan-ignore-next-line */
        return Cache::remember('dashboard.revenue', self::CACHE_TTL * 60, function (): array {
            // Optimized: Single query with conditional aggregation
            $result = Payment::selectRaw('
                SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as confirmed,
                SUM(CASE WHEN status IN (?, ?) THEN amount ELSE 0 END) as pending,
                SUM(amount) as total
            ', [
                Payment::STATUS_APPROVED,
                Payment::STATUS_PENDING,
                Payment::STATUS_PENDING_APPROVAL,
            ])->first();

            $confirmedValue = $result->confirmed ?? 0;
            $pendingValue = $result->pending ?? 0;

            $confirmed = is_numeric($confirmedValue) ? (float) $confirmedValue : 0.0;
            $pending = is_numeric($pendingValue) ? (float) $pendingValue : 0.0;

            return [
                'confirmed' => $confirmed,
                'pending' => $pending,
                'total' => $confirmed + $pending,
            ];
        });
    }

    /**
     * Get transport needs statistics - optimized with single query
     *
     * @return array{from_usp: int, from_gru: int, both: int, total: int}
     */
    public function getTransportNeeds(): array
    {
        /** @phpstan-ignore-next-line */
        return Cache::remember('dashboard.transport_needs', self::CACHE_TTL * 60, function (): array {
            // Optimized: Single query with conditional aggregation
            $result = Registration::selectRaw('
                COUNT(CASE WHEN needs_transport_from_usp = 1 THEN 1 END) as from_usp,
                COUNT(CASE WHEN needs_transport_from_gru = 1 THEN 1 END) as from_gru,
                COUNT(CASE WHEN needs_transport_from_usp = 1 AND needs_transport_from_gru = 1 THEN 1 END) as both_transports
            ')->first();

            $fromUSPValue = $result->from_usp ?? 0;
            $fromGRUValue = $result->from_gru ?? 0;
            $bothValue = $result->both_transports ?? 0;

            $fromUSP = is_numeric($fromUSPValue) ? (int) $fromUSPValue : 0;
            $fromGRU = is_numeric($fromGRUValue) ? (int) $fromGRUValue : 0;
            $both = is_numeric($bothValue) ? (int) $bothValue : 0;

            return [
                'from_usp' => $fromUSP,
                'from_gru' => $fromGRU,
                'both' => $both,
                'total' => $fromUSP + $fromGRU - $both, // Avoid double counting
            ];
        });
    }

    /**
     * Get all dashboard metrics at once - with request-level memoization
     *
     * @return array<string, mixed>
     */
    public function getAllMetrics(): array
    {
        // Request-level memoization to avoid repeated calls within same request
        return once(function (): array {
            return [
                'total_registrations' => $this->getTotalRegistrations(),
                'registrations_by_category' => $this->getRegistrationsByCategory(),
                'pending_approvals' => $this->getPendingApprovals(),
                'revenue' => $this->getRevenue(),
                'transport_needs' => $this->getTransportNeeds(),
            ];
        });
    }

    /**
     * Get critical metrics only (for fast initial loading)
     *
     * @return array<string, mixed>
     */
    public function getCriticalMetrics(): array
    {
        return once(function (): array {
            return [
                'total_registrations' => $this->getTotalRegistrations(),
                'pending_approvals' => $this->getPendingApprovals(),
                'revenue' => $this->getRevenue(),
            ];
        });
    }

    /**
     * Get non-critical metrics (for progressive loading)
     *
     * @return array<string, mixed>
     */
    public function getNonCriticalMetrics(): array
    {
        return once(function (): array {
            return [
                'registrations_by_category' => $this->getRegistrationsByCategory(),
                'transport_needs' => $this->getTransportNeeds(),
            ];
        });
    }

    /**
     * Warm up dashboard cache - preload all metrics
     */
    public function warmCache(): void
    {
        // Pre-fetch all metrics to warm the cache
        $this->getTotalRegistrations();
        $this->getRegistrationsByCategory();
        $this->getPendingApprovals();
        $this->getRevenue();
        $this->getTransportNeeds();
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
