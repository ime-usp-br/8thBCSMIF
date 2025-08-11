<?php

namespace App\Livewire\Admin\Widgets;

use App\Services\DashboardMetricService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Registrations by Category Widget Component (Non-Critical - Progressive Loading)
 *
 * Displays a breakdown of registrations by category (undergrad, grad, professor, etc.)
 * with a visual pie chart representation.
 * Optimized for progressive loading with 1.5s delay.
 */
class RegistrationsByCategory extends Component
{
    /**
     * Category distribution data
     *
     * @var array<int, array{category: string, label: string, count: int, color: string}>
     */
    public array $data = [];

    /**
     * Loading state for progressive loading
     */
    public bool $isLoading = true;

    /**
     * Category display names mapping
     *
     * @var array<string, string>
     */
    public array $categoryLabels = [
        'undergrad_student' => 'Undergraduate Students',
        'grad_student' => 'Graduate Students',
        'professor' => 'Professors',
        'professional' => 'Professionals',
        'other' => 'Others',
    ];

    /**
     * Colors for pie chart segments
     *
     * @var array<string, string>
     */
    public array $colors = [
        'undergrad_student' => '#3B82F6', // blue
        'grad_student' => '#10B981',      // green
        'professor' => '#F59E0B',         // yellow
        'professional' => '#EF4444',     // red
        'other' => '#8B5CF6',             // purple
    ];

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
     * Load the registrations by category data
     */
    private function loadData(DashboardMetricService $metricsService): void
    {
        $rawData = $metricsService->getRegistrationsByCategory();

        /** @var array<int, array{category: string, label: string, count: int, color: string}> $processedData */
        $processedData = collect($rawData)->map(function ($count, $category) {
            return [
                'category' => (string) $category,
                'label' => $this->categoryLabels[$category] ?? ucfirst(str_replace('_', ' ', $category)),
                'count' => (int) $count,
                'color' => $this->colors[$category] ?? '#6B7280', // gray fallback
            ];
        })->values()->toArray();

        $this->data = $processedData;
    }

    /**
     * Get total count for percentage calculations
     */
    public function getTotalCount(): int
    {
        return collect($this->data)->sum('count');
    }

    /**
     * Get percentage for a given count
     */
    public function getPercentage(int $count): float
    {
        $total = $this->getTotalCount();

        return $total > 0 ? round(($count / $total) * 100, 1) : 0;
    }

    /**
     * Render the widget
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.widgets.registrations-by-category');
    }
}
