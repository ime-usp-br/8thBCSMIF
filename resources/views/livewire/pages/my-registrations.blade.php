<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    
    public function mount(): void
    {
        // Ensure user is authenticated and verified
        if (!Auth::check() || !Auth::user()->hasVerifiedEmail()) {
            $this->redirect(route('verification.notice'));
            return;
        }
    }

    public function with(): array
    {
        return [
            'registrations' => Auth::user()->registrations()->with('events')->latest()->get(),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-2xl font-bold mb-6">{{ __('My Registrations') }}</h2>
                
                @if($registrations->count() > 0)
                    <div class="space-y-4">
                        @foreach($registrations as $registration)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-semibold mb-2">
                                            {{ __('Registration') }} #{{ $registration->id }}
                                        </h3>
                                        <p class="text-gray-600 dark:text-gray-400 mb-2">
                                            <strong>{{ __('Events') }}:</strong>
                                            {{ $registration->events->pluck('name')->join(', ') }}
                                        </p>
                                        <p class="text-gray-600 dark:text-gray-400 mb-2">
                                            <strong>{{ __('Total Fee') }}:</strong>
                                            R$ {{ number_format($registration->calculated_fee, 2, ',', '.') }}
                                        </p>
                                        <p class="text-gray-600 dark:text-gray-400">
                                            <strong>{{ __('Payment Status') }}:</strong>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($registration->payment_status === 'pending_payment')
                                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                                @elseif($registration->payment_status === 'pending_br_proof_approval')
                                                    bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                                @elseif($registration->payment_status === 'approved')
                                                    bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                                @else
                                                    bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                                @endif
                                            ">
                                                {{ __(ucfirst(str_replace(['_', '-'], ' ', $registration->payment_status))) }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="mb-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            {{ __('No registrations found') }}
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            {{ __('You have not registered for any events yet.') }}
                        </p>
                        <a href="{{ route('register-event') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                           wire:navigate>
                            {{ __('Register for Event') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>