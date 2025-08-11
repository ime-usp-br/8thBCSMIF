<div wire:poll.30s="refreshActivities">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('Recent Activity') }}
        </h3>
        <div class="flex items-center space-x-2">
            <!-- Loading indicator -->
            <div wire:loading wire:target="refreshActivities" class="text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-sync-alt animate-spin mr-1"></i>
                {{ __('Updating...') }}
            </div>
            
            <!-- Manual refresh button -->
            <button wire:click="refreshActivities"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    title="{{ __('Refresh activity feed') }}">
                <i class="fas fa-sync-alt text-sm"></i>
            </button>
            
            <!-- Activity count indicator -->
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ __(':count items', ['count' => $activities->count()]) }}
            </div>
        </div>
    </div>

    @if($this->hasActivities())
        <div class="space-y-3">
            @foreach($activities as $activity)
                <div wire:key="{{ $activity['id'] }}" 
                     class="flex items-start space-x-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200 group">
                    
                    <!-- Activity icon -->
                    <div class="flex-shrink-0 pt-0.5">
                        <i class="{{ $this->getActivityIcon($activity['type']) }} text-lg"></i>
                    </div>
                    
                    <!-- Activity content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $activity['title'] }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $activity['description'] }}
                                </p>
                                
                                <!-- User info -->
                                <div class="flex items-center mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-user mr-1"></i>
                                    <span>{{ $activity['user_name'] }}</span>
                                    <span class="mx-1">•</span>
                                    <span>{{ $activity['user_email'] }}</span>
                                </div>
                            </div>
                            
                            <!-- Timestamp and status -->
                            <div class="flex flex-col items-end space-y-1 ml-4">
                                <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $this->formatTimestamp($activity['timestamp']) }}
                                </span>
                                
                                <!-- Status badge -->
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $this->getStatusBadgeClass($activity['status']) }}">
                                    {{ $this->getStatusText($activity['status']) }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Action link -->
                        <div class="mt-2">
                            <a href="{{ $activity['link_url'] }}" 
                               class="inline-flex items-center text-xs text-usp-blue-pri hover:text-usp-blue-pri/80 font-medium group-hover:underline">
                                {{ $activity['link_text'] }}
                                <i class="fas fa-arrow-right ml-1 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Activity summary -->
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center space-x-4">
                    <span>
                        <i class="fas fa-user-plus text-blue-500 mr-1"></i>
                        {{ __(':count new registrations', ['count' => $this->getActivityCountByType('registrations')]) }}
                    </span>
                    <span>
                        <i class="fas fa-credit-card text-green-500 mr-1"></i>
                        {{ __(':count payment uploads', ['count' => $this->getActivityCountByType('payments')]) }}
                    </span>
                    <span>
                        <i class="fas fa-graduation-cap text-purple-500 mr-1"></i>
                        {{ __(':count enrollment proofs', ['count' => $this->getActivityCountByType('enrollment_proofs')]) }}
                    </span>
                </div>
                
                <!-- View all link -->
                <a href="{{ route('admin.registrations.index') }}" 
                   class="text-usp-blue-pri hover:text-usp-blue-pri/80 font-medium">
                    {{ __('View all activities') }} →
                </a>
            </div>
        </div>
        
        <!-- Settings -->
        <div class="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center space-x-2">
                <i class="fas fa-clock"></i>
                <span>{{ __('Updates every 30 seconds') }}</span>
            </div>
            
            <div class="flex items-center space-x-2">
                <span>{{ __('Show') }}:</span>
                <select wire:model.live="limit" 
                        class="text-xs border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                </select>
                <span>{{ __('items') }}</span>
            </div>
        </div>
    @else
        <!-- Empty state -->
        <div class="text-center py-8">
            <i class="fas fa-bell-slash text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                {{ __('No recent activity') }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Activity will appear here as registrations, payments, and enrollment proofs are submitted.') }}
            </p>
            <div class="mt-4">
                <button wire:click="refreshActivities"
                        class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    {{ __('Refresh') }}
                </button>
            </div>
        </div>
    @endif
    
    <!-- Debugging info (only in local environment) -->
    @if(config('app.debug') && app()->environment('local'))
        <div class="mt-4 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
            <strong>Debug Info:</strong> 
            Loaded {{ $activities->count() }} activities. 
            Polling every 30s. 
            Last update: {{ now()->format('H:i:s') }}
        </div>
    @endif
</div>