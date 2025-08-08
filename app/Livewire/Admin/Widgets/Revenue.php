<?php

namespace App\Livewire\Admin\Widgets;

use App\Services\DashboardMetricService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Revenue Widget Component
 *
 * Displays revenue metrics including confirmed revenue (approved payments)
 * and pending revenue (pending approvals), formatted in BRL currency.
 */
class Revenue extends Component
{
    /**
     * Revenue data
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
     * Load the revenue data
     */
    private function loadData(DashboardMetricService $metricsService): void
    {
        $this->data = $metricsService->getRevenue();
    }

    /**
     * Format currency value to BRL
     */
    public function formatCurrency(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    /**
     * Get the percentage of confirmed revenue vs total
     */
    public function getConfirmedPercentage(): float
    {
        $total = is_numeric($this->data['total'] ?? 0) ? (float) ($this->data['total'] ?? 0) : 0.0;
        if ($total <= 0) {
            return 0.0;
        }

        $confirmed = is_numeric($this->data['confirmed'] ?? 0) ? (float) ($this->data['confirmed'] ?? 0) : 0.0;

        return round(($confirmed / $total) * 100, 1);
    }

    /**
     * Render the widget
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.widgets.revenue');
    }
}
