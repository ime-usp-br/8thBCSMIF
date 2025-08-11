<div class="bg-white overflow-hidden shadow rounded-lg"
     x-data="{ loaded: false }"
     x-init="setTimeout(() => { loaded = true; $wire.loadProgressively(); }, 1000)">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        {{ __('Transport Needs') }}
                    </dt>
                    <dd class="text-2xl font-semibold text-gray-900">
                        @if($isLoading)
                            <div class="animate-pulse bg-gray-300 h-8 rounded w-16"></div>
                        @else
                            {{ number_format($data['total'] ?? 0) }}
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        @if($isLoading)
            {{-- Loading State with Skeleton --}}
            <div class="mt-5 space-y-3 animate-pulse">
                @for($i = 0; $i < 3; $i++)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-gray-300 rounded-full mr-2"></div>
                            <div class="h-4 bg-gray-300 rounded w-20"></div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="h-4 bg-gray-300 rounded w-8"></div>
                            <div class="h-6 bg-gray-300 rounded-full w-16"></div>
                        </div>
                    </div>
                @endfor
                <div class="mt-5 pt-4 border-t border-gray-200">
                    <div class="h-4 bg-gray-300 rounded w-32"></div>
                </div>
            </div>
        @else
            {{-- Transport Breakdown --}}
        <div class="mt-5 space-y-3">
            {{-- USP Transport --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-blue-400 rounded-full mr-2"></div>
                    <span class="text-sm font-medium text-gray-700">
                        {{ __('From USP') }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-900 font-medium">
                        {{ number_format($data['from_usp'] ?? 0) }}
                    </span>
                    @if(($data['from_usp'] ?? 0) > 0)
                        <button wire:click="goToUSPTransportList" 
                                class="text-xs bg-blue-100 hover:bg-blue-200 text-blue-800 px-2 py-1 rounded-full transition-colors">
                            {{ __('View List') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- GRU Airport Transport --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                    <span class="text-sm font-medium text-gray-700">
                        {{ __('From GRU Airport') }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-900 font-medium">
                        {{ number_format($data['from_gru'] ?? 0) }}
                    </span>
                    @if(($data['from_gru'] ?? 0) > 0)
                        <button wire:click="goToGRUTransportList" 
                                class="text-xs bg-green-100 hover:bg-green-200 text-green-800 px-2 py-1 rounded-full transition-colors">
                            {{ __('View List') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Both Transport Options --}}
            @if(($data['both'] ?? 0) > 0)
                <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-purple-400 rounded-full mr-2"></div>
                        <span class="text-sm font-medium text-gray-700">
                            {{ __('Both Options') }}
                        </span>
                    </div>
                    <span class="text-sm text-gray-900 font-medium">
                        {{ number_format($data['both'] ?? 0) }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Transport Reports Link --}}
        @if(($data['total'] ?? 0) > 0)
            <div class="mt-5 pt-4 border-t border-gray-200">
                <button wire:click="goToTransportReports" 
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                    {{ __('View Transport Reports') }}
                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        @else
            <div class="mt-5 pt-4 border-t border-gray-200 text-center">
                <span class="text-sm text-gray-500">
                    {{ __('No transport requests') }}
                </span>
            </div>
        @endif
        @endif
    </div>
</div>