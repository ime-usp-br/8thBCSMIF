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
            
            <!-- Enhanced Filters Section -->
            <div class="mb-8 bg-gray-50 dark:bg-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600" 
                 x-data="{ showAdvanced: false }">
                
                <!-- Filter Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        <svg class="w-5 h-5 text-usp-blue-pri mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z" />
                        </svg>
                        {{ __('Filter Registrations') }}
                    </h3>
                    <button @click="showAdvanced = !showAdvanced" 
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-usp-blue-pri hover:text-usp-blue-sec dark:text-usp-blue-sec dark:hover:text-usp-blue-pri transition-colors duration-200">
                        <span x-text="showAdvanced ? '{{ __('Hide Advanced') }}' : '{{ __('Show Advanced') }}'"></span>
                        <svg x-show="!showAdvanced" class="w-4 h-4 ml-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        <svg x-show="showAdvanced" class="w-4 h-4 ml-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                    </button>
                </div>

                <!-- Quick Search -->
                <div class="mb-4">
                    <x-enhanced-input 
                        type="text"
                        name="search"
                        :label="__('Quick Search')"
                        :placeholder="__('Search by name, email, or registration ID...')"
                        wire:model.live.debounce.300ms="search"
                        icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>'
                    />
                </div>

                <!-- Basic Filters Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Event Filter -->
                    <x-enhanced-input 
                        type="select"
                        name="filterEventCode"
                        :label="__('Event')"
                        wire:model.live="filterEventCode"
                        :options="[
                            '' => __('All Events'),
                            'BCSMIF2025' => __('8th BCSMIF'),
                            'RAA2025' => __('RAA2025'),
                            'WDA2025' => __('WDA2025')
                        ]"
                    />
                    
                    <!-- Registration Status Filter -->
                    <x-enhanced-input 
                        type="select"
                        name="filterPaymentStatus"
                        :label="__('Registration Status')"
                        wire:model.live="filterPaymentStatus"
                        :options="[
                            '' => __('All Statuses'),
                            'pending' => __('Pending'),
                            'pending_approval' => __('Pending Approval'),
                            'approved' => __('Approved'),
                            'rejected' => __('Rejected')
                        ]"
                    />

                    <!-- Student Category Filter -->
                    <x-enhanced-input 
                        type="select"
                        name="filterStudentCategory"
                        :label="__('Category')"
                        wire:model.live="filterStudentCategory"
                        :options="[
                            '' => __('All Categories'),
                            'student' => __('Students Only'),
                            'undergrad_student' => __('Undergraduate'),
                            'grad_student' => __('Graduate'),
                            'professor_abe' => __('Professor (ABE)'),
                            'professor_non_abe' => __('Professor (Non-ABE)'),
                            'professional_foreign' => __('Professional/Foreign'),
                            'accompanying_person' => __('Accompanying Person')
                        ]"
                    />
                    
                    <!-- Enrollment Proof Filter -->
                    <x-enhanced-input 
                        type="select"
                        name="filterEnrollmentProofStatus"
                        :label="__('Document Status')"
                        wire:model.live="filterEnrollmentProofStatus"
                        :options="[
                            '' => __('All Statuses'),
                            'none' => __('No Document'),
                            'pending_approval' => __('Pending Review'),
                            'approved' => __('Approved'),
                            'rejected' => __('Rejected')
                        ]"
                    />
                </div>

                <!-- Bulk Actions -->
                <div class="border-t border-gray-200 dark:border-gray-600 pt-4 mt-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('Bulk Actions') }}</h4>
                    
                    <div class="flex flex-wrap items-center gap-3">
                        <button wire:click="exportSelected" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ __('Export Filtered') }}
                        </button>

                        <button wire:click="markDocumentsReviewed" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Batch Review Documents') }}
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div x-show="showAdvanced" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 max-h-0"
                     x-transition:enter-end="opacity-100 max-h-full"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 max-h-full"
                     x-transition:leave-end="opacity-0 max-h-0"
                     class="border-t border-gray-200 dark:border-gray-600 pt-4">
                    
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">{{ __('Advanced Filters') }}</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                        <!-- Date Range -->
                        <x-enhanced-input 
                            type="date"
                            name="filterDateFrom"
                            :label="__('Registration From')"
                            wire:model.live="filterDateFrom"
                        />
                        
                        <x-enhanced-input 
                            type="date"
                            name="filterDateTo"
                            :label="__('Registration To')"
                            wire:model.live="filterDateTo"
                        />

                        <!-- Fee Range -->
                        <x-enhanced-input 
                            type="number"
                            name="filterMinFee"
                            :label="__('Min Fee (R$)')"
                            wire:model.live.debounce.500ms="filterMinFee"
                            placeholder="0.00"
                            step="0.01"
                        />

                        <x-enhanced-input 
                            type="number"
                            name="filterMaxFee"
                            :label="__('Max Fee (R$)')"
                            wire:model.live.debounce.500ms="filterMaxFee"
                            placeholder="999999.99"
                            step="0.01"
                        />

                        <!-- Country Filter -->
                        <x-enhanced-input 
                            type="select"
                            name="filterCountry"
                            :label="__('Country')"
                            wire:model.live="filterCountry"
                            :options="[
                                '' => __('All Countries'),
                                'Brazil' => __('Brazil'),
                                'OTHER' => __('International')
                            ]"
                        />

                        <!-- Transport Filter -->
                        <x-enhanced-input 
                            type="select"
                            name="filterTransport"
                            :label="__('Transport Needs')"
                            wire:model.live="filterTransport"
                            :options="[
                                '' => __('All Transport'),
                                'gru' => __('Needs GRU Transport'),
                                'usp' => __('Needs USP Transport'),
                                'both' => __('Needs Both'),
                                'none' => __('No Transport')
                            ]"
                        />
                    </div>

                </div>

                <!-- Active Filters Summary -->
                <div class="flex flex-wrap items-center gap-2 mt-4" wire:ignore.self>
                    @if($search)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-usp-blue-pri/10 text-usp-blue-pri border border-usp-blue-pri/20">
                            {{ __('Search') }}: "{{ $search }}"
                            <button wire:click="$set('search', '')" class="ml-2 hover:text-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    @endif

                    <!-- Clear All Filters -->
                    @if($search || $filterEventCode || $filterPaymentStatus || $filterEnrollmentProofStatus)
                        <button wire:click="clearAllFilters" 
                                class="inline-flex items-center px-3 py-1 text-sm text-gray-600 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            {{ __('Clear All') }}
                        </button>
                    @endif
                </div>

                <!-- Results Count -->
                @if($registrations->count() > 0)
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Showing :count of :total registrations', ['count' => $registrations->count(), 'total' => $registrations->total()]) }}
                    </div>
                @endif
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
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                            'pending_approval' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                            'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            'paid_br' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'paid_int' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        ];
                                        $statusLabels = [
                                            'pending' => __('Pending'),
                                            'pending_approval' => __('Pending Approval'),
                                            'approved' => __('Approved'),
                                            'rejected' => __('Rejected'),
                                            'paid_br' => __('Paid (BR)'),
                                            'paid_int' => __('Paid (International)'),
                                            'cancelled' => __('Cancelled'),
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$registration->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$registration->status] ?? $registration->status }}
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
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 ml-1">R$ {{ number_format($registration->calculateCorrectTotalFee(), 2, ',', '.') }}</span>
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

                                <!-- Enrollment Proof Status -->
                                @if($registration->registration_category_snapshot === 'undergraduate_student')
                                <div class="mb-4">
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-1">{{ __('Enrollment Proof') }}:</span>
                                    @if($registration->enrollmentProof)
                                        @php
                                            $proofStatusColors = [
                                                'pending_approval' => 'bg-yellow-100 text-yellow-800',
                                                'approved' => 'bg-green-100 text-green-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                            ];
                                            $proofStatusLabels = [
                                                'pending_approval' => __('Pending Approval'),
                                                'approved' => __('Approved'),
                                                'rejected' => __('Rejected'),
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $proofStatusColors[$registration->enrollmentProof->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $proofStatusLabels[$registration->enrollmentProof->status] ?? $registration->enrollmentProof->status }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-sm">{{ __('No proof uploaded') }}</span>
                                    @endif
                                </div>
                                @endif

                                <!-- Payment Statuses -->
                                <div class="mb-4">
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-1">{{ __('Payment Statuses') }}:</span>
                                    <div class="flex flex-wrap gap-1">
                                        @php
                                            $paymentStatusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'pending_approval' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                'refunded' => 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200',
                                            ];
                                            $payments = $registration->payments;
                                            $limit = 3;
                                            $totalPayments = $payments->count();
                                        @endphp
                                        @forelse($payments->take($limit) as $payment)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $paymentStatusColors[$payment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ __($payment->status) }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400 dark:text-gray-500 text-sm">{{ __('No payments') }}</span>
                                        @endforelse

                                        @if($totalPayments > $limit)
                                            @php
                                                $remainingCount = $totalPayments - $limit;
                                                $tooltipText = $payments->slice($limit)->pluck('status')->map(fn($status) => __($status))->implode(', ');
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200"
                                                  title="{{ __('Remaining') }}: {{ $tooltipText }}">
                                                +{{ $remainingCount }}
                                            </span>
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
                                    {{ __('Registration Status') }}
                                </th>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Payment Statuses') }}
                                </th>
                                <th scope="col" class="px-4 xl:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Student Documents') }}
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
                                    <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
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
                                    <td class="px-4 xl:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        R$ {{ number_format($registration->calculateCorrectTotalFee(), 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 whitespace-nowrap">
                                        @php
                                            $regStatusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                'pending_approval' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                'paid_br' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'paid_int' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            ];
                                            $regStatusLabels = [
                                                'pending' => __('Pending'),
                                                'pending_approval' => __('Pending Approval'),
                                                'approved' => __('Approved'),
                                                'rejected' => __('Rejected'),
                                                'paid_br' => __('Paid (BR)'),
                                                'paid_int' => __('Paid (International)'),
                                                'cancelled' => __('Cancelled'),
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $regStatusColors[$registration->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $regStatusLabels[$registration->status] ?? $registration->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-wrap items-center gap-1">
                                            @php
                                                $paymentStatusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                    'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                    'pending_approval' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                    'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                    'refunded' => 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200',
                                                ];
                                                $payments = $registration->payments;
                                                $limit = 3;
                                                $totalPayments = $payments->count();
                                            @endphp
                                            @forelse($payments->take($limit) as $payment)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $paymentStatusColors[$payment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ __($payment->status) }}
                                                </span>
                                            @empty
                                                <span class="text-gray-400 dark:text-gray-500 text-xs">{{ __('No payments') }}</span>
                                            @endforelse

                                            @if($totalPayments > $limit)
                                                @php
                                                    $remainingCount = $totalPayments - $limit;
                                                    $tooltipText = $payments->slice($limit)->pluck('status')->map(fn($status) => __($status))->implode(', ');
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200 cursor-help"
                                                      title="{{ __('Remaining') }}: {{ $tooltipText }}">
                                                    +{{ $remainingCount }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 xl:px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        @if(in_array($registration->registration_category_snapshot, ['undergrad_student', 'grad_student']))
                                            @if($registration->enrollmentProof)
                                                @php
                                                    $proofStatusColors = [
                                                        'pending_approval' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                    ];
                                                    $proofStatusLabels = [
                                                        'pending_approval' => __('Pending'),
                                                        'approved' => __('Approved'),
                                                        'rejected' => __('Rejected'),
                                                    ];
                                                @endphp
                                                <div class="flex items-center justify-between">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $proofStatusColors[$registration->enrollmentProof->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                        {{ $proofStatusLabels[$registration->enrollmentProof->status] ?? $registration->enrollmentProof->status }}
                                                    </span>
                                                    
                                                    @if($registration->enrollmentProof->status === 'pending_approval')
                                                        <div class="flex items-center space-x-1 ml-2" x-data="{ showActions: false }">
                                                            <button @click="showActions = !showActions" 
                                                                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                                </svg>
                                                            </button>
                                                            
                                                            <div x-show="showActions" 
                                                                 x-transition:enter="transition ease-out duration-100"
                                                                 x-transition:enter-start="transform opacity-0 scale-95"
                                                                 x-transition:enter-end="transform opacity-100 scale-100"
                                                                 x-transition:leave="transition ease-in duration-75"
                                                                 x-transition:leave-start="transform opacity-100 scale-100"
                                                                 x-transition:leave-end="transform opacity-0 scale-95"
                                                                 @click.away="showActions = false"
                                                                 class="absolute z-10 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none right-0"
                                                                 style="display: none;">
                                                                <div class="py-1">
                                                                    <button wire:click="approveDocument({{ $registration->enrollmentProof->id }})" 
                                                                            class="group flex items-center px-4 py-2 text-sm text-green-700 hover:bg-green-50 dark:text-green-300 dark:hover:bg-green-900/20 w-full text-left">
                                                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                        </svg>
                                                                        {{ __('Approve') }}
                                                                    </button>
                                                                    <button wire:click="rejectDocument({{ $registration->enrollmentProof->id }})" 
                                                                            class="group flex items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/20 w-full text-left">
                                                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                        </svg>
                                                                        {{ __('Reject') }}
                                                                    </button>
                                                                    <a href="{{ route('enrollment-proofs.download', $registration) }}" 
                                                                       target="_blank"
                                                                       class="group flex items-center px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-900/20 w-full text-left">
                                                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                        </svg>
                                                                        {{ __('View Document') }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <a href="{{ route('enrollment-proofs.download', $registration) }}" 
                                                           target="_blank"
                                                           class="p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 rounded-full hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </a>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="flex items-center">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ __('Missing') }}
                                                    </span>
                                                    <svg class="w-4 h-4 text-orange-500 dark:text-orange-400 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500 text-xs">{{ __('N/A') }}</span>
                                        @endif
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

    <!-- CSV Export Modal -->
    <div x-data="{ open: @entangle('showExportModal') }" 
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
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Export Registrations') }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    {{ __('Select which columns to include in the CSV export. Only filtered registrations will be exported.') }}
                                </p>

                                <!-- Global Selection Controls -->
                                <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-600">
                                    <div class="flex flex-wrap gap-3">
                                        <button wire:click="selectAllColumns"
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-usp-blue-pri">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ __('Select All') }}
                                        </button>
                                        <button wire:click="deselectAllColumns"
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-usp-blue-pri">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            {{ __('Deselect All') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Column Groups -->
                                <div class="max-h-96 overflow-y-auto space-y-4">
                                    @foreach($columnGroups as $groupKey => $groupLabel)
                                        @if(isset($availableColumns[$groupKey]))
                                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                                <!-- Group Header -->
                                                <div class="flex items-center justify-between mb-3">
                                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                                        @switch($groupKey)
                                                            @case('basic')
                                                                <svg class="w-4 h-4 mr-2 text-usp-blue-pri" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                </svg>
                                                                @break
                                                            @case('personal')
                                                                <svg class="w-4 h-4 mr-2 text-usp-blue-pri" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                                </svg>
                                                                @break
                                                            @case('contact')
                                                                <svg class="w-4 h-4 mr-2 text-usp-blue-pri" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                                </svg>
                                                                @break
                                                            @case('professional')
                                                                <svg class="w-4 h-4 mr-2 text-usp-blue-pri" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 00-2 2H8a2 2 0 00-2-2V4"/>
                                                                </svg>
                                                                @break
                                                            @case('conference')
                                                                <svg class="w-4 h-4 mr-2 text-usp-blue-pri" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                                </svg>
                                                                @break
                                                            @case('administrative')
                                                                <svg class="w-4 h-4 mr-2 text-usp-blue-pri" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                                </svg>
                                                                @break
                                                        @endswitch
                                                        {{ $groupLabel }}
                                                    </h4>
                                                    <button wire:click="toggleGroupColumns('{{ $groupKey }}')"
                                                            type="button"
                                                            class="text-sm text-usp-blue-pri hover:text-usp-blue-sec dark:text-usp-blue-sec dark:hover:text-usp-blue-pri transition-colors duration-200">
                                                        @if($this->isGroupFullySelected($groupKey))
                                                            {{ __('Deselect All') }}
                                                        @else
                                                            {{ __('Select All') }}
                                                        @endif
                                                    </button>
                                                </div>

                                                <!-- Column Checkboxes -->
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                    @foreach($availableColumns[$groupKey] as $columnKey => $columnLabel)
                                                        <label class="flex items-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                            <input type="checkbox" 
                                                                   wire:model="selectedColumns" 
                                                                   value="{{ $columnKey }}"
                                                                   class="h-4 w-4 text-usp-blue-pri focus:ring-usp-blue-pri border-gray-300 dark:border-gray-600 rounded">
                                                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300 flex-1">{{ $columnLabel }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                @error('selectedColumns')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="exportCsv"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        <span wire:loading.remove>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('Export CSV') }}
                        </span>
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('Exporting...') }}
                        </span>
                    </button>
                    <button wire:click="closeExportModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-usp-blue-pri sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for CSV Export -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('export-csv', (data) => {
                // Create form and submit to export endpoint
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.registrations.export-csv') }}';
                form.style.display = 'none';

                // CSRF Token
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                form.appendChild(csrfToken);

                // Columns
                data[0].columns.forEach(column => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'columns[]';
                    input.value = column;
                    form.appendChild(input);
                });

                // Filters
                Object.keys(data[0].filters).forEach(filterKey => {
                    if (data[0].filters[filterKey]) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `filters[${filterKey}]`;
                        input.value = data[0].filters[filterKey];
                        form.appendChild(input);
                    }
                });

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        });
    </script>
</div>
