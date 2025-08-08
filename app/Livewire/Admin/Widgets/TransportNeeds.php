<?php

namespace App\Livewire\Admin\Widgets;

use App\Services\DashboardMetricService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Transport Needs Widget Component
 *
 * Displays transport needs statistics for participants requiring
 * transportation from USP or GRU airport with integration to existing
 * transport reports.
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
     * Mount the component and load data
     */
    public function mount(DashboardMetricService $metricsService): void
    {
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
