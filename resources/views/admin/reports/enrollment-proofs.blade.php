<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Enrollment Proofs Report') }}
            </h2>
            <a href="{{ route('admin.reports.index') }}" 
               class="text-usp-blue-pri hover:text-usp-blue-pri/80 text-sm font-medium">
                ← {{ __('Back to Reports') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Statistics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['total']) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Proofs') }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending']) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Pending') }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ number_format($stats['approved']) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Approved') }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600">{{ number_format($stats['rejected']) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Rejected') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        {{ __('Filters') }}
                    </h3>
                    <form method="GET" action="{{ route('admin.reports.enrollment-proofs') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Status') }}
                            </label>
                            <select name="status" id="status" 
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri">
                                <option value="">{{ __('All Statuses') }}</option>
                                <option value="pending_approval" {{ $filterStatus === 'pending_approval' ? 'selected' : '' }}>{{ __('Pending Approval') }}</option>
                                <option value="approved" {{ $filterStatus === 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
                                <option value="rejected" {{ $filterStatus === 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('From Date') }}
                            </label>
                            <input type="date" name="date_from" id="date_from" value="{{ $filterDateFrom }}"
                                   class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri">
                        </div>
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('To Date') }}
                            </label>
                            <input type="date" name="date_to" id="date_to" value="{{ $filterDateTo }}"
                                   class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-usp-blue-pri focus:ring-usp-blue-pri">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-usp-blue-pri border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-usp-blue-pri/90 focus:bg-usp-blue-pri/90 active:bg-usp-blue-pri/80 focus:outline-none focus:ring-2 focus:ring-usp-blue-pri focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Filter') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Enrollment Proofs Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="border-l-4 border-usp-blue-pri pl-4 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('Enrollment Proofs Details') }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ __('Detailed view of all enrollment proof submissions') }}
                        </p>
                    </div>

                    @if($enrollmentProofs->count() > 0)
                        <!-- Desktop Table -->
                        <div class="hidden lg:block overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('ID') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('Student') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('Email') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('Events') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('Status') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('Upload Date') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($enrollmentProofs as $proof)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                #{{ $proof->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $proof->registration->full_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $proof->registration->email }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                <div class="flex flex-wrap gap-1">
                                                    @forelse($proof->registration->events as $event)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-usp-blue-sec/20 text-usp-blue-pri">
                                                            {{ $event->code }}
                                                        </span>
                                                    @empty
                                                        <span class="text-gray-400">{{ __('No events') }}</span>
                                                    @endforelse
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
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
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$proof->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ $statusLabels[$proof->status] ?? $proof->status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $proof->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('admin.registrations.show', $proof->registration) }}" 
                                                   class="text-usp-blue-pri hover:text-usp-blue-pri/80 mr-3">
                                                    {{ __('View') }}
                                                </a>
                                                @if($proof->file_path)
                                                <a href="{{ route('admin.registrations.download-enrollment-proof', $proof->registration) }}" 
                                                   class="text-gray-600 hover:text-gray-900">
                                                    {{ __('Download') }}
                                                </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="lg:hidden space-y-4">
                            @foreach($enrollmentProofs as $proof)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">#{{ $proof->id }}</span>
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
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$proof->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabels[$proof->status] ?? $proof->status }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-900 dark:text-gray-100 mb-1">
                                        {{ $proof->registration->full_name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                        {{ $proof->registration->email }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                        {{ $proof->created_at->format('d/m/Y H:i') }}
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.registrations.show', $proof->registration) }}" 
                                           class="text-xs text-usp-blue-pri hover:text-usp-blue-pri/80">
                                            {{ __('View') }}
                                        </a>
                                        @if($proof->file_path)
                                        <a href="{{ route('admin.registrations.download-enrollment-proof', $proof->registration) }}" 
                                           class="text-xs text-gray-600 hover:text-gray-900">
                                            {{ __('Download') }}
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $enrollmentProofs->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                {{ __('No enrollment proofs found matching your criteria.') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>