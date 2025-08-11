<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Admin Dashboard') }}
            </h2>
            {{-- Skip to main content link for accessibility --}}
            <a href="#main-dashboard-content" 
               class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-usp-blue-pri text-white px-4 py-2 rounded-md z-50 focus:z-50"
               aria-label="{{ __('Skip to main dashboard content') }}">
                {{ __('Skip to main content') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 lg:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Breadcrumb Navigation --}}
            <x-admin.breadcrumbs :breadcrumbs="[
                ['label' => __('Dashboard'), 'url' => '#']
            ]" />
            
            {{-- Main dashboard content --}}
            <main id="main-dashboard-content" role="main" aria-label="{{ __('Admin Dashboard Main Content') }}" class="space-y-6">
            
            {{-- Overview Statistics Cards --}}
            <section aria-labelledby="overview-heading">
                <h2 id="overview-heading" class="sr-only">{{ __('Dashboard Overview Statistics') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                
                {{-- Total Registrations Card --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg focus-within:ring-2 focus-within:ring-usp-blue-pri focus-within:ring-offset-2 transition-shadow duration-200">
                    <div class="p-4 sm:p-6">
                        <div class="border-l-4 border-usp-blue-pri pl-3 sm:pl-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex-1">
                                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100" id="total-registrations-heading">
                                        {{ __('Total Registrations') }}
                                    </h3>
                                    <p class="text-2xl sm:text-3xl font-bold text-usp-blue-pri mt-1 sm:mt-2" 
                                       aria-labelledby="total-registrations-heading">
                                        {{ number_format($metrics['total_registrations']['count']) }}
                                    </p>
                                    <div class="flex items-center mt-2" role="status" 
                                         aria-live="polite" 
                                         aria-label="{{ __('Trend information') }}">
                                        @if($metrics['total_registrations']['trend'] === 'up')
                                            <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" 
                                                 aria-hidden="true" role="img">
                                                <title>{{ __('Trending up') }}</title>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            </svg>
                                            <span class="text-green-600 text-sm font-medium">
                                                {{ __('Increase') }} +{{ $metrics['total_registrations']['change_percent'] }}%
                                            </span>
                                        @else
                                            <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" 
                                                 aria-hidden="true" role="img">
                                                <title>{{ __('Trending down') }}</title>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                            </svg>
                                            <span class="text-red-600 text-sm font-medium">
                                                {{ __('Decrease') }} -{{ $metrics['total_registrations']['change_percent'] }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-usp-blue-pri/20 mt-4 sm:mt-0 sm:ml-4 flex justify-end">
                                    <svg class="w-10 h-10 sm:w-12 sm:h-12" fill="currentColor" viewBox="0 0 24 24" 
                                         aria-hidden="true" role="img">
                                        <title>{{ __('Registration icon') }}</title>
                                        <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zM4 18v-4h3v4h2v-7.5c0-1.1.9-2 2-2s2 .9 2 2V18h2v-4h3v4h4V8H0v10h4z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pending Approvals Card --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg focus-within:ring-2 focus-within:ring-orange-500 focus-within:ring-offset-2 transition-shadow duration-200">
                    <div class="p-4 sm:p-6">
                        <div class="border-l-4 border-orange-500 pl-3 sm:pl-4">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3" id="pending-approvals-heading">
                                {{ __('Pending Approvals') }}
                            </h3>
                            <div class="space-y-2 text-sm" role="group" aria-labelledby="pending-approvals-heading">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Payment Proofs') }}:</span>
                                    <span class="font-medium text-orange-600 tabular-nums" 
                                          aria-label="{{ __('Payment proofs pending approval') }}: {{ $metrics['pending_approvals']['payment_proofs'] }}">
                                        {{ number_format($metrics['pending_approvals']['payment_proofs']) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Enrollment Proofs') }}:</span>
                                    <span class="font-medium text-orange-600 tabular-nums" 
                                          aria-label="{{ __('Enrollment proofs pending approval') }}: {{ $metrics['pending_approvals']['enrollment_proofs'] }}">
                                        {{ number_format($metrics['pending_approvals']['enrollment_proofs']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('admin.registrations.index') }}" 
                                   class="inline-flex items-center px-3 py-2 bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-400 focus:bg-orange-400 active:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150 min-h-[44px] min-w-[44px]" 
                                   aria-describedby="pending-approvals-heading"
                                   aria-label="{{ __('Review pending approvals queue') }}">
                                    {{ __('Review Queue') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Revenue Card --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg focus-within:ring-2 focus-within:ring-usp-yellow focus-within:ring-offset-2 transition-shadow duration-200">
                    <div class="p-4 sm:p-6">
                        <div class="border-l-4 border-usp-yellow pl-3 sm:pl-4">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3" id="revenue-heading">
                                {{ __('Revenue') }}
                            </h3>
                            <div class="space-y-2 text-sm" role="group" aria-labelledby="revenue-heading">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Confirmed') }}:</span>
                                    <span class="font-medium text-green-600 tabular-nums" 
                                          aria-label="{{ __('Confirmed revenue') }}: R$ {{ number_format($metrics['revenue']['confirmed'], 2, ',', '.') }}">
                                        R$ {{ number_format($metrics['revenue']['confirmed'], 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Pending') }}:</span>
                                    <span class="font-medium text-orange-600 tabular-nums" 
                                          aria-label="{{ __('Pending revenue') }}: R$ {{ number_format($metrics['revenue']['pending'], 2, ',', '.') }}">
                                        R$ {{ number_format($metrics['revenue']['pending'], 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="border-t pt-2 mt-3">
                                    <div class="flex justify-between font-semibold items-center">
                                        <span class="text-gray-900 dark:text-gray-100">{{ __('Total') }}:</span>
                                        <span class="text-gray-900 dark:text-gray-100 tabular-nums" 
                                              aria-label="{{ __('Total revenue') }}: R$ {{ number_format($metrics['revenue']['confirmed'] + $metrics['revenue']['pending'], 2, ',', '.') }}">
                                            R$ {{ number_format($metrics['revenue']['confirmed'] + $metrics['revenue']['pending'], 2, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Transport Needs Card --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg focus-within:ring-2 focus-within:ring-purple-500 focus-within:ring-offset-2 transition-shadow duration-200">
                    <div class="p-4 sm:p-6">
                        <div class="border-l-4 border-purple-500 pl-3 sm:pl-4">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3" id="transport-needs-heading">
                                {{ __('Transport Needs') }}
                            </h3>
                            <div class="space-y-2 text-sm" role="group" aria-labelledby="transport-needs-heading">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('From USP') }}:</span>
                                    <span class="font-medium text-purple-600 tabular-nums" 
                                          aria-label="{{ __('Participants needing transport from USP') }}: {{ $metrics['transport_needs']['from_usp'] }}">
                                        {{ number_format($metrics['transport_needs']['from_usp']) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('From GRU') }}:</span>
                                    <span class="font-medium text-purple-600 tabular-nums" 
                                          aria-label="{{ __('Participants needing transport from GRU Airport') }}: {{ $metrics['transport_needs']['from_gru'] }}">
                                        {{ number_format($metrics['transport_needs']['from_gru']) }}
                                    </span>
                                </div>
                                <div class="border-t pt-2 mt-3">
                                    <div class="flex justify-between font-semibold items-center">
                                        <span class="text-gray-900 dark:text-gray-100">{{ __('Total') }}:</span>
                                        <span class="text-gray-900 dark:text-gray-100 tabular-nums" 
                                              aria-label="{{ __('Total participants needing transport') }}: {{ $metrics['transport_needs']['from_usp'] + $metrics['transport_needs']['from_gru'] }}">
                                            {{ number_format($metrics['transport_needs']['from_usp'] + $metrics['transport_needs']['from_gru']) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Secondary Dashboard Content --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                {{-- Registrations by Category Chart --}}
                <section aria-labelledby="registrations-by-category-heading">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4 sm:p-6">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4" 
                                id="registrations-by-category-heading">
                                {{ __('Registrations by Category') }}
                            </h3>
                            <div class="grid grid-cols-2 gap-3 sm:gap-4" role="group" aria-labelledby="registrations-by-category-heading">
                                <div class="text-center p-3 sm:p-4 bg-gray-50 dark:bg-gray-700 rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-2 transition-shadow duration-200">
                                    <div class="text-xl sm:text-2xl font-bold text-blue-600 tabular-nums" 
                                         aria-label="{{ __('Undergraduate students') }}: {{ $metrics['registrations_by_category']['undergrad_student'] }}">
                                        {{ $metrics['registrations_by_category']['undergrad_student'] }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ __('Undergrad Students') }}
                                    </div>
                                </div>
                                <div class="text-center p-3 sm:p-4 bg-gray-50 dark:bg-gray-700 rounded-lg focus-within:ring-2 focus-within:ring-green-500 focus-within:ring-offset-2 transition-shadow duration-200">
                                    <div class="text-xl sm:text-2xl font-bold text-green-600 tabular-nums" 
                                         aria-label="{{ __('Graduate students') }}: {{ $metrics['registrations_by_category']['grad_student'] }}">
                                        {{ $metrics['registrations_by_category']['grad_student'] }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ __('Grad Students') }}
                                    </div>
                                </div>
                                <div class="text-center p-3 sm:p-4 bg-gray-50 dark:bg-gray-700 rounded-lg focus-within:ring-2 focus-within:ring-purple-500 focus-within:ring-offset-2 transition-shadow duration-200">
                                    <div class="text-xl sm:text-2xl font-bold text-purple-600 tabular-nums" 
                                         aria-label="{{ __('Professors') }}: {{ $metrics['registrations_by_category']['professor'] }}">
                                        {{ $metrics['registrations_by_category']['professor'] }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ __('Professors') }}
                                    </div>
                                </div>
                                <div class="text-center p-3 sm:p-4 bg-gray-50 dark:bg-gray-700 rounded-lg focus-within:ring-2 focus-within:ring-orange-500 focus-within:ring-offset-2 transition-shadow duration-200">
                                    <div class="text-xl sm:text-2xl font-bold text-orange-600 tabular-nums" 
                                         aria-label="{{ __('Professionals') }}: {{ $metrics['registrations_by_category']['professional'] }}">
                                        {{ $metrics['registrations_by_category']['professional'] }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ __('Professionals') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Recent Activity Feed --}}
                <section aria-labelledby="recent-activity-heading">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4 sm:p-6">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4" 
                                id="recent-activity-heading">
                                {{ __('Recent Activity') }}
                            </h3>
                            <div aria-labelledby="recent-activity-heading" aria-live="polite" role="region">
                                <livewire:admin.recent-activity-feed />
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Quick Actions --}}
            <section aria-labelledby="quick-actions-heading">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4" 
                            id="quick-actions-heading">
                            {{ __('Quick Actions') }}
                        </h3>
                        <nav aria-labelledby="quick-actions-heading" role="navigation">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                                <a href="{{ route('admin.registrations.index') }}" 
                                   class="group block p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-usp-blue-pri dark:hover:border-usp-blue-pri focus:outline-none focus:ring-2 focus:ring-usp-blue-pri focus:ring-offset-2 focus:border-usp-blue-pri transition-colors duration-200 min-h-[88px]"
                                   aria-describedby="registrations-description"
                                   tabindex="0">
                                    <div class="flex items-center h-full">
                                        <div class="flex-shrink-0">
                                            <svg class="h-6 w-6 sm:h-8 sm:w-8 text-usp-blue-pri group-hover:text-usp-blue-pri group-focus:text-usp-blue-pri" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" 
                                                 aria-hidden="true" role="img">
                                                <title>{{ __('Registrations icon') }}</title>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3 sm:ml-4">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                                                {{ __('View Registrations') }}
                                            </h4>
                                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1" 
                                               id="registrations-description">
                                                {{ __('Manage and review registrations') }}
                                            </p>
                                        </div>
                                    </div>
                                </a>

                                <a href="{{ route('admin.reports.index') }}" 
                                   class="group block p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-usp-yellow dark:hover:border-usp-yellow focus:outline-none focus:ring-2 focus:ring-usp-yellow focus:ring-offset-2 focus:border-usp-yellow transition-colors duration-200 min-h-[88px]"
                                   aria-describedby="reports-description"
                                   tabindex="0">
                                    <div class="flex items-center h-full">
                                        <div class="flex-shrink-0">
                                            <svg class="h-6 w-6 sm:h-8 sm:w-8 text-usp-yellow group-hover:text-usp-yellow group-focus:text-usp-yellow" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" 
                                                 aria-hidden="true" role="img">
                                                <title>{{ __('Reports icon') }}</title>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3 sm:ml-4">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                                                {{ __('Generate Reports') }}
                                            </h4>
                                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1" 
                                               id="reports-description">
                                                {{ __('View detailed analytics and reports') }}
                                            </p>
                                        </div>
                                    </div>
                                </a>

                                <a href="{{ route('admin.registrations.index') }}?filter=pending_approval" 
                                   class="group block p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-orange-500 dark:hover:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:border-orange-500 transition-colors duration-200 min-h-[88px]"
                                   aria-describedby="pending-approvals-description"
                                   tabindex="0">
                                    <div class="flex items-center h-full">
                                        <div class="flex-shrink-0">
                                            <svg class="h-6 w-6 sm:h-8 sm:w-8 text-orange-500 group-hover:text-orange-500 group-focus:text-orange-500" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" 
                                                 aria-hidden="true" role="img">
                                                <title>{{ __('Pending approvals icon') }}</title>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3 sm:ml-4">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                                                {{ __('Pending Approvals') }}
                                            </h4>
                                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1" 
                                               id="pending-approvals-description">
                                                {{ __('Review items awaiting approval') }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </nav>
                    </div>
                </div>
            </section>
            </main>
        </div>
    </div>
</x-app-layout>