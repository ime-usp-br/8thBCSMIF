<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        {{ __('Total Registrations') }}
                    </dt>
                    <dd class="flex items-baseline">
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ number_format($data['total'] ?? 0) }}
                        </div>
                        @if(isset($data['trend']))
                            @php
                                $change = $data['trend']['percentage_change'] ?? 0;
                                $isPositive = $change >= 0;
                            @endphp
                            <div class="ml-2 flex items-baseline text-sm">
                                @if($isPositive)
                                    <svg class="flex-shrink-0 self-center w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-green-600 font-medium">{{ number_format(abs($change), 1) }}%</span>
                                @else
                                    <svg class="flex-shrink-0 self-center w-3 h-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-red-600 font-medium">{{ number_format(abs($change), 1) }}%</span>
                                @endif
                            </div>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
        
        @if(isset($data['trend']))
            <div class="mt-5">
                <div class="flex justify-between text-sm text-gray-500">
                    <span>{{ __('This month') }}: {{ number_format($data['trend']['current_month'] ?? 0) }}</span>
                    <span>{{ __('Last month') }}: {{ number_format($data['trend']['previous_month'] ?? 0) }}</span>
                </div>
            </div>
        @endif
    </div>
</div>