<div>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-4 sm:p-6">
            <!-- Header with USP Brand Colors -->
            <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('Registration List') }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ __('Manage conference registrations') }}
                </p>
            </div>
            
            <!-- Filters Section - Improved Mobile Layout -->
            <div class="mb-6 space-y-4 sm:space-y-0 sm:grid sm:grid-cols-1 lg:grid-cols-2 sm:gap-4">
                <!-- Event Filter -->
                <div>
                    <label for="filterEventCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Filter by Event') }}
                    </label>
                    <select wire:model.live="filterEventCode" id="filterEventCode" 
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri sm:text-sm transition-colors duration-200">
                        <option value="">{{ __('All Events') }}</option>
                        <option value="BCSMIF2025">{{ __('8th BCSMIF') }}</option>
                        <option value="RAA2025">{{ __('RAA2025') }}</option>
                        <option value="WDA2025">{{ __('WDA2025') }}</option>
                    </select>
                </div>
                
                <!-- Payment Status Filter -->
                <div>
                    <label for="filterPaymentStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Filter by Payment Status') }}
                    </label>
                    <select wire:model.live="filterPaymentStatus" id="filterPaymentStatus" 
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri sm:text-sm transition-colors duration-200">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="pending_payment">{{ __('Pending Payment') }}</option>
                        <option value="paid_br">{{ __('Paid (BR)') }}</option>
                        <option value="paid_int">{{ __('Paid (International)') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                </div>
            </div>
            
            @if($registrations->count() > 0)
                <!-- Mobile Cards Layout (Hidden on Desktop) -->
                <div class="block lg:hidden space-y-4">
                    @foreach($registrations as $registration)
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                            <div class="p-4">
                                <!-- Header with ID and Status -->
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 mr-2">{{ __('ID') }}</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">#{{ $registration->id }}</span>
                                    </div>
                                    @php
                                        $statusColors = [
                                            'pending_payment' => 'bg-yellow-100 text-yellow-800',
                                            'paid_br' => 'bg-green-100 text-green-800',
                                            'paid_int' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusLabels = [
                                            'pending_payment' => __('Pending Payment'),
                                            'paid_br' => __('Paid (BR)'),
                                            'paid_int' => __('Paid (International)'),
                                            'cancelled' => __('Cancelled'),
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$registration->payment_status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$registration->payment_status] ?? $registration->payment_status }}
                                    </span>
                                </div>
                                
                                <!-- Participant Info -->
                                <div class="space-y-2 mb-4">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Name') }}:</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100 ml-1">{{ $registration->full_name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Email') }}:</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100 ml-1 break-all">{{ $registration->email }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Fee') }}:</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 ml-1">R$ {{ number_format($registration->calculated_fee, 2, ',', '.') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Events -->
                                <div class="mb-4">
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-1">{{ __('Events') }}:</span>
                                    <div class="flex flex-wrap gap-1">
                                        @if($registration->events->count() > 0)
                                            @foreach($registration->events as $event)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-usp-blue-sec/20 text-usp-blue-pri">
                                                    {{ $event->code }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500 text-sm">{{ __('No events') }}</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Date and Actions -->
                                <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $registration->created_at->format('d/m/Y H:i') }}
                                    </span>
                                    <a href="{{ route('admin.registrations.show', $registration) }}" 
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-white bg-usp-blue-pri hover:bg-usp-blue-pri/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-usp-blue-pri transition-colors duration-200">
                                        {{ __('Details') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Desktop Table Layout (Hidden on Mobile) -->
                <div class="hidden lg:block overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('ID') }}
                                </th>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Participant') }}
                                </th>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">
                                    {{ __('Email') }}
                                </th>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Events') }}
                                </th>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Fee') }}
                                </th>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">
                                    {{ __('Date') }}
                                </th>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($registrations as $registration)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #{{ $registration->id }}
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        <div class="font-medium">{{ $registration->full_name }}</div>
                                        <div class="text-gray-500 dark:text-gray-400 xl:hidden text-xs mt-1 truncate max-w-32">{{ $registration->email }}</div>
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 hidden xl:table-cell">
                                        {{ $registration->email }}
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-wrap gap-1">
                                            @if($registration->events->count() > 0)
                                                @foreach($registration->events as $event)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-usp-blue-sec/20 text-usp-blue-pri">
                                                        {{ $event->code }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">{{ __('No events') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        R$ {{ number_format($registration->calculated_fee, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending_payment' => 'bg-yellow-100 text-yellow-800',
                                                'paid_br' => 'bg-green-100 text-green-800',
                                                'paid_int' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                            ];
                                            $statusLabels = [
                                                'pending_payment' => __('Pending Payment'),
                                                'paid_br' => __('Paid (BR)'),
                                                'paid_int' => __('Paid (International)'),
                                                'cancelled' => __('Cancelled'),
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$registration->payment_status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabels[$registration->payment_status] ?? $registration->payment_status }}
                                        </span>
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden xl:table-cell">
                                        {{ $registration->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.registrations.show', $registration) }}" 
                                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-usp-blue-pri hover:bg-usp-blue-pri/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-usp-blue-pri transition-colors duration-200">
                                            <span class="hidden xl:inline">{{ __('Details') }}</span>
                                            <span class="xl:hidden">{{ __('View') }}</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Enhanced Pagination -->
                <div class="mt-6 px-4 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 rounded-b-lg">
                    {{ $registrations->links() }}
                </div>
            @else
                <!-- Enhanced Empty State -->
                <div class="text-center py-16 px-6">
                    <div class="mx-auto h-24 w-24 mb-4">
                        <svg class="h-full w-full text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('No registrations found') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm max-w-sm mx-auto">
                        {{ __('No registrations match your current filters. Try adjusting your search criteria.') }}
                    </p>
                    <div class="mt-6">
                        <button wire:click="$set('filterEventCode', '')" class="text-sm text-usp-blue-pri hover:text-usp-blue-pri/80 dark:text-usp-blue-sec dark:hover:text-usp-blue-pri font-medium">
                            {{ __('Clear all filters') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>