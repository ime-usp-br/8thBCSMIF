<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold">{{ __('Add Additional Events') }}</h2>
                    <a href="{{ route('registrations.my') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                       wire:navigate>
                        ← {{ __('Back to My Registrations') }}
                    </a>
                </div>

                {{-- Display flash messages --}}
                @if (session()->has('success'))
                    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Show current accessible events --}}
                @if(!empty($userAccessibleEvents))
                    <div class="mb-8 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                        <h3 class="font-medium text-green-800 dark:text-green-300 mb-3">
                            🔒 {{ __('Events You Currently Have Paid Access To (PERMANENT)') }}
                        </h3>
                        <p class="text-sm text-green-700 dark:text-green-400 mb-3">
                            {{ __('These events are paid and permanent. No refunds or cancellations are allowed once payment is confirmed.') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($userAccessibleEvents as $event)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300">
                                    🔒 {{ $event['name'] ?? $event['code'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Event selection form --}}
                <form wire:submit="submit" class="space-y-6">
                    {{-- Available Events Section --}}
                    <div>
                        <h3 class="text-lg font-medium mb-4">{{ __('Select Additional Events') }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            {{ __('Choose the additional events you would like to register for. You will only be charged for new events.') }}
                        </p>
                        
                        @if(!empty($existingEventCodes))
                            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                                <p class="text-sm text-blue-800 dark:text-blue-300 flex items-center">
                                    <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Your current events are pre-selected below and cannot be unchecked. Select additional events you want to add.') }}
                                </p>
                            </div>
                        @endif

                        @if(!empty($availableEvents))
                            <div class="space-y-3">
                                @foreach($availableEvents as $event)
                                    @php
                                        $isExistingEvent = in_array($event['code'], $existingEventCodes);
                                        $isImmutable = in_array($event['code'], $immutableEventCodes);
                                        $isDisabled = $isExistingEvent; // ALL existing events are disabled (can't be unchecked)
                                        $bgClass = $isImmutable ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700' : ($isExistingEvent ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700' : 'bg-white dark:bg-gray-900');
                                    @endphp
                                    <div class="border rounded-lg p-4 {{ $bgClass }}">
                                        <label class="flex items-start space-x-3 {{ $isDisabled ? '' : 'cursor-pointer' }}">
                                            <input 
                                                type="checkbox" 
                                                value="{{ $event['code'] }}" 
                                                wire:model.live="selectedEvents"
                                                @if($isDisabled) 
                                                    onclick="return false;" 
                                                    style="pointer-events: none;"
                                                @endif
                                                class="mt-1 h-4 w-4 {{ $isImmutable ? 'text-green-600' : ($isExistingEvent ? 'text-blue-600' : 'text-indigo-600') }} border-gray-300 rounded focus:ring-blue-500 {{ $isDisabled ? 'opacity-75' : '' }}"
                                            >
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                            {{ $event['name'] }}
                                                        </h4>
                                                        <div class="flex items-center mt-1 space-x-2">
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300">
                                                                {{ $event['code'] }}
                                                            </span>
                                                            @if($event['is_main_conference'])
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-300">
                                                                    {{ __('Main Conference') }}
                                                                </span>
                                                            @endif
                                                            @if($isImmutable)
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300">
                                                                    🔒 {{ __('PAID - PERMANENT') }}
                                                                </span>
                                                            @elseif($isExistingEvent)
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300">
                                                                    📋 {{ __('Currently Registered') }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        @if($event['description'])
                                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                                {{ Str::limit($event['description'], 150) }}
                                                            </p>
                                                        @endif
                                                        @if($event['start_date'] && $event['end_date'])
                                                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                                                {{ \Carbon\Carbon::parse($event['start_date'])->format('M d') }} - 
                                                                {{ \Carbon\Carbon::parse($event['end_date'])->format('M d, Y') }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                                {{ __('No additional events available at the moment.') }}
                            </p>
                        @endif

                        @error('selectedEvents')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Fee Calculation Display --}}
                    @if($showCalculation && !empty($feeCalculation))
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-gray-50 dark:bg-gray-800">
                            <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-gray-100">
                                {{ __('Fee Calculation') }}
                            </h3>

                            @if($feeCalculation['can_register'])
                                @if(!empty($feeCalculation['details']))
                                    <div class="space-y-3 mb-4">
                                        @foreach($feeCalculation['details'] as $detail)
                                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                                <span class="text-gray-700 dark:text-gray-300">{{ $detail['event_name'] }}</span>
                                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                                    R$ {{ number_format($detail['calculated_price'], 2, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="border-t border-gray-300 dark:border-gray-600 pt-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {{ __('Total Additional Fee') }}
                                            </span>
                                            <span class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                                R$ {{ number_format($feeCalculation['difference_to_pay'], 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($feeCalculation['difference_to_pay'] == 0)
                                        <div class="mt-4 p-3 bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded">
                                            {{ __('These events are free! No additional payment required.') }}
                                        </div>
                                    @else
                                        <div class="mt-4 p-3 bg-blue-100 dark:bg-blue-900/20 border border-blue-400 dark:border-blue-700 text-blue-700 dark:text-blue-300 rounded">
                                            {{ __('You will need to make an additional payment for these events.') }}
                                        </div>
                                    @endif
                                @endif
                            @else
                                <div class="p-4 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded">
                                    {{ $message }}
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- No Refund Policy Warning --}}
                    <div class="border border-yellow-200 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                    {{ __('Important: No Refund Policy') }}
                                </h3>
                                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-400">
                                    {{ __('Once payment is confirmed, events become permanent and non-refundable. You cannot cancel or modify paid events. Only additional events can be added.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex justify-between items-center pt-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('After submission, you will receive payment instructions if additional payment is required.') }}
                        </div>
                        
                        <button 
                            type="submit" 
                            :disabled="!$wire.selectedEvents.length"
                            class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="submit">
                                {{ __('Add Selected Events') }}
                            </span>
                            <span wire:loading wire:target="submit" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Processing...') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>