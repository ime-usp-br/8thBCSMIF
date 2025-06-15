<div>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ __('Registration List') }}
            </h3>
            
            <!-- Filters Section -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Event Filter -->
                <div>
                    <label for="filterEventCode" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('Filter by Event') }}
                    </label>
                    <select wire:model.live="filterEventCode" id="filterEventCode" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">{{ __('All Events') }}</option>
                        <option value="BCSMIF2025">{{ __('8th BCSMIF') }}</option>
                        <option value="RAA2025">{{ __('RAA2025') }}</option>
                        <option value="WDA2025">{{ __('WDA2025') }}</option>
                    </select>
                </div>
                
                <!-- Payment Status Filter -->
                <div>
                    <label for="filterPaymentStatus" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('Filter by Payment Status') }}
                    </label>
                    <select wire:model.live="filterPaymentStatus" id="filterPaymentStatus" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="pending_payment">{{ __('Pending Payment') }}</option>
                        <option value="paid_br">{{ __('Paid (BR)') }}</option>
                        <option value="paid_int">{{ __('Paid (International)') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                </div>
            </div>
            
            @if($registrations->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Registration ID') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Participant Name') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Participant Email') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Events') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Total Fee') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Payment Status') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Registration Date') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($registrations as $registration)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #{{ $registration->id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $registration->full_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $registration->email }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        @if($registration->events->count() > 0)
                                            @foreach($registration->events as $event)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1 mb-1">
                                                    {{ $event->code }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400">{{ __('No events') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        R$ {{ number_format($registration->calculated_fee, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $registration->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.registrations.show', $registration) }}" 
                                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            {{ __('Details') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    {{ $registrations->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-gray-400 text-lg">
                        {{ __('No registrations found') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>