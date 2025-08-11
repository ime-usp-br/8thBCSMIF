<div class="space-y-4 sm:space-y-6" role="main" aria-labelledby="dashboard-main-heading">
    {{-- Dashboard Header --}}
    <header class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100" id="dashboard-main-heading">
            {{ __('Admin Dashboard') }}
        </h1>
        <button wire:click="refreshMetrics" 
                class="bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 text-white px-4 py-2 rounded-lg flex items-center justify-center space-x-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 min-h-[44px] min-w-[44px]"
                aria-label="{{ __('Refresh dashboard metrics') }}"
                type="button">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" 
                 aria-hidden="true" role="img">
                <title>{{ __('Refresh icon') }}</title>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span class="hidden sm:inline">{{ __('Refresh Metrics') }}</span>
            <span class="sm:hidden">{{ __('Refresh') }}</span>
        </button>
    </header>

    {{-- Success Message --}}
    @if (session()->has('success'))
        <div class="bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded relative" 
             role="alert" 
             aria-live="polite"
             aria-atomic="true">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Statistics Widgets Grid --}}
    <section aria-labelledby="widgets-section-heading">
        <h2 id="widgets-section-heading" class="sr-only">{{ __('Dashboard Statistics Widgets') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 sm:gap-6">
        
        {{-- Total Registrations Widget --}}
        <div class="lg:col-span-1">
            <livewire:admin.widgets.total-registrations />
        </div>

        {{-- Pending Approvals Widget --}}
        <div class="lg:col-span-1">
            <livewire:admin.widgets.pending-approvals />
        </div>

        {{-- Revenue Widget --}}
        <div class="lg:col-span-1">
            <livewire:admin.widgets.revenue />
        </div>

        {{-- Transport Needs Widget --}}
        <div class="lg:col-span-1">
            <livewire:admin.widgets.transport-needs />
        </div>

        {{-- Registrations by Category Widget (takes more space for chart) --}}
        <div class="lg:col-span-1 xl:col-span-1">
            <livewire:admin.widgets.registrations-by-category />
        </div>

        </div>
    </section>

    {{-- Quick Actions Section --}}
    <section aria-labelledby="livewire-quick-actions-heading" class="mt-6 sm:mt-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100 mb-4" 
                    id="livewire-quick-actions-heading">
                    {{ __('Quick Actions') }}
                </h3>
                <nav aria-labelledby="livewire-quick-actions-heading" role="navigation">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                        <a href="{{ route('admin.registrations.index') }}" 
                           class="bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4 text-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 min-h-[80px] flex flex-col justify-center"
                           aria-describedby="livewire-registrations-desc"
                           tabindex="0">
                            <div class="text-blue-600 dark:text-blue-400 font-medium">{{ __('View Registrations') }}</div>
                            <div class="text-blue-500 dark:text-blue-500 text-sm mt-1" 
                                 id="livewire-registrations-desc">{{ __('Manage all registrations') }}</div>
                        </a>
                        <a href="{{ route('admin.reports.index') }}" 
                           class="bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg p-4 text-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 min-h-[80px] flex flex-col justify-center"
                           aria-describedby="livewire-reports-desc"
                           tabindex="0">
                            <div class="text-green-600 dark:text-green-400 font-medium">{{ __('Generate Reports') }}</div>
                            <div class="text-green-500 dark:text-green-500 text-sm mt-1" 
                                 id="livewire-reports-desc">{{ __('Export data and analytics') }}</div>
                        </a>
                        <button wire:click="refreshMetrics"
                                class="bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 rounded-lg p-4 text-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 min-h-[80px] flex flex-col justify-center"
                                aria-describedby="livewire-refresh-desc"
                                type="button">
                            <div class="text-gray-600 dark:text-gray-300 font-medium">{{ __('Refresh Data') }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm mt-1" 
                                 id="livewire-refresh-desc">{{ __('Update all metrics') }}</div>
                        </button>
                    </div>
                </nav>
            </div>
        </div>
    </section>
</div>