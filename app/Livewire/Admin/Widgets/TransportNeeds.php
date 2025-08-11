<?php

namespace App\Livewire\Admin\Widgets;

use App\Services\DashboardMetricService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Transport Needs Widget Component (Non-Critical - Progressive Loading)
 *
 * Displays transport needs statistics for participants requiring
 * transportation from USP or GRU airport with integration to existing
 * transport reports. Optimized for progressive loading with 1s delay.
 */
class TransportNeeds extends Component
{
    /**
     * Transport needs data
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * Loading state for progressive loading
     */
    public bool $isLoading = true;

    /**
     * Mount the component - start with loading state (progressive loading)
     */
    public function mount(): void
    {
        // Non-critical metric - will be loaded progressively via JavaScript
        $this->isLoading = true;
    }

    /**
     * Load data progressively (called by frontend after delay)
     */
    public function loadProgressively(DashboardMetricService $metricsService): void
    {
        $this->loadData($metricsService);
        $this->isLoading = false;
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
     * Load the transport needs data
     */
    private function loadData(DashboardMetricService $metricsService): void
    {
        $this->data = $metricsService->getTransportNeeds();
    }

    /**
     * Navigate to transport reports
     */
    public function goToTransportReports(): void
    {
        $this->redirect(route('admin.reports.index'));
    }

    /**
     * Navigate to USP transport list
     */
    public function goToUSPTransportList(): void
    {
        $this->redirect(route('admin.registrations.index', ['filterTransport' => 'usp']));
    }

    /**
     * Navigate to GRU transport list
     */
    public function goToGRUTransportList(): void
    {
        $this->redirect(route('admin.registrations.index', ['filterTransport' => 'gru']));
    }

    /**
     * Render the widget
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.widgets.transport-needs');
    }
}
