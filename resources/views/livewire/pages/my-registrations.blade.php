<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    
    public ?int $selectedRegistrationId = null;
    
    public function mount(): void
    {
        // Ensure user is authenticated and verified
        if (!Auth::check() || !Auth::user()->hasVerifiedEmail()) {
            $this->redirect(route('verification.notice'));
            return;
        }
    }

    public function viewRegistration(int $registrationId): void
    {
        // Toggle selection - if same registration is clicked, deselect it
        $this->selectedRegistrationId = $this->selectedRegistrationId === $registrationId ? null : $registrationId;
    }

    public function with(): array
    {
        $registrations = Auth::user()->registrations()->with('events')->latest()->get();
        
        $selectedRegistration = null;
        if ($this->selectedRegistrationId) {
            $selectedRegistration = $registrations->firstWhere('id', $this->selectedRegistrationId);
        }
        
        return [
            'registrations' => $registrations,
            'selectedRegistration' => $selectedRegistration,
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
                                    <div class="flex-1">
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
                                    <div class="ml-4">
                                        <button 
                                            wire:click="viewRegistration({{ $registration->id }})"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150"
                                        >
                                            @if($selectedRegistrationId === $registration->id)
                                                {{ __('Hide Details') }}
                                            @else
                                                {{ __('View Details') }}
                                            @endif
                                        </button>
                                    </div>
                                </div>
                                
                                @if($selectedRegistrationId === $registration->id && $selectedRegistration)
                                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                        <h4 class="text-lg font-medium mb-4">{{ __('Registration Details') }}</h4>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('Personal Information') }}</h5>
                                                <div class="space-y-2 text-sm">
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400">{{ __('Full Name') }}:</span>
                                                        <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $selectedRegistration->full_name }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400">{{ __('Email') }}:</span>
                                                        <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $selectedRegistration->email }}</span>
                                                    </div>
                                                    @if($selectedRegistration->nationality)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400">{{ __('Nationality') }}:</span>
                                                        <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $selectedRegistration->nationality }}</span>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->document_country_origin)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400">{{ __('Document Country') }}:</span>
                                                        <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $selectedRegistration->document_country_origin }}</span>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('Events & Pricing') }}</h5>
                                                <div class="space-y-3">
                                                    @foreach($selectedRegistration->events as $event)
                                                    <div class="border border-gray-200 dark:border-gray-600 rounded p-3">
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $event->name }}</div>
                                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            {{ __('Price at Registration') }}: R$ {{ number_format($event->pivot->price_at_registration, 2, ',', '.') }}
                                                        </div>
                                                        @if($event->description)
                                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                            {{ Str::limit($event->description, 100) }}
                                                        </div>
                                                        @endif
                                                    </div>
                                                    @endforeach
                                                    
                                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                                            {{ __('Total Fee') }}: R$ {{ number_format($selectedRegistration->calculated_fee, 2, ',', '.') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
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