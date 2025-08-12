<?php

namespace App\Livewire\Admin;

use App\Services\ActivityFeedService;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Recent Activity Feed Livewire Component
 *
 * Displays the latest registration submissions, payment proof uploads,
 * and enrollment proof submissions with real-time updates via polling.
 */
class RecentActivityFeed extends Component
{
    /** @var \Illuminate\Support\Collection<int, array<string, mixed>> */
    public Collection $activities;

    /** @var array<string, int> */
    public array $activityCounts = [];

    public int $limit = 10;

    public bool $isLoading = false;

    /**
     * Initialize component with recent activity data.
     */
    public function mount(): void
    {
        $this->loadActivities();
    }

    /**
     * Refresh the activity feed data.
     * This method is called by wire:poll automatically.
     */
    public function refreshActivities(): void
    {
        $this->isLoading = true;

        try {
            $this->loadActivities();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Load activities using the ActivityFeedService.
     */
    protected function loadActivities(): void
    {
        $service = app(ActivityFeedService::class);

        $this->activities = $service->getRecentActivity($this->limit);
        $this->activityCounts = $service->getActivityCounts();
    }

    /**
     * Get the activity icon class for a given activity type.
     *
     * @param  string  $type  Activity type
     * @return string CSS class for the icon
     */
    public function getActivityIcon(string $type): string
    {
        return app(ActivityFeedService::class)->getActivityIcon($type);
    }

    /**
     * Get the status badge class for a given status.
     *
     * @param  string  $status  Status value
     * @return string CSS class for the status badge
     */
    public function getStatusBadgeClass(string $status): string
    {
        return app(ActivityFeedService::class)->getStatusBadgeClass($status);
    }

    /**
     * Get the localized status text for a given status.
     *
     * @param  string  $status  Status value
     * @return string Localized status text
     */
    public function getStatusText(string $status): string
    {
        return app(ActivityFeedService::class)->getStatusText($status);
    }

    /**
     * Format a timestamp for display.
     *
     * @param  \Carbon\Carbon|string  $timestamp
     * @return string Formatted timestamp
     */
    public function formatTimestamp($timestamp): string
    {
        if (is_string($timestamp)) {
            $timestamp = \Carbon\Carbon::parse($timestamp);
        }

        // Ensure Carbon uses the application's locale
        $locale = config('app.locale') ?? 'en';
        if (!is_string($locale)) {
            $locale = 'en';
        }
        $timestamp->locale($locale);

        return $timestamp->diffForHumans();
    }

    /**
     * Set the activity limit and reload data.
     *
     * @param  int  $newLimit  New limit value
     */
    public function setLimit(int $newLimit): void
    {
        $this->limit = max(5, min(20, $newLimit)); // Constrain between 5-20
        $this->loadActivities();
    }

    /**
     * Check if there are any activities to display.
     *
     * @return bool True if activities exist, false otherwise
     */
    public function hasActivities(): bool
    {
        return $this->activities->isNotEmpty();
    }

    /**
     * Get a count of activities by type for display.
     *
     * @param  string  $type  Activity type
     * @return int Count of activities
     */
    public function getActivityCountByType(string $type): int
    {
        return (int) ($this->activityCounts[$type] ?? 0);
    }

    /**
     * Render the component view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.admin.recent-activity-feed');
    }
}
