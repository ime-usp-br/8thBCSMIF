<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        {{ __('Total Revenue') }}
                    </dt>
                    <dd class="text-2xl font-semibold text-gray-900">
                        {{ $this->formatCurrency($data['total'] ?? 0) }}
                    </dd>
                </dl>
            </div>
        </div>

        {{-- Revenue Breakdown --}}
        <div class="mt-5 space-y-4">
            {{-- Confirmed Revenue --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-400 rounded-full mr-2"></div>
                    <span class="text-sm font-medium text-gray-700">
                        {{ __('Confirmed') }}
                    </span>
                    <span class="ml-2 text-xs text-gray-500">
                        ({{ $this->getConfirmedPercentage() }}%)
                    </span>
                </div>
                <span class="text-sm text-gray-900 font-medium">
                    {{ $this->formatCurrency($data['confirmed'] ?? 0) }}
                </span>
            </div>

            {{-- Pending Revenue --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-yellow-400 rounded-full mr-2"></div>
                    <span class="text-sm font-medium text-gray-700">
                        {{ __('Pending') }}
                    </span>
                </div>
                <span class="text-sm text-gray-900 font-medium">
                    {{ $this->formatCurrency($data['pending'] ?? 0) }}
                </span>
            </div>

            {{-- Visual Progress Bar --}}
            @if(($data['total'] ?? 0) > 0)
                <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                    <div class="bg-green-500 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ $this->getConfirmedPercentage() }}%;">
                    </div>
                </div>
            @endif
        </div>

        {{-- Additional Info --}}
        @if(($data['total'] ?? 0) > 0)
            <div class="mt-5 pt-4 border-t border-gray-200">
                <p class="text-xs text-gray-500">
                    {{ __('Confirmed revenue reflects approved payments only') }}
                </p>
            </div>
        @else
            <div class="mt-5 pt-4 border-t border-gray-200 text-center">
                <span class="text-sm text-gray-500">
                    {{ __('No revenue data available') }}
                </span>
            </div>
        @endif
    </div>
</div>