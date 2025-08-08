<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ __('Registrations by Category') }}
                </h3>
            </div>
        </div>

        @if(empty($data))
            <div class="text-center py-8 text-gray-500">
                {{ __('No registration data available') }}
            </div>
        @else
            {{-- Simple visual representation with bars --}}
            <div class="space-y-3">
                @foreach($data as $item)
                    @php
                        $percentage = $this->getPercentage($item['count']);
                        $maxWidth = $percentage > 0 ? max($percentage, 5) : 0; // Minimum 5% width for visibility
                    @endphp
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700 truncate">
                                    {{ __($item['label']) }}
                                </span>
                                <span class="ml-2 text-gray-500">
                                    {{ number_format($item['count']) }} ({{ $percentage }}%)
                                </span>
                            </div>
                            <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full transition-all duration-300"
                                     style="width: {{ $maxWidth }}%; background-color: {{ $item['color'] }};">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Summary --}}
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex justify-between text-sm font-medium text-gray-900">
                    <span>{{ __('Total') }}</span>
                    <span>{{ number_format($this->getTotalCount()) }}</span>
                </div>
            </div>
        @endif
    </div>
</div>