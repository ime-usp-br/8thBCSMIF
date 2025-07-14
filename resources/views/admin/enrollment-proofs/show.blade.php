<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Enrollment Proof Details') }} - #{{ $enrollmentProof->id }}
            </h2>
            <div class="mt-2 sm:mt-0">
                @php
                    $statusColors = [
                        'pending_approval' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                    ];
                    $statusLabels = [
                        'pending_approval' => __('Pending Approval'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    ];
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$enrollmentProof->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $statusLabels[$enrollmentProof->status] ?? $enrollmentProof->status }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back to List Button -->
            <div class="mb-6">
                <a href="{{ route('admin.enrollment-proofs.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-usp-blue-pri border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-usp-blue-pri/90 focus:bg-usp-blue-pri/90 active:bg-usp-blue-pri focus:outline-none focus:ring-2 focus:ring-usp-blue-pri focus:ring-offset-2 transition ease-in-out duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('Back to Enrollment Proofs') }}
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Header Section with USP Brand -->
                <div class="bg-gradient-to-r from-usp-blue-pri to-usp-blue-sec px-4 sm:px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-white">
                                {{ $enrollmentProof->registration->full_name }}
                            </h3>
                            <p class="text-usp-blue-sec/80 text-sm">
                                {{ __('Registration ID') }}: #{{ $enrollmentProof->registration->id }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-white font-bold text-lg">
                                {{ __('Enrollment Proof') }}
                            </p>
                            <p class="text-usp-blue-sec/80 text-xs">
                                {{ $enrollmentProof->uploaded_at ? $enrollmentProof->uploaded_at->format('d/m/Y H:i') : __('Not uploaded') }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 sm:p-6 text-gray-900 dark:text-gray-100">
                    <!-- Registration Information -->
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Registration Information') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Student registration details') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Full Name') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $enrollmentProof->registration->full_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Email') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $enrollmentProof->registration->email }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Category') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $enrollmentProof->registration->category ?? __('Not specified') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Events -->
                    @if($enrollmentProof->registration->events->count() > 0)
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Registered Events') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Events the student has registered for') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($enrollmentProof->registration->events as $event)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-usp-blue-sec/20 text-usp-blue-pri">
                                    {{ $event->code }} - {{ $event->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Enrollment Proof Details -->
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Enrollment Proof Details') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Document information and status') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$enrollmentProof->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$enrollmentProof->status] ?? $enrollmentProof->status }}
                                    </span>
                                </p>
                            </div>
                            @if($enrollmentProof->uploaded_at)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Upload Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $enrollmentProof->uploaded_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @endif
                            @if($enrollmentProof->original_filename)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Original Filename') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 break-all">{{ $enrollmentProof->original_filename }}</p>
                            </div>
                            @endif
                        </div>

                        @if($enrollmentProof->approved_at && $enrollmentProof->approvedBy)
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Processed Date') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $enrollmentProof->approved_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Processed By') }}</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $enrollmentProof->approvedBy->name ?? $enrollmentProof->approvedBy->email }}</p>
                            </div>
                        </div>
                        @endif

                        @if($enrollmentProof->rejection_reason)
                        <div class="mt-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Rejection Reason') }}</p>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $enrollmentProof->rejection_reason }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- File Actions -->
                    @if($enrollmentProof->file_path)
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('File Actions') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Download and manage enrollment proof document') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('admin.enrollment-proofs.download', $enrollmentProof) }}" 
                               class="inline-flex items-center px-4 py-2 bg-usp-blue-pri border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-usp-blue-pri/90 focus:bg-usp-blue-pri/90 active:bg-usp-blue-pri focus:outline-none focus:ring-2 focus:ring-usp-blue-pri focus:ring-offset-2 transition ease-in-out duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ __('Download Enrollment Proof') }}
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Approval Actions -->
                    @if($enrollmentProof->status === 'pending_approval')
                    <div class="mb-8">
                        <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Approval Actions') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('Approve or reject this enrollment proof') }}
                            </p>
                        </div>
                        <div x-data="{ 
                            showRejectModal: false,
                            reason: '',
                            isSubmitting: false,
                            approve() {
                                this.isSubmitting = true;
                                fetch(`{{ route('enrollment-proofs.approve', $enrollmentProof) }}`, {
                                    method: 'PATCH',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.message) {
                                        window.location.reload();
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('{{ __('An error occurred. Please try again.') }}');
                                })
                                .finally(() => {
                                    this.isSubmitting = false;
                                });
                            },
                            reject() {
                                if (!this.reason.trim()) {
                                    alert('{{ __('Please provide a rejection reason.') }}');
                                    return;
                                }
                                this.isSubmitting = true;
                                fetch(`{{ route('enrollment-proofs.reject', $enrollmentProof) }}`, {
                                    method: 'PATCH',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ reason: this.reason })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.message) {
                                        window.location.reload();
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('{{ __('An error occurred. Please try again.') }}');
                                })
                                .finally(() => {
                                    this.isSubmitting = false;
                                    this.showRejectModal = false;
                                });
                            }
                        }">
                            <div class="flex flex-wrap gap-3">
                                <button @click="approve()" 
                                        :disabled="isSubmitting"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-show="!isSubmitting">{{ __('Approve') }}</span>
                                    <span x-show="isSubmitting">{{ __('Processing...') }}</span>
                                </button>
                                
                                <button @click="showRejectModal = true" 
                                        :disabled="isSubmitting"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    {{ __('Reject') }}
                                </button>
                            </div>

                            <!-- Reject Modal -->
                            <div x-show="showRejectModal" 
                                 x-transition:enter="ease-out duration-300" 
                                 x-transition:enter-start="opacity-0" 
                                 x-transition:enter-end="opacity-100" 
                                 x-transition:leave="ease-in duration-200" 
                                 x-transition:leave-start="opacity-100" 
                                 x-transition:leave-end="opacity-0"
                                 class="fixed inset-0 z-50 overflow-y-auto" 
                                 style="display: none;">
                                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Reject Enrollment Proof') }}</h3>
                                            <div class="mt-4">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Rejection Reason') }}</label>
                                                <textarea x-model="reason" 
                                                          rows="4" 
                                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri"
                                                          placeholder="{{ __('Please provide a detailed reason for rejection...') }}"></textarea>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                            <button @click="reject()" 
                                                    :disabled="isSubmitting"
                                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span x-show="!isSubmitting">{{ __('Reject') }}</span>
                                                <span x-show="isSubmitting">{{ __('Processing...') }}</span>
                                            </button>
                                            <button @click="showRejectModal = false" 
                                                    :disabled="isSubmitting"
                                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-usp-blue-pri sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                                {{ __('Cancel') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>