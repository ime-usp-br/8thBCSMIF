<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Reports Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Enrollment Proofs Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="border-l-4 border-usp-blue-pri pl-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {{ __('Enrollment Proofs') }}
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Total') }}:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($enrollmentProofsStats['total']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-yellow-600">{{ __('Pending') }}:</span>
                                    <span class="font-medium text-yellow-600">{{ number_format($enrollmentProofsStats['pending']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-green-600">{{ __('Approved') }}:</span>
                                    <span class="font-medium text-green-600">{{ number_format($enrollmentProofsStats['approved']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-red-600">{{ __('Rejected') }}:</span>
                                    <span class="font-medium text-red-600">{{ number_format($enrollmentProofsStats['rejected']) }}</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('admin.reports.enrollment-proofs') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-usp-blue-pri border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-usp-blue-pri/90 focus:bg-usp-blue-pri/90 active:bg-usp-blue-pri/80 focus:outline-none focus:ring-2 focus:ring-usp-blue-pri focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('View Details') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payments Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="border-l-4 border-usp-yellow pl-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {{ __('Payments') }}
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Total') }}:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($paymentsStats['total']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-yellow-600">{{ __('Pending') }}:</span>
                                    <span class="font-medium text-yellow-600">{{ number_format($paymentsStats['pending']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-green-600">{{ __('Approved') }}:</span>
                                    <span class="font-medium text-green-600">{{ number_format($paymentsStats['approved']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Total Amount') }}:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">R$ {{ number_format($paymentsStats['total_amount'], 2, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('admin.reports.payments') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-usp-yellow border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-usp-yellow/90 focus:bg-usp-yellow/90 active:bg-usp-yellow/80 focus:outline-none focus:ring-2 focus:ring-usp-yellow focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('View Details') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Auto-approved Payments Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="border-l-4 border-green-500 pl-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {{ __('Auto-approved Payments') }}
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Total') }}:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($autoApprovedStats['total']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Grad Students') }}:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($autoApprovedStats['graduate_students']) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Workshop Registrations') }}:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($autoApprovedStats['workshop_registrations']) }}</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('admin.reports.auto-approved') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('View Details') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Navigation -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        {{ __('Quick Navigation') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ route('admin.reports.enrollment-proofs') }}" 
                           class="block p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-usp-blue-pri" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Enrollment Proofs') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Detailed enrollment proofs report') }}</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.reports.payments') }}" 
                           class="block p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-usp-yellow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Payments') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Detailed payments report') }}</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.reports.auto-approved') }}" 
                           class="block p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Auto-approved Payments') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Graduate students workshop payments') }}</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>