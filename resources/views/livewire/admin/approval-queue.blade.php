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
                            @foreach($pendingItems as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <!-- AC1: Type Column with Clear Differentiation -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $item['type'] === 'payment' 
                                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' 
                                                : 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' }}">
                                            @if($item['type'] === 'payment')
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                            {{ $item['type_label'] }}
                                        </span>
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
                                        <div class="text-sm text-gray-900 dark:text-gray-100">
                                            {{ __('ID: :id', ['id' => $item['registration']->id]) }}
                                        </div>
                                        @if($item['amount'])
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ __('Amount: R$ :amount', ['amount' => number_format($item['amount'], 2, ',', '.')]) }}
                                            </div>
                                        @endif
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

                                            <!-- AC1: Quick Reject Button (Async) -->
                                            <button wire:click="quickReject('{{ $item['type'] }}', {{ $item['id'] }})"
                                                    wire:confirm="{{ __('Are you sure you want to reject this proof?') }}"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                                    title="{{ __('Quick Reject') }}">
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
</div>