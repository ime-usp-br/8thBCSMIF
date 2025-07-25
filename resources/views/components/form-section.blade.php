@props([
    'title' => '',
    'description' => '',
    'icon' => null,
    'step' => null,
    'completed' => false,
    'collapsible' => false,
    'expanded' => true
])

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
     x-data="{ expanded: {{ $expanded ? 'true' : 'false' }} }">
    
    <!-- Section Header -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-700 dark:to-gray-800"
         @if($collapsible) 
         @click="expanded = !expanded" 
         class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200"
         @endif>
        
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <!-- Step Number or Icon -->
                @if($step)
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                            @if($completed)
                                bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                            @else
                                bg-usp-blue-pri text-white
                            @endif">
                            @if($completed)
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            @else
                                {{ $step }}
                            @endif
                        </div>
                    </div>
                @elseif($icon)
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-usp-blue-pri/10 flex items-center justify-center">
                            {!! $icon !!}
                        </div>
                    </div>
                @endif

                <!-- Title and Description -->
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        {{ $title }}
                        @if($completed)
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </h3>
                    @if($description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $description }}</p>
                    @endif
                </div>
            </div>

            <!-- Collapse/Expand Icon -->
            @if($collapsible)
                <div class="flex-shrink-0">
                    <svg x-show="!expanded" class="w-5 h-5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <svg x-show="expanded" class="w-5 h-5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </div>
            @endif
        </div>
    </div>

    <!-- Section Content -->
    <div x-show="expanded" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 max-h-0"
         x-transition:enter-end="opacity-100 max-h-full"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 max-h-full"
         x-transition:leave-end="opacity-0 max-h-0"
         class="px-6 py-6">
        {{ $slot }}
    </div>
</div>