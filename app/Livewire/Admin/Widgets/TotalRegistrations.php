<?php

namespace App\Livewire\Admin\Widgets;

use App\Services\DashboardMetricService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Total Registrations Widget Component (Critical - Immediate Loading)
 *
 * Displays the total number of registrations with trend indicator
 * showing month-over-month growth percentage.
 * Optimized for immediate loading as part of critical metrics.
 */
class TotalRegistrations extends Component
{
    /**
     * Dashboard metrics data
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * Loading state
     */
    public bool $isLoading = false;

    /**
     * Mount the component and load data immediately (critical metric)
     */
    public function mount(DashboardMetricService $metricsService): void
    {
        // Critical metric - load immediately without delay
        $this->loadData($metricsService);
    }

    /**
     * Listen for dashboard refresh events
     */
    #[On('dashboard-metrics-refreshed')]
    public function refreshData(DashboardMetricService $metricsService): void
    {
        $this->loadData($metricsService);
    }

    /**
     * Load the total registrations data
     */
    private function loadData(DashboardMetricService $metricsService): void
    {
        $this->data = $metricsService->getTotalRegistrations();
    }

    /**
     * Render the widget
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.widgets.total-registrations');
    }
}
