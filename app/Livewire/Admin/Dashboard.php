<?php

namespace App\Livewire\Admin;

use App\Services\DashboardMetricService;
use Livewire\Component;

/**
 * Admin Dashboard Component
 *
 * Main dashboard component that serves as a container for all dashboard widgets
 * and provides real-time metrics updates through nested widgets.
 */
class Dashboard extends Component
{
    /**
     * The dashboard metrics service instance
     */
    protected DashboardMetricService $metricsService;

    /**
     * Boot the component and inject the metrics service
     */
    public function boot(DashboardMetricService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Refresh all dashboard metrics
     */
    public function refreshMetrics(): void
    {
        $this->metricsService->clearCache();

        // Dispatch refresh event to all nested widgets
        $this->dispatch('dashboard-metrics-refreshed');

        session()->flash('success', __('Dashboard metrics refreshed successfully.'));
    }

    /**
     * Render the dashboard component
     */
    public function render(): \Illuminate\View\View
    {
        /** @var \Illuminate\View\View $view */
        $view = view('livewire.admin.dashboard')
            ->title(__('Admin Dashboard - 8th BCSMIF'));

        return $view;
    }
}
