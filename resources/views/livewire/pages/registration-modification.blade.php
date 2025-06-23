<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Event;
use App\Models\Registration;
use App\Services\FeeCalculationService;

new #[Layout('layouts.app')] class extends Component {
    
    public ?Registration $registration = null;
    public $availableEvents = [];
    public $selectedEventCodes = [];
    public $feeCalculation = null;
    public $hasPendingPayments = false;
    
    public function mount(): void
    {
        // Ensure user is authenticated and verified
        if (!Auth::check() || !Auth::user()->hasVerifiedEmail()) {
            $this->redirect(route('verification.notice'));
            return;
        }

        // Load the user's registration
        $this->registration = Auth::user()->registration()->with(['payments', 'events'])->first();
        
        if (!$this->registration) {
            $this->redirect(route('register-event'));
            return;
        }

        // Load available events (events not yet selected by the user)
        $selectedEventCodes = $this->registration->events->pluck('code')->toArray();
        $this->availableEvents = Event::whereNotIn('code', $selectedEventCodes)
            ->orderBy('name')
            ->get();

        // Check for pending payments
        $this->hasPendingPayments = $this->registration->payments()
            ->where('status', 'pending_approval')
            ->exists();
        
        // Initialize selected event codes as empty array
        $this->selectedEventCodes = [];
        
        // Initialize fee calculation
        $this->calculateFees();
    }

    public function updatedSelectedEventCodes(): void
    {
        $this->calculateFees();
    }

    public function calculateFees(): void
    {
        if (empty($this->selectedEventCodes)) {
            $this->feeCalculation = null;
            return;
        }

        $feeCalculationService = app(FeeCalculationService::class);
        
        // Get all event codes (current + new selected)
        $currentEventCodes = $this->registration->events->pluck('code')->toArray();
        $allEventCodes = array_merge($currentEventCodes, $this->selectedEventCodes);
        
        $this->feeCalculation = $feeCalculationService->calculateFees(
            $this->registration->registration_category_snapshot,
            $allEventCodes,
            now(),
            $this->registration->participation_format ?? 'in-person',
            $this->registration
        );
    }

    public function with(): array
    {
        return [
            'registration' => $this->registration,
            'availableEvents' => $this->availableEvents,
            'feeCalculation' => $this->feeCalculation,
            'hasPendingPayments' => $this->hasPendingPayments,
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <h2 class="text-2xl font-bold mb-4 sm:mb-0">{{ __('Modify Registration') }}</h2>
                    <a href="{{ route('registrations.my') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                       wire:navigate>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        {{ __('Back to My Registration') }}
                    </a>
                </div>

                @if($registration)
                    {{-- Warning about pending payments --}}
                    @if($hasPendingPayments)
                        <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                        {{ __('Payment Under Review') }}
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                                        <p>{{ __('You have payments that are currently under administrative review. You can still add new events, but please note that payment approval may take additional time.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Current Registration Overview --}}
                    <div class="mb-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h3 class="text-lg font-semibold mb-3">{{ __('Current Registration') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Events') }}:</p>
                                <div class="space-y-1">
                                    @foreach($registration->events as $event)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            {{ $event->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Total Fee') }}:</p>
                                <p class="text-lg font-semibold">R$ {{ number_format($registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Available Events Selection --}}
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">{{ __('Add New Events') }}</h3>
                        
                        @if($availableEvents->count() > 0)
                            <div class="space-y-4">
                                @foreach($availableEvents as $event)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex items-center h-5">
                                                <input 
                                                    id="event_{{ $event->code }}" 
                                                    type="checkbox" 
                                                    value="{{ $event->code }}"
                                                    wire:model.live="selectedEventCodes"
                                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                                >
                                            </div>
                                            <div class="flex-1">
                                                <label for="event_{{ $event->code }}" class="block text-sm font-medium text-gray-900 dark:text-gray-100 cursor-pointer">
                                                    {{ $event->name }}
                                                </label>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-300">
                                                        {{ $event->code }}
                                                    </span>
                                                </p>
                                                @if($event->description)
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                        {{ Str::limit($event->description, 200) }}
                                                    </p>
                                                @endif
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                    {{ __('From') }}: {{ $event->start_date->format('d/m/Y') }} 
                                                    {{ __('to') }}: {{ $event->end_date->format('d/m/Y') }}
                                                    | {{ __('Location') }}: {{ $event->location }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="mb-4">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    {{ __('All Events Selected') }}
                                </h3>
                                <p class="text-gray-500 dark:text-gray-400">
                                    {{ __('You are already registered for all available events.') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Financial Summary --}}
                    @if($feeCalculation && count($selectedEventCodes) > 0)
                        <div class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                            <h3 class="text-lg font-semibold mb-4 text-blue-900 dark:text-blue-100">{{ __('Financial Summary') }}</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Original Value') }}</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        R$ {{ number_format($registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Already Paid') }}</p>
                                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                                        R$ {{ number_format($feeCalculation['total_paid'] ?? 0, 2, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Cost of New Items') }}</p>
                                    <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                        R$ {{ number_format(($feeCalculation['new_total_fee'] ?? 0) - $registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total to Pay Now') }}</p>
                                    <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                                        R$ {{ number_format($feeCalculation['amount_due'] ?? 0, 2, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Confirm Changes Button --}}
                    @if(count($selectedEventCodes) > 0)
                        <div class="flex justify-end">
                            <form action="{{ route('registration.modify', $registration) }}" method="POST">
                                @csrf
                                @foreach($selectedEventCodes as $eventCode)
                                    <input type="hidden" name="selected_event_codes[]" value="{{ $eventCode }}">
                                @endforeach
                                
                                <button 
                                    type="submit"
                                    class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ __('Confirm Changes') }}
                                </button>
                            </form>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <div class="mb-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            {{ __('No Registration Found') }}
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            {{ __('You need to register for an event first before you can modify your registration.') }}
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