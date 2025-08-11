<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Admin Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Overview Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                
                <!-- Total Registrations Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="border-l-4 border-usp-blue-pri pl-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ __('Total Registrations') }}
                                    </h3>
                                    <p class="text-3xl font-bold text-usp-blue-pri mt-2">
                                        {{ number_format($metrics['total_registrations']['count']) }}
                                    </p>
                                    <div class="flex items-center mt-2">
                                        @if($metrics['total_registrations']['trend'] === 'up')
                                            <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            </svg>
                                            <span class="text-green-600 text-sm">+{{ $metrics['total_registrations']['change_percent'] }}%</span>
                                        @else
                                            <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                            </svg>
                                            <span class="text-red-600 text-sm">-{{ $metrics['total_registrations']['change_percent'] }}%</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-usp-blue-pri/20">
                                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zM4 18v-4h3v4h2v-7.5c0-1.1.9-2 2-2s2 .9 2 2V18h2v-4h3v4h4V8H0v10h4z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="border-l-4 border-orange-500 pl-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                {{ __('Pending Approvals') }}
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Payment Proofs') }}:</span>
                                    <span class="font-medium text-orange-600">{{ number_format($metrics['pending_approvals']['payment_proofs']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Enrollment Proofs') }}:</span>
                                    <span class="font-medium text-orange-600">{{ number_format($metrics['pending_approvals']['enrollment_proofs']) }}</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('admin.registrations.index') }}" 
                                   class="inline-flex items-center px-3 py-2 bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-400 focus:bg-orange-400 active:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('Review Queue') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="border-l-4 border-usp-yellow pl-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                {{ __('Revenue') }}
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Confirmed') }}:</span>
                                    <span class="font-medium text-green-600">R$ {{ number_format($metrics['revenue']['confirmed'], 2, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Pending') }}:</span>
                                    <span class="font-medium text-orange-600">R$ {{ number_format($metrics['revenue']['pending'], 2, ',', '.') }}</span>
                                </div>
                                <div class="border-t pt-2 mt-3">
                                    <div class="flex justify-between font-semibold">
                                        <span class="text-gray-900 dark:text-gray-100">{{ __('Total') }}:</span>
                                        <span class="text-gray-900 dark:text-gray-100">R$ {{ number_format($metrics['revenue']['confirmed'] + $metrics['revenue']['pending'], 2, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transport Needs Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="border-l-4 border-purple-500 pl-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                {{ __('Transport Needs') }}
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('From USP') }}:</span>
                                    <span class="font-medium text-purple-600">{{ number_format($metrics['transport_needs']['from_usp']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('From GRU') }}:</span>
                                    <span class="font-medium text-purple-600">{{ number_format($metrics['transport_needs']['from_gru']) }}</span>
                                </div>
                                <div class="border-t pt-2 mt-3">
                                    <div class="flex justify-between font-semibold">
                                        <span class="text-gray-900 dark:text-gray-100">{{ __('Total') }}:</span>
                                        <span class="text-gray-900 dark:text-gray-100">{{ number_format($metrics['transport_needs']['from_usp'] + $metrics['transport_needs']['from_gru']) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registrations by Category Chart Placeholder -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            {{ __('Registrations by Category') }}
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ $metrics['registrations_by_category']['undergrad_student'] }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Undergrad Students') }}</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ $metrics['registrations_by_category']['grad_student'] }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Grad Students') }}</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600">{{ $metrics['registrations_by_category']['professor'] }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Professors') }}</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-orange-600">{{ $metrics['registrations_by_category']['professional'] }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Professionals') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <livewire:admin.recent-activity-feed />
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        {{ __('Quick Actions') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ route('admin.registrations.index') }}" 
                           class="block p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-usp-blue-pri" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('View Registrations') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manage and review registrations') }}</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.reports.index') }}" 
                           class="block p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-usp-yellow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Generate Reports') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('View detailed analytics and reports') }}</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.registrations.index') }}?filter=pending_approval" 
                           class="block p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Pending Approvals') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Review items awaiting approval') }}</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>