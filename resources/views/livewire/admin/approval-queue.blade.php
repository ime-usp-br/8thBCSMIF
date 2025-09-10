<div>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-4 sm:p-6">
            <!-- AC1: Header with USP Brand Colors -->
            <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('Approval Queue') }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ __('Pending payment and enrollment proof validations') }} 
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-usp-yellow text-gray-900 ml-2">
                        {{ $totalPending }} {{ __('items') }}
                    </span>
                </p>
            </div>

            <!-- AC1: Flash Messages -->
            @if (session()->has('success'))
                <div class="mb-6 p-4 rounded-md bg-green-50 border-l-4 border-green-400">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-6 p-4 rounded-md bg-red-50 border-l-4 border-red-400">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- AC1: Filters Section -->
            <div class="mb-8 bg-gray-50 dark:bg-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600">
                <!-- Filter Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        <svg class="w-5 h-5 text-usp-blue-pri mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z" />
                        </svg>
                        {{ __('Filter Pending Items') }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <!-- Quick Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Search Participant') }}
                        </label>
                        <input type="text" 
                               id="search"
                               wire:model.live.debounce.300ms="search" 
                               placeholder="{{ __('Search by name or email...') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri">
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="filterType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Proof Type') }}
                        </label>
                        <select id="filterType" 
                                wire:model.live="filterType"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri">
                            <option value="">{{ __('All Types') }}</option>
                            <option value="payment">{{ __('Payment Proofs') }}</option>
                            <option value="enrollment">{{ __('Enrollment Proofs') }}</option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    <div class="flex items-end">
                        <button wire:click="clearFilters" 
                                class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded-md text-sm font-medium transition-colors duration-200">
                            {{ __('Clear Filters') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- AC1: Approval Queue Table -->
            @if($pendingItems->isNotEmpty())
                <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600 table-fixed">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="w-32 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Type') }}
                                </th>
                                <th scope="col" class="w-56 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Participant') }}
                                </th>
                                <th scope="col" class="w-32 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Registration') }}
                                </th>
                                <th scope="col" class="w-48 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Events') }}
                                </th>
                                <th scope="col" class="w-32 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Upload Date') }}
                                </th>
                                <th scope="col" class="w-40 px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            @foreach($pendingItems as $index => $item)
                                @php
                                    $prevItem = $index > 0 ? $pendingItems[$index - 1] : null;
                                    $nextItem = $index < count($pendingItems) - 1 ? $pendingItems[$index + 1] : null;
                                    $isFirstInGroup = !$prevItem || !$item['requires_dual_validation'] || $prevItem['registration_id'] !== $item['registration_id'];
                                    $isLastInGroup = !$nextItem || !$item['requires_dual_validation'] || $nextItem['registration_id'] !== $item['registration_id'];
                                    $isConnectedGroup = $item['requires_dual_validation'] && (!$isFirstInGroup || !$isLastInGroup);
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 
                                    {{ $item['requires_dual_validation'] ? 'bg-usp-blue-pri/5 dark:bg-usp-blue-pri/10' : '' }}
                                    {{ $isConnectedGroup && $isFirstInGroup ? 'border-t-2 border-t-usp-blue-pri/30' : '' }}
                                    {{ $isConnectedGroup && $isLastInGroup ? 'border-b-2 border-b-usp-blue-pri/30' : '' }}
                                    {{ $isConnectedGroup && !$isFirstInGroup && !$isLastInGroup ? 'border-l-4 border-l-usp-blue-pri/40' : '' }}">
                                    
                                    <!-- Type Column with Stacked Tags -->
                                    <td class="px-4 py-4 w-32">
                                        <div class="space-y-1">
                                            <!-- Main Type Badge -->
                                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium w-full justify-center
                                                {{ $item['type'] === 'payment' 
                                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' 
                                                    : 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' }}">
                                                @if($item['type'] === 'payment')
                                                    <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                                                    </svg>
                                                @endif
                                                <span class="truncate">{{ $item['type_label'] }}</span>
                                            </div>

                                            <!-- Dual Validation Badge -->
                                            @if($item['requires_dual_validation'])
                                                <div class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-usp-yellow/20 text-usp-blue-pri border border-usp-blue-pri/30 w-full justify-center">
                                                    <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                    </svg>
                                                    <span class="truncate">{{ __('Dual Validation') }}</span>
                                                </div>
                                            @endif

                                            <!-- Registration Category Indicator -->
                                            @if($item['registration_category'] === 'grad_student' && $item['requires_dual_validation'])
                                                <div class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100 w-full justify-center">
                                                    <span class="truncate">{{ __('Graduate Student') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Participant Column -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $item['participant_name'] }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $item['participant_email'] }}
                                        </div>
                                    </td>

                                    <!-- Registration Column -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="space-y-1">
                                            <!-- Registration ID with Dual Validation Progress -->
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ __('ID: :id', ['id' => $item['registration']->id]) }}
                                                </span>
                                                @if($item['requires_dual_validation'])
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-usp-blue-pri/20 text-usp-blue-pri">
                                                        {{ $item['dual_validation_type'] === 'payment_first' ? '1/2' : '2/2' }}
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Amount Info -->
                                            @if($item['amount'])
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ __('Amount: R$ :amount', ['amount' => number_format($item['amount'], 2, ',', '.')]) }}
                                                </div>
                                            @endif

                                            <!-- Dual Validation Status Message -->
                                            @if($item['requires_dual_validation'])
                                                <div class="text-xs text-usp-blue-pri font-medium">
                                                    @if($item['dual_validation_type'] === 'payment_first')
                                                        {{ __('Payment validation (enrollment also required)') }}
                                                    @else
                                                        {{ __('Enrollment validation (payment also required)') }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Events Column - Badges like registrations page -->
                                    <td class="px-4 py-4 w-48">
                                        <div class="flex flex-wrap gap-1">
                                            @if($item['events']->count() > 0)
                                                @foreach($item['events']->take(2) as $event)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-usp-blue-sec/20 text-usp-blue-pri">
                                                        {{ $event->code }}
                                                    </span>
                                                @endforeach
                                                @if($item['events']->count() > 2)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                                                        +{{ $item['events']->count() - 2 }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500 text-sm">{{ __('No events') }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Upload Date Column -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item['created_at']->format('d/m/Y H:i') }}
                                    </td>

                                    <!-- AC1: Actions Column with Direct Links -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex items-center justify-center space-x-2">
                                            <!-- View Details Link -->
                                            <a href="{{ route('admin.registrations.show', $item['registration']->id) }}" 
                                               class="text-usp-blue-pri hover:text-usp-blue-sec dark:text-usp-blue-sec dark:hover:text-usp-blue-pri transition-colors duration-200"
                                               title="{{ __('View Details') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>

                                            <!-- Download Proof Link -->
                                            @if($item['has_file'])
                                                @if($item['type'] === 'payment')
                                                    <a href="{{ route('admin.registrations.download-proof', $item['registration']->id) }}"
                                                       class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-200"
                                                       title="{{ __('Download Proof') }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                    </a>
                                                @else
                                                    <a href="{{ route('admin.registrations.download-enrollment-proof', $item['registration']->id) }}"
                                                       class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-200"
                                                       title="{{ __('Download Proof') }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                    </a>
                                                @endif
                                            @endif

                                            <!-- AC1: Quick Approve Button (Async) -->
                                            <button wire:click="quickApprove('{{ $item['type'] }}', {{ $item['id'] }})"
                                                    wire:confirm="{{ __('Are you sure you want to approve this proof?') }}"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors duration-200"
                                                    title="{{ __('Quick Approve') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>

                                            <!-- Reject with Reason Button -->
                                            <button wire:click="openRejectModal('{{ $item['type'] }}', {{ $item['id'] }})"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                                    title="{{ __('Reject with Reason') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ __('No pending approvals') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('All proofs have been processed or no proofs are waiting for validation.') }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Rejection Modal -->
    <div x-data="{ open: @entangle('showRejectModal') }" 
         x-show="open" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="open" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <!-- Modal content -->
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Reject Proof') }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    {{ __('Please provide a reason for rejecting this proof. This will be sent to the participant.') }}
                                </p>
                                <div>
                                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('Rejection Reason') }}
                                    </label>
                                    <textarea wire:model="rejectionReason" 
                                              id="rejection_reason"
                                              rows="4" 
                                              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri"
                                              placeholder="{{ __('Enter the reason for rejection...') }}"></textarea>
                                    @error('rejectionReason')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="rejectWithReason"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        <span wire:loading.remove>{{ __('Reject Proof') }}</span>
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('Processing...') }}
                        </span>
                    </button>
                    <button wire:click="closeRejectModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-usp-blue-pri sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>