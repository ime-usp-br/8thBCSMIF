<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetricService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected DashboardMetricService $metricsService
    ) {}

    /**
     * Show the admin dashboard with comprehensive metrics overview.
     * Uses real-time data from DashboardMetricService with caching optimizations.
     *
     * All metrics are loaded immediately to ensure accurate data display,
     * fixing the previous issue where transport needs showed incorrect values.
     */
    public function index(): View
    {
        // Get critical metrics for immediate display
        $criticalMetrics = $this->metricsService->getCriticalMetrics();

        // Transform data structure for backward compatibility with views
        /** @var array<string, mixed> $totalRegistrations */
        $totalRegistrations = is_array($criticalMetrics['total_registrations'] ?? null) ? $criticalMetrics['total_registrations'] : [];
        /** @var array<string, mixed> $pendingApprovals */
        $pendingApprovals = is_array($criticalMetrics['pending_approvals'] ?? null) ? $criticalMetrics['pending_approvals'] : [];
        /** @var array<string, mixed> $revenue */
        $revenue = is_array($criticalMetrics['revenue'] ?? null) ? $criticalMetrics['revenue'] : [];

        // Extract trend data safely
        /** @var array<string, mixed> $trend */
        $trend = is_array($totalRegistrations['trend'] ?? null) ? $totalRegistrations['trend'] : [];

        // Safe casting with type checking
        $totalCount = $totalRegistrations['total'] ?? 0;
        $percentageChange = $trend['percentage_change'] ?? 0;
        $currentMonth = $trend['current_month'] ?? 0;
        $previousMonth = $trend['previous_month'] ?? 0;

        $paymentProofs = $pendingApprovals['payment_proofs'] ?? 0;
        $enrollmentProofs = $pendingApprovals['enrollment_proofs'] ?? 0;
        $totalApprovals = $pendingApprovals['total'] ?? 0;

        $confirmedRevenue = $revenue['confirmed'] ?? 0;
        $pendingRevenue = $revenue['pending'] ?? 0;
        $totalRevenue = $revenue['total'] ?? 0;

        // Get transport needs data from service
        $transportNeeds = $this->metricsService->getTransportNeeds();
        $registrationsByCategory = $this->metricsService->getRegistrationsByCategory();

        $metrics = [
            'total_registrations' => [
                'count' => is_numeric($totalCount) ? (int) $totalCount : 0,
                'trend' => (is_numeric($percentageChange) && (float) $percentageChange >= 0) ? 'up' : 'down',
                'change_percent' => is_numeric($percentageChange) ? abs((float) $percentageChange) : 0.0,
                'current_month' => is_numeric($currentMonth) ? (int) $currentMonth : 0,
                'previous_month' => is_numeric($previousMonth) ? (int) $previousMonth : 0,
            ],
            'pending_approvals' => [
                'payment_proofs' => is_numeric($paymentProofs) ? (int) $paymentProofs : 0,
                'enrollment_proofs' => is_numeric($enrollmentProofs) ? (int) $enrollmentProofs : 0,
                'total' => is_numeric($totalApprovals) ? (int) $totalApprovals : 0,
            ],
            'revenue' => [
                'confirmed' => is_numeric($confirmedRevenue) ? (float) $confirmedRevenue : 0.0,
                'pending' => is_numeric($pendingRevenue) ? (float) $pendingRevenue : 0.0,
                'total' => is_numeric($totalRevenue) ? (float) $totalRevenue : 0.0,
                'currency' => config('currency.code'),
            ],
            'registrations_by_category' => $registrationsByCategory,
            'transport_needs' => [
                'from_usp' => $transportNeeds['from_usp'],
                'from_gru' => $transportNeeds['from_gru'],
                'both' => $transportNeeds['both'],
                'total' => $transportNeeds['total'],
            ],
        ];

        // Pre-warm cache for progressive loading
        $this->metricsService->warmCache();

        return view('admin.dashboard', compact('metrics'));
    }

    /**
     * Get non-critical metrics for progressive loading via AJAX
     *
     * @return array<string, mixed>
     */
    public function getNonCriticalMetrics(): array
    {
        return $this->metricsService->getNonCriticalMetrics();
    }

    /**
     * Refresh all dashboard metrics and clear cache
     *
     * @return array<string, mixed>
     */
    public function refreshMetrics(): array
    {
        $this->metricsService->clearCache();

        return $this->metricsService->getAllMetrics();
    }
}
