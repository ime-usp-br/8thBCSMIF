<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\User;
use App\Services\AdditionalRegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class AdditionalRegistrationForm extends Component
{
    #[Validate('required|array|min:1')]
    /** @phpstan-ignore-next-line missingType.iterableValue */
    public array $selectedEvents = [];

    /** @var list<mixed> */
    public array $availableEvents = [];

    /** @var list<mixed> */
    public array $userAccessibleEvents = [];

    /** @var list<string> */
    public array $immutableEventCodes = [];

    /** @var list<string> */
    public array $existingEventCodes = [];

    /** @var array<string, mixed> */
    public array $feeCalculation = [];

    public string $message = '';

    public bool $showCalculation = false;

    protected AdditionalRegistrationService $additionalRegistrationService;

    public function boot(AdditionalRegistrationService $additionalRegistrationService): void
    {
        $this->additionalRegistrationService = $additionalRegistrationService;
    }

    public function mount(): void
    {
        $user = Auth::user();

        // Ensure user is authenticated and verified
        if (! $user || ! $user->hasVerifiedEmail()) {
            $this->redirect(route('verification.notice'));

            return;
        }

        // Check if user has existing registration
        if (! $user->registration) {
            session()->flash('error', __('You must have an existing registration to add additional events.'));
            $this->redirect(route('register-event'));

            return;
        }

        $this->loadAvailableEvents();
        $this->loadUserAccessibleEvents();
        $this->loadImmutableEvents();
        $this->loadExistingEvents();
        $this->preSelectExistingEvents();
    }

    public function loadAvailableEvents(): void
    {
        $this->availableEvents = array_values(Event::all()->toArray());
    }

    public function loadUserAccessibleEvents(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->userAccessibleEvents = $this->additionalRegistrationService
                ->getUserAccessibleEvents($user);
        }
    }

    public function loadImmutableEvents(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->immutableEventCodes = $user->getImmutableEventCodes();
        }
    }

    public function loadExistingEvents(): void
    {
        $user = Auth::user();
        if ($user && $user->registration) {
            // Get all events the user is currently registered for (regardless of payment status)
            /** @phpstan-ignore-next-line */
            $this->existingEventCodes = $user->registration->events->pluck('code')->toArray();
        }
    }

    public function preSelectExistingEvents(): void
    {
        // Pre-select ALL existing events (paid and unpaid)
        $this->selectedEvents = $this->existingEventCodes;

        // Force Livewire to recalculate if there are events to pre-select
        if (! empty($this->existingEventCodes)) {
            $this->calculateFees();
        }
    }

    public function updatedSelectedEvents(): void
    {
        // Ensure ALL existing events always remain selected (not just paid ones)
        $missingExistingEvents = array_diff($this->existingEventCodes, $this->selectedEvents);
        if (! empty($missingExistingEvents)) {
            $this->selectedEvents = array_unique(array_merge($this->selectedEvents, $missingExistingEvents));
        }

        $this->showCalculation = false;
        $this->feeCalculation = [];
        $this->message = '';

        if (empty($this->selectedEvents)) {
            return;
        }

        $this->calculateFees();
    }

    public function calculateFees(): void
    {
        if (empty($this->selectedEvents)) {
            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->registration) {
            return;
        }

        $registration = $user->registration;

        /** @var list<string> $eventCodes */
        $eventCodes = array_values($this->selectedEvents);
        $this->feeCalculation = $this->additionalRegistrationService
            ->calculateAdditionalEventsFees(
                $user,
                $eventCodes,
                $registration->registration_category_snapshot,
                $registration->participation_format ?: 'in-person'
            );

        $this->showCalculation = true;
        $this->message = $this->feeCalculation['message'] ?? '';
    }

    public function submit(): void
    {
        $this->validate();

        if (empty($this->selectedEvents)) {
            session()->flash('error', __('Please select at least one event.'));

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->registration) {
            session()->flash('error', __('User or registration not found.'));

            return;
        }

        // Filter out ALL existing events - only process truly new events
        $newEventCodes = array_diff($this->selectedEvents, $this->existingEventCodes);

        if (empty($newEventCodes)) {
            session()->flash('error', __('Please select at least one new event to add.'));

            return;
        }

        // Check if user can register for new events
        /** @var list<string> $eventCodes */
        $eventCodes = array_values($newEventCodes);
        $canRegisterCheck = $this->additionalRegistrationService
            ->canUserRegisterForEvents($user, $eventCodes);

        if (! $canRegisterCheck['can_register']) {
            session()->flash('error', $canRegisterCheck['message']);

            return;
        }

        $registration = $user->registration;

        // Create additional registration
        $result = $this->additionalRegistrationService->createAdditionalRegistration(
            $user,
            $eventCodes,
            $registration->registration_category_snapshot,
            $registration->participation_format ?: 'in-person',
            'bank_transfer'
        );

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->redirect(route('registrations.my'));
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function render(): View
    {
        // Ensure ALL existing events are always selected before rendering
        if (! empty($this->existingEventCodes)) {
            $missingExistingEvents = array_diff($this->existingEventCodes, $this->selectedEvents);
            if (! empty($missingExistingEvents)) {
                $this->selectedEvents = array_unique(array_merge($this->selectedEvents, $missingExistingEvents));
            }
        }

        return view('livewire.additional-registration-form');
    }

    // Debug method to force refresh - remove in production
    public function refreshData(): void
    {
        $this->loadImmutableEvents();
        $this->loadExistingEvents();
        $this->preSelectExistingEvents();
    }
}
