<div class="space-y-6">
    {{-- Dashboard Header --}}
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Admin Dashboard') }}</h1>
        <button wire:click="refreshMetrics" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>{{ __('Refresh Metrics') }}</span>
        </button>
    </div>

    {{-- Success Message --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Statistics Widgets Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
        
        {{-- Total Registrations Widget --}}
        <div class="lg:col-span-1">
            <livewire:admin.widgets.total-registrations />
        </div>

        {{-- Pending Approvals Widget --}}
        <div class="lg:col-span-1">
            <livewire:admin.widgets.pending-approvals />
        </div>

        {{-- Revenue Widget --}}
        <div class="lg:col-span-1">
            <livewire:admin.widgets.revenue />
        </div>

        {{-- Transport Needs Widget --}}
        <div class="lg:col-span-1">
            <livewire:admin.widgets.transport-needs />
        </div>

        {{-- Registrations by Category Widget (takes more space for chart) --}}
        <div class="lg:col-span-1 xl:col-span-1">
            <livewire:admin.widgets.registrations-by-category />
        </div>

    </div>

    {{-- Additional Dashboard Content can be added here --}}
    <div class="mt-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ __('Quick Actions') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('admin.registrations.index') }}" 
                       class="bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg p-4 text-center transition-colors">
                        <div class="text-blue-600 font-medium">{{ __('View Registrations') }}</div>
                        <div class="text-blue-500 text-sm">{{ __('Manage all registrations') }}</div>
                    </a>
                    <a href="{{ route('admin.reports.index') }}" 
                       class="bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-4 text-center transition-colors">
                        <div class="text-green-600 font-medium">{{ __('Generate Reports') }}</div>
                        <div class="text-green-500 text-sm">{{ __('Export data and analytics') }}</div>
                    </a>
                    <button wire:click="refreshMetrics"
                            class="bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg p-4 text-center transition-colors">
                        <div class="text-gray-600 font-medium">{{ __('Refresh Data') }}</div>
                        <div class="text-gray-500 text-sm">{{ __('Update all metrics') }}</div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>