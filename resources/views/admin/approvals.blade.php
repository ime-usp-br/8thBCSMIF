<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Approval Queue') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- AC1: Breadcrumb Navigation --}}
            <x-admin.breadcrumbs :breadcrumbs="[
                ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('Approval Queue'), 'url' => '#']
            ]" />
            
            {{-- AC1: ApprovalQueue Livewire Component --}}
            <livewire:admin.approval-queue />
        </div>
    </div>
</x-app-layout>