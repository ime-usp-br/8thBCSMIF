<?php

namespace App\Livewire\Admin\Widgets;

use App\Services\DashboardMetricService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Pending Approvals Widget Component
 *
 * Displays the count of pending approvals for both payment proofs and enrollment proofs
 * with quick navigation links to respective approval queues.
 */
class PendingApprovals extends Component
{
    /**
     * Pending approvals data
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
     * Load the pending approvals data
     */
    private function loadData(DashboardMetricService $metricsService): void
    {
        $this->data = $metricsService->getPendingApprovals();
    }

    /**
     * Navigate to payment approvals queue
     */
    public function goToPaymentApprovals(): void
    {
        $this->redirect(route('admin.registrations.index', ['filterPaymentStatus' => 'pending_approval']));
    }

    /**
     * Navigate to enrollment approvals queue
     */
    public function goToEnrollmentApprovals(): void
    {
        $this->redirect(route('admin.registrations.index', ['filterEnrollmentProofStatus' => 'pending_approval']));
    }

    /**
     * Render the widget
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.widgets.pending-approvals');
    }
}
