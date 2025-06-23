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
        // Load the single registration with payments and events eager-loaded
        $registration = Auth::user()->registration()->with(['payments', 'events'])->first();
        
        $selectedRegistration = null;
        if ($this->selectedRegistrationId && $registration && $this->selectedRegistrationId === $registration->id) {
            $selectedRegistration = $registration;
        }
        
        return [
            'registration' => $registration,
            'selectedRegistration' => $selectedRegistration,
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <h2 class="text-2xl font-bold mb-4 sm:mb-0">{{ __('My Registration') }}</h2>
                    @if($registration)
                        <a href="#" 
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                           wire:navigate>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            {{ __('Add Events') }}
                        </a>
                    @endif
                </div>
                
                @if($registration)
                    <div class="space-y-6">
                        {{-- Registration Overview --}}
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
                                        R$ {{ number_format($registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}
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
                        </div>

                        {{-- Payments Timeline --}}
                        @if($registration->payments->count() > 0)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h4 class="text-lg font-semibold mb-4 border-l-4 border-blue-500 pl-3">{{ __('Payment History') }}</h4>
                            <div class="space-y-4">
                                @foreach($registration->payments->sortByDesc('created_at') as $payment)
                                <div class="flex items-start space-x-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center
                                            @if($payment->status === 'pending')
                                                bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400
                                            @elseif($payment->status === 'approved')
                                                bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400
                                            @elseif($payment->status === 'rejected')
                                                bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400
                                            @else
                                                bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400
                                            @endif
                                        ">
                                            @if($payment->status === 'pending')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                </svg>
                                            @elseif($payment->status === 'approved')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            @elseif($payment->status === 'rejected')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ __('Payment') }} #{{ $payment->id }}
                                                </p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ __('Amount') }}: R$ {{ number_format($payment->amount, 2, ',', '.') }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($payment->status === 'pending')
                                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                                    @elseif($payment->status === 'approved')
                                                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                                    @elseif($payment->status === 'rejected')
                                                        bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                                    @else
                                                        bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                                    @endif
                                                ">
                                                    {{ __(ucfirst($payment->status)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $payment->created_at->format('d/m/Y H:i') }}
                                            @if($payment->payment_date)
                                                • {{ __('Paid on') }}: {{ $payment->payment_date->format('d/m/Y') }}
                                            @endif
                                        </p>
                                        @if($payment->notes)
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-2">
                                            {{ $payment->notes }}
                                        </p>
                                        @endif
                                        
                                        {{-- Payment Proof Upload Form - Conditionally displayed for pending payments without proof --}}
                                        @if($payment->status === 'pending' && in_array($registration->document_country_origin, ['Brasil', 'BR']) && !$payment->payment_proof_path)
                                            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                                                <h5 class="font-medium text-yellow-800 dark:text-yellow-300 mb-3">
                                                    {{ __('Payment Proof Upload') }}
                                                </h5>
                                                
                                                {{-- Display success message --}}
                                                @if(session('success'))
                                                    <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                                                        {{ session('success') }}
                                                    </div>
                                                @endif
                                                
                                                {{-- Display validation errors --}}
                                                @if($errors->any())
                                                    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                                                        <ul class="list-disc list-inside">
                                                            @foreach($errors->all() as $error)
                                                                <li>{{ $error }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                                
                                                <form action="{{ route('event-registrations.upload-proof', $registration) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                                                    @csrf
                                                    <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                                    
                                                    <div>
                                                        <label for="payment_proof_{{ $payment->id }}" class="block text-sm font-medium text-yellow-800 dark:text-yellow-300 mb-2">
                                                            {{ __('Payment Proof Document') }}
                                                        </label>
                                                        <input 
                                                            type="file" 
                                                            id="payment_proof_{{ $payment->id }}" 
                                                            name="payment_proof" 
                                                            accept=".jpg,.jpeg,.png,.pdf"
                                                            class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                                   file:mr-4 file:py-2 file:px-4
                                                                   file:rounded-full file:border-0
                                                                   file:text-sm file:font-semibold
                                                                   file:bg-yellow-100 file:text-yellow-800
                                                                   hover:file:bg-yellow-200
                                                                   dark:file:bg-yellow-900 dark:file:text-yellow-300
                                                                   dark:hover:file:bg-yellow-800"
                                                            required
                                                        >
                                                        <p class="mt-1 text-xs text-yellow-700 dark:text-yellow-400">
                                                            {{ __('Accepted formats: JPG, JPEG, PNG, PDF. Maximum size: 10MB.') }}
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="flex justify-end">
                                                        <button 
                                                            type="submit" 
                                                            dusk="upload-payment-proof-button-{{ $payment->id }}"
                                                            class="inline-flex items-center px-3 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                        >
                                                            {{ __('Upload Payment Proof') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @elseif($payment->payment_proof_path)
                                            {{-- Show uploaded proof confirmation --}}
                                            <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                                                <div class="flex items-center">
                                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="text-green-800 dark:text-green-300 font-medium">
                                                        {{ __('Payment proof uploaded successfully') }}
                                                    </span>
                                                </div>
                                                @if($payment->payment_date)
                                                    <p class="text-sm text-green-700 dark:text-green-400 mt-2">
                                                        {{ __('Uploaded on') }}: {{ $payment->payment_date->format('d/m/Y H:i') }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($selectedRegistrationId === $registration->id && $selectedRegistration)
                                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                        <h4 class="text-lg font-medium mb-6">{{ __('Registration Details') }}</h4>
                                        
                                        <div class="space-y-8">
                                            <!-- Personal Information -->
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-4 border-l-4 border-blue-500 pl-3">{{ __('Personal Information') }}</h5>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Full Name') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->full_name }}</div>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Email') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->email }}</div>
                                                    </div>
                                                    @if($selectedRegistration->phone_number)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Phone Number') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->phone_number }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->nationality)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Nationality') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->nationality }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->date_of_birth)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Date of Birth') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->date_of_birth->format('d/m/Y') }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->gender)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Gender') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->gender }}</div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Document Information -->
                                            @if($selectedRegistration->document_country_origin || $selectedRegistration->cpf || $selectedRegistration->rg_number || $selectedRegistration->passport_number)
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-4 border-l-4 border-green-500 pl-3">{{ __('Document Information') }}</h5>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                                    @if($selectedRegistration->document_country_origin)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Document Country') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->document_country_origin }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->cpf)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('CPF') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->cpf }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->rg_number)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('RG Number') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->rg_number }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->passport_number)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Passport Number') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->passport_number }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->passport_expiry_date)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Passport Expiry Date') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->passport_expiry_date->format('d/m/Y') }}</div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif

                                            <!-- Address Information -->
                                            @if($selectedRegistration->address_street || $selectedRegistration->address_city || $selectedRegistration->address_country)
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-4 border-l-4 border-purple-500 pl-3">{{ __('Address') }}</h5>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                                    @if($selectedRegistration->address_street)
                                                    <div class="md:col-span-2">
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Street Address') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->address_street }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->address_city)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('City') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->address_city }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->address_state_province)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('State/Province') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->address_state_province }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->address_country)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Country') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->address_country }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->address_postal_code)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Postal Code') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->address_postal_code }}</div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif

                                            <!-- Professional Information -->
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-4 border-l-4 border-indigo-500 pl-3">{{ __('Professional Information') }}</h5>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                                    @if($selectedRegistration->affiliation)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Affiliation') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->affiliation }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->position)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Position') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->position }}</div>
                                                    </div>
                                                    @endif
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('ABE Member') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">
                                                            {{ $selectedRegistration->is_abe_member ? __('Yes') : __('No') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Event Participation -->
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-4 border-l-4 border-orange-500 pl-3">{{ __('Event Participation') }}</h5>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                                    @if($selectedRegistration->participation_format)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Participation Format') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->participation_format }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->arrival_date)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Arrival Date') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->arrival_date->format('d/m/Y') }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->departure_date)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Departure Date') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->departure_date->format('d/m/Y') }}</div>
                                                    </div>
                                                    @endif
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Transport from GRU Airport') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">
                                                            {{ $selectedRegistration->needs_transport_from_gru ? __('Yes') : __('No') }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Transport from USP') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">
                                                            {{ $selectedRegistration->needs_transport_from_usp ? __('Yes') : __('No') }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Requires Visa Letter') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">
                                                            {{ $selectedRegistration->requires_visa_letter ? __('Yes') : __('No') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Dietary Information -->
                                            @if($selectedRegistration->dietary_restrictions || $selectedRegistration->other_dietary_restrictions)
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-4 border-l-4 border-red-500 pl-3">{{ __('Dietary Information') }}</h5>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                    @if($selectedRegistration->dietary_restrictions)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Dietary Restrictions') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->dietary_restrictions }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->other_dietary_restrictions)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Other Dietary Restrictions') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->other_dietary_restrictions }}</div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif

                                            <!-- Emergency Contact -->
                                            @if($selectedRegistration->emergency_contact_name || $selectedRegistration->emergency_contact_phone)
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-4 border-l-4 border-pink-500 pl-3">{{ __('Emergency Contact') }}</h5>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                                    @if($selectedRegistration->emergency_contact_name)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Contact Name') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->emergency_contact_name }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->emergency_contact_relationship)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Relationship') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->emergency_contact_relationship }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->emergency_contact_phone)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Contact Phone') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->emergency_contact_phone }}</div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif
                                            
                                            <!-- Events & Pricing -->
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-4 border-l-4 border-blue-600 pl-3">{{ __('Events & Pricing') }}</h5>
                                                <div class="space-y-3">
                                                    @foreach($selectedRegistration->events as $event)
                                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                                                        <div class="flex justify-between items-start">
                                                            <div class="flex-1">
                                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $event->name }}</div>
                                                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300">
                                                                        {{ $event->code }}
                                                                    </span>
                                                                </div>
                                                                @if($event->description)
                                                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                                    {{ Str::limit($event->description, 150) }}
                                                                </div>
                                                                @endif
                                                            </div>
                                                            <div class="text-right ml-4">
                                                                <div class="font-bold text-lg text-gray-900 dark:text-gray-100">
                                                                    R$ {{ number_format($event->pivot->price_at_registration, 2, ',', '.') }}
                                                                </div>
                                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Price at registration') }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                    
                                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4">
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Total Registration Fee') }}</span>
                                                            <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                                                R$ {{ number_format($selectedRegistration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Registration Information -->
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-4 border-l-4 border-gray-500 pl-3">{{ __('Registration Information') }}</h5>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Registration Date') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->created_at->format('d/m/Y H:i') }}</div>
                                                    </div>
                                                    @if($selectedRegistration->payment_uploaded_at)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Payment Proof Uploaded At') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->payment_uploaded_at->format('d/m/Y H:i') }}</div>
                                                    </div>
                                                    @endif
                                                    @if($selectedRegistration->registration_category_snapshot)
                                                    <div>
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Registration Category') }}:</span>
                                                        <div class="text-gray-900 dark:text-gray-100">{{ $selectedRegistration->registration_category_snapshot }}</div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        @endif
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