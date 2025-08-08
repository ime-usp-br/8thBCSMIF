<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        {{ __('Pending Approvals') }}
                    </dt>
                    <dd class="text-2xl font-semibold text-gray-900">
                        {{ number_format($data['total'] ?? 0) }}
                    </dd>
                </dl>
            </div>
        </div>

        {{-- Breakdown --}}
        <div class="mt-5 space-y-3">
            {{-- Payment Proofs --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-orange-400 rounded-full mr-2"></div>
                    <span class="text-sm font-medium text-gray-700">
                        {{ __('Payment Proofs') }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-900 font-medium">
                        {{ number_format($data['payment_proofs'] ?? 0) }}
                    </span>
                    @if(($data['payment_proofs'] ?? 0) > 0)
                        <button wire:click="goToPaymentApprovals" 
                                class="text-xs bg-orange-100 hover:bg-orange-200 text-orange-800 px-2 py-1 rounded-full transition-colors">
                            {{ __('Review') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Enrollment Proofs --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-purple-400 rounded-full mr-2"></div>
                    <span class="text-sm font-medium text-gray-700">
                        {{ __('Enrollment Proofs') }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-900 font-medium">
                        {{ number_format($data['enrollment_proofs'] ?? 0) }}
                    </span>
                    @if(($data['enrollment_proofs'] ?? 0) > 0)
                        <button wire:click="goToEnrollmentApprovals" 
                                class="text-xs bg-purple-100 hover:bg-purple-200 text-purple-800 px-2 py-1 rounded-full transition-colors">
                            {{ __('Review') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Overall Action --}}
        @if(($data['total'] ?? 0) > 0)
            <div class="mt-5 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.registrations.index') }}" 
                   class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center">
                    {{ __('View All Pending Items') }}
                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        @else
            <div class="mt-5 pt-4 border-t border-gray-200 text-center">
                <span class="text-sm text-green-600 font-medium">
                    ✓ {{ __('All caught up!') }}
                </span>
            </div>
        @endif
    </div>
</div>