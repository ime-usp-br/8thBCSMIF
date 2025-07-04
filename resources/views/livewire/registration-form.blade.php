<?php

use App\Events\NewRegistrationCreated;
use App\Models\Event;
use App\Models\Registration;
use App\Services\FeeCalculationService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    // Personal Information
    public string $full_name = '';
    public string $nationality = '';
    public string $date_of_birth = '';
    public string $gender = '';
    public string $other_gender = '';

    // Identification Details
    public string $document_country_origin = 'BR';
    public string $other_document_country_origin = '';
    public string $cpf = '';
    public string $rg_number = '';
    public string $passport_number = '';
    public string $passport_expiry_date = '';

    // Contact Information
    public string $email = '';
    public string $phone_number = '';
    public string $address_street = '';
    public string $address_city = '';
    public string $address_state_province = '';
    public string $address_country = 'BR';
    public string $other_address_country = '';
    public string $address_postal_code = '';

    // Professional Details
    public string $affiliation = '';
    public string $position = '';
    public string $other_position = '';
    public string $is_abe_member = '';

    // Event Participation
    public string $arrival_date = '';
    public string $departure_date = '';
    public array $selected_event_codes = [];
    public string $participation_format = '';
    public bool $needs_transport_from_gru = false;
    public bool $needs_transport_from_usp = false;

    // Dietary Restrictions
    public string $dietary_restrictions = '';
    public string $other_dietary_restrictions = '';

    // Emergency Contact
    public string $emergency_contact_name = '';
    public string $emergency_contact_relationship = '';
    public string $emergency_contact_phone = '';

    // Visa Support
    public string $requires_visa_letter = '';

    // Declaration
    public bool $confirm_information = false;
    public bool $consent_data_processing = false;

    // Fee calculation
    public array $fee_details = [];
    public float $total_fee = 0.0;

    // Available options
    public array $available_events = [];
    public array $countries = [];
    
    // Success state
    public bool $registration_successful = false;
    
    // Error handling state
    public string $general_error_message = '';

    public function mount(): void
    {
        // Load available events
        $this->available_events = Event::all()->pluck('name', 'code')->toArray();
        
        // Load countries (simplified list for now)
        $this->countries = [
            'AR' => __('Argentina'),
            'BR' => __('Brazil'),
            'CA' => __('Canada'),
            'CL' => __('Chile'),
            'CO' => __('Colombia'),
            'US' => __('United States'),
            'MX' => __('Mexico'),
            'PE' => __('Peru'),
            'UY' => __('Uruguay'),
            'VE' => __('Venezuela'),
            'OTHER' => __('Other'),
        ];

        // Pre-fill email if user is logged in
        if (auth()->user()) {
            $this->email = auth()->user()->email;
        }
    }

    public function updatedSelectedEventCodes(): void
    {
        $this->calculateFees();
    }

    public function updatedPosition(): void
    {
        $this->calculateFees();
    }

    public function updatedIsAbeMember(): void
    {
        $this->calculateFees();
    }

    public function updatedParticipationFormat(): void
    {
        $this->calculateFees();
    }

    protected function calculateFees(): void
    {
        if (empty($this->selected_event_codes) || empty($this->position)) {
            $this->fee_details = [];
            $this->total_fee = 0.0;
            return;
        }

        try {
            // Map position to participant category
            $participantCategory = match ($this->position) {
                'undergraduate_student' => 'undergrad_student',
                'graduate_student' => 'grad_student',
                'researcher', 'professor' => $this->is_abe_member === 'yes' ? 'professor_abe' : 'professor_non_abe_professional',
                'professional' => 'professor_non_abe_professional',
                default => 'professor_non_abe_professional'
            };

            $feeCalculationService = app(FeeCalculationService::class);
            $feeCalculation = $feeCalculationService->calculateFees(
                $participantCategory,
                $this->selected_event_codes,
                Carbon::now(),
                $this->participation_format ?: 'in-person'
            );

            $this->fee_details = $feeCalculation['details'];
            $this->total_fee = $feeCalculation['total_fee'];
            
            // Clear any previous fee calculation errors
            $this->resetErrorBag(['general', 'selected_event_codes']);
            
        } catch (\Exception $e) {
            $this->fee_details = [];
            $this->total_fee = 0.0;
            
            if (config('app.debug')) {
                $this->addError('general', __('Fee calculation error: ') . $e->getMessage());
            } else {
                $this->addError('general', __('Unable to calculate registration fees. Please try again or contact support.'));
            }
        }
    }

    public function submit(): void
    {
        // Perform client-side validation with Livewire
        $this->validate([
            'full_name' => 'required|string|max:255',
            'nationality' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string|in:male,female,other,prefer_not_to_say',
            'other_gender' => 'required_if:gender,other|string|max:255',
            'document_country_origin' => 'required|string',
            'cpf' => 'required_if:document_country_origin,BR|nullable|string|max:20',
            'rg_number' => 'required_if:document_country_origin,BR|nullable|string|max:20',
            'passport_number' => 'required_unless:document_country_origin,BR|nullable|string|max:50',
            'passport_expiry_date' => 'required_unless:document_country_origin,BR|nullable|date|after:today',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'address_street' => 'required|string|max:255',
            'address_city' => 'required|string|max:255',
            'address_state_province' => 'required|string|max:255',
            'address_country' => 'required|string|max:2',
            'address_postal_code' => 'required|string|max:20',
            'affiliation' => 'required|string|max:255',
            'position' => 'required|string|in:undergraduate_student,graduate_student,researcher,professor,professional,other',
            'other_position' => 'required_if:position,other|string|max:255',
            'is_abe_member' => 'required|string|in:yes,no',
            'arrival_date' => 'required|date|after_or_equal:today',
            'departure_date' => 'required|date|after_or_equal:arrival_date',
            'selected_event_codes' => 'required|array|min:1',
            'selected_event_codes.*' => 'exists:events,code',
            'participation_format' => 'required|string|in:in-person,online',
            'dietary_restrictions' => 'required|string|in:none,vegetarian,vegan,gluten_free,other',
            'other_dietary_restrictions' => 'required_if:dietary_restrictions,other|string|max:255',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_relationship' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20',
            'requires_visa_letter' => 'required_unless:document_country_origin,BR|nullable|string|in:yes,no',
            'confirm_information' => 'required|accepted',
            'consent_data_processing' => 'required|accepted',
        ]);
        
        // Validation passed - allow form submission to RegistrationController@store
        // This method only validates; actual submission happens via HTML form
    }

    public function validateAndSubmit(): void
    {
        try {
            // Perform Livewire validation first
            $this->validate([
                'full_name' => 'required|string|max:255',
                'nationality' => 'required|string|max:255',
                'date_of_birth' => 'required|date|before:today',
                'gender' => 'required|string|in:male,female,other,prefer_not_to_say',
                'other_gender' => 'required_if:gender,other|string|max:255',
                'document_country_origin' => 'required|string',
                'cpf' => 'required_if:document_country_origin,BR|nullable|string|max:20',
                'rg_number' => 'required_if:document_country_origin,BR|nullable|string|max:20',
                'passport_number' => 'required_unless:document_country_origin,BR|nullable|string|max:50',
                'passport_expiry_date' => 'required_unless:document_country_origin,BR|nullable|date|after:today',
                'email' => 'required|email|max:255',
                'phone_number' => 'required|string|max:20',
                'address_street' => 'required|string|max:255',
                'address_city' => 'required|string|max:255',
                'address_state_province' => 'required|string|max:255',
                'address_country' => 'required|string|max:2',
                'address_postal_code' => 'required|string|max:20',
                'affiliation' => 'required|string|max:255',
                'position' => 'required|string|in:undergraduate_student,graduate_student,researcher,professor,professional,other',
                'other_position' => 'required_if:position,other|string|max:255',
                'is_abe_member' => 'required|string|in:yes,no',
                'arrival_date' => 'required|date|after_or_equal:today',
                'departure_date' => 'required|date|after_or_equal:arrival_date',
                'selected_event_codes' => 'required|array|min:1',
                'selected_event_codes.*' => 'exists:events,code',
                'participation_format' => 'required|string|in:in-person,online',
                'dietary_restrictions' => 'required|string|in:none,vegetarian,vegan,gluten_free,other',
                'other_dietary_restrictions' => 'required_if:dietary_restrictions,other|string|max:255',
                'emergency_contact_name' => 'required|string|max:255',
                'emergency_contact_relationship' => 'required|string|max:255',
                'emergency_contact_phone' => 'required|string|max:20',
                'requires_visa_letter' => 'required_unless:document_country_origin,BR|nullable|string|in:yes,no',
                'confirm_information' => 'required|accepted',
                'consent_data_processing' => 'required|accepted',
            ]);

            // Clear any previous general errors
            $this->clearGeneralError();
            
            // Validate that we have selected events and can calculate fees
            if (empty($this->selected_event_codes)) {
                $this->showGeneralError(__('You must select at least one event to register.'));
                return;
            }

            // Validate fee calculation works
            if ($this->total_fee < 0) {
                $this->showGeneralError(__('Unable to calculate registration fees. Please try again or contact support.'));
                return;
            }

            // If validation passes, redirect to controller with data
            $this->submitRegistration();

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Livewire validation errors are automatically handled
            // Scroll to first error after a small delay to ensure DOM is updated
            $this->js('
                setTimeout(() => {
                    const firstError = document.querySelector("[class*=\"text-red-\"], .text-red-600, .text-red-500");
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: "smooth", block: "center" });
                    }
                }, 100);
            ');
            // Just re-throw to let Livewire handle the display
            throw $e;
        } catch (\Exception $e) {
            // Handle unexpected errors
            if (config('app.debug')) {
                $this->showGeneralError(__('An error occurred during validation: ') . $e->getMessage());
            } else {
                $this->showGeneralError(__('An unexpected error occurred. Please try again or contact support.'));
            }
            
            // Scroll to general error message
            $this->js('
                setTimeout(() => {
                    const generalError = document.querySelector("[dusk=\"general-error-message\"]");
                    if (generalError) {
                        generalError.scrollIntoView({ behavior: "smooth", block: "center" });
                    }
                }, 100);
            ');
        }
    }


    public function resetForm(): void
    {
        $this->reset([
            'full_name', 'nationality', 'date_of_birth', 'gender', 'other_gender',
            'document_country_origin', 'other_document_country_origin', 'cpf', 'rg_number', 'passport_number', 'passport_expiry_date',
            'email', 'phone_number', 'address_street', 'address_city', 'address_state_province', 
            'address_country', 'other_address_country', 'address_postal_code', 'affiliation', 'position', 'other_position',
            'is_abe_member', 'arrival_date', 'departure_date', 'selected_event_codes', 
            'participation_format', 'needs_transport_from_gru', 'needs_transport_from_usp',
            'dietary_restrictions', 'other_dietary_restrictions', 'emergency_contact_name',
            'emergency_contact_relationship', 'emergency_contact_phone', 'requires_visa_letter',
            'confirm_information', 'consent_data_processing', 'registration_successful', 'general_error_message'
        ]);
        
        // Re-initialize defaults
        $this->document_country_origin = 'BR';
        $this->address_country = 'BR';
        if (auth()->user()) {
            $this->email = auth()->user()->email;
        }
    }

    public function showGeneralError(string $message): void
    {
        $this->general_error_message = $message;
    }

    public function clearGeneralError(): void
    {
        $this->general_error_message = '';
    }

    public function submitRegistration(): void
    {
        try {
            // Create the JS to submit form dynamically with current component data
            $this->js('
                const formData = {
                    full_name: "' . addslashes($this->full_name) . '",
                    nationality: "' . addslashes($this->nationality) . '",
                    date_of_birth: "' . addslashes($this->date_of_birth) . '",
                    gender: "' . addslashes($this->gender === 'other' ? $this->other_gender : $this->gender) . '",
                    document_country_origin: "' . addslashes($this->document_country_origin === 'OTHER' ? $this->other_document_country_origin : $this->document_country_origin) . '",
                    cpf: "' . addslashes($this->cpf) . '",
                    rg_number: "' . addslashes($this->rg_number) . '",
                    passport_number: "' . addslashes($this->passport_number) . '",
                    passport_expiry_date: "' . addslashes($this->passport_expiry_date) . '",
                    email: "' . addslashes($this->email) . '",
                    phone_number: "' . addslashes($this->phone_number) . '",
                    address_street: "' . addslashes($this->address_street) . '",
                    address_city: "' . addslashes($this->address_city) . '",
                    address_state_province: "' . addslashes($this->address_state_province) . '",
                    address_country: "' . addslashes($this->address_country === 'OTHER' ? $this->other_address_country : $this->address_country) . '",
                    address_postal_code: "' . addslashes($this->address_postal_code) . '",
                    affiliation: "' . addslashes($this->affiliation) . '",
                    position: "' . addslashes($this->position === 'other' ? $this->other_position : $this->position) . '",
                    is_abe_member: "' . ($this->is_abe_member === 'yes' ? '1' : '0') . '",
                    arrival_date: "' . addslashes($this->arrival_date) . '",
                    departure_date: "' . addslashes($this->departure_date) . '",
                    selected_event_codes: ' . json_encode($this->selected_event_codes) . ',
                    participation_format: "' . addslashes($this->participation_format) . '",
                    needs_transport_from_gru: "' . ($this->needs_transport_from_gru ? '1' : '0') . '",
                    needs_transport_from_usp: "' . ($this->needs_transport_from_usp ? '1' : '0') . '",
                    dietary_restrictions: "' . addslashes($this->dietary_restrictions === 'other' ? $this->other_dietary_restrictions : $this->dietary_restrictions) . '",
                    emergency_contact_name: "' . addslashes($this->emergency_contact_name) . '",
                    emergency_contact_relationship: "' . addslashes($this->emergency_contact_relationship) . '",
                    emergency_contact_phone: "' . addslashes($this->emergency_contact_phone) . '",
                    requires_visa_letter: "' . ($this->requires_visa_letter === 'yes' ? '1' : '0') . '",
                    confirm_information_accuracy: "' . ($this->confirm_information ? '1' : '0') . '",
                    confirm_data_processing_consent: "' . ($this->consent_data_processing ? '1' : '0') . '"
                };
                
                // Create and submit form
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "' . route('event-registrations.store') . '";
                
                // Add CSRF token
                const csrfToken = document.createElement("input");
                csrfToken.type = "hidden";
                csrfToken.name = "_token";
                csrfToken.value = "' . csrf_token() . '";
                form.appendChild(csrfToken);
                
                // Add all form data
                Object.keys(formData).forEach(key => {
                    const value = formData[key];
                    if (Array.isArray(value)) {
                        value.forEach(item => {
                            const input = document.createElement("input");
                            input.type = "hidden";
                            input.name = key + "[]";
                            input.value = item;
                            form.appendChild(input);
                        });
                    } else if (value !== null && value !== undefined) {
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = key;
                        input.value = value;
                        form.appendChild(input);
                    }
                });
                
                document.body.appendChild(form);
                form.submit();
            ');

        } catch (\Exception $e) {
            if (config('app.debug')) {
                $this->showGeneralError(__('Registration submission error: ') . $e->getMessage());
            } else {
                $this->showGeneralError(__('An error occurred during registration. Please try again or contact support.'));
            }
        }
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-5xl mx-auto py-4 px-3 sm:py-6 sm:px-6 lg:py-8 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-6 sm:p-8 text-gray-900 dark:text-gray-100">
                <div class="text-center mb-8">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-2" dusk="registration-form-title">{{ __('8th BCSMIF Registration Form') }}</h1>
                    <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">{{ __('Please fill out all required information to register for the conference') }}</p>
                </div>

                {{-- Success Display --}}
                @if($registration_successful)
                    <div class="text-center">
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <div class="flex items-center justify-center mb-4">
                                <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold mb-2">{{ __('Registration Successful!') }}</h3>
                            <p class="mb-4">{{ __('Your registration has been submitted successfully. You can view and manage your registrations on the My Registrations page.') }}</p>
                            <div class="space-y-2 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                                <a href="{{ route('registrations.my') }}" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200">
                                    {{ __('View My Registrations') }}
                                </a>
                                <button type="button" wire:click="resetForm" class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-all duration-200">
                                    {{ __('Submit Another Registration') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- General Error Display --}}
                    @if($errors->has('general') || !empty($general_error_message))
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert" dusk="general-error-message">
                            <strong class="font-bold">{{ __('Error!') }}</strong>
                            <span class="block sm:inline">
                                @if($errors->has('general'))
                                    {{ $errors->first('general') }}
                                @else
                                    {{ $general_error_message }}
                                @endif
                            </span>
                        </div>
                    @endif

                    {{-- Session Error Display (from Controller) --}}
                    @if(session('error'))
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">{{ __('Error!') }}</strong>
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif


                <div class="space-y-8">
                    {{-- Personal Information --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('1. Personal Information') }}</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <x-input-label for="full_name" :value="__('Full Name')" />
                                <x-text-input wire:model="full_name" id="full_name" class="block mt-1 w-full" type="text" required dusk="full-name-input" />
                                <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="nationality" :value="__('Nationality')" />
                                <x-text-input wire:model="nationality" id="nationality" class="block mt-1 w-full" type="text" required dusk="nationality-input" />
                                <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                                <x-text-input wire:model="date_of_birth" id="date_of_birth" class="block mt-1 w-full" type="date" required dusk="date-of-birth-input" />
                                <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('Gender')" />
                                <div class="mt-2 space-y-2 sm:space-y-3">
                                    <label class="flex items-center">
                                        <input wire:model.live="gender" type="radio" value="male" name="gender" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="gender-male">
                                        <span class="ml-2">{{ __('Male') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="gender" type="radio" value="female" name="gender" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="gender-female">
                                        <span class="ml-2">{{ __('Female') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="gender" type="radio" value="other" name="gender" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="gender-other">
                                        <span class="ml-2">{{ __('Other') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="gender" type="radio" value="prefer_not_to_say" name="gender" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="gender-prefer-not-to-say">
                                        <span class="ml-2">{{ __('Prefer not to say') }}</span>
                                    </label>
                                </div>
                                @if($gender === 'other')
                                    <div class="mt-2">
                                        <x-text-input wire:model="other_gender" placeholder="{{ __('Please specify') }}" class="block w-full" type="text" required />
                                        <x-input-error :messages="$errors->get('other_gender')" class="mt-2" />
                                    </div>
                                @endif
                                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- Identification Details --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('2. Identification Details') }}</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div class="sm:col-span-2">
                                <x-input-label for="document_country_origin" :value="__('Country of residence')" />
                                <select wire:model.live="document_country_origin" id="document_country_origin" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-usp-blue-pri dark:focus:border-usp-blue-sec focus:ring-usp-blue-pri dark:focus:ring-usp-blue-sec rounded-md shadow-sm block mt-1 w-full" required dusk="document-country-origin-select">
                                    @foreach($countries as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('document_country_origin')" class="mt-2" />
                                @if($document_country_origin === 'OTHER')
                                    <div class="mt-2">
                                        <x-text-input wire:model="other_document_country_origin" placeholder="{{ __('Please specify the country') }}" class="block w-full" type="text" required dusk="other-document-country-input" />
                                        <x-input-error :messages="$errors->get('other_document_country_origin')" class="mt-2" />
                                    </div>
                                @endif
                            </div>

                            @if($document_country_origin === 'BR')
                                <div>
                                    <x-input-label for="cpf" :value="__('CPF')" />
                                    <x-text-input wire:model="cpf" id="cpf" class="block mt-1 w-full" type="text" placeholder="000.000.000-00" required dusk="cpf-input" />
                                    <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="rg_number" :value="__('RG (ID) Number')" />
                                    <x-text-input wire:model="rg_number" id="rg_number" class="block mt-1 w-full" type="text" required dusk="rg-number-input" />
                                    <x-input-error :messages="$errors->get('rg_number')" class="mt-2" />
                                </div>
                            @else
                                <div>
                                    <x-input-label for="passport_number" :value="__('Passport Number')" />
                                    <x-text-input wire:model="passport_number" id="passport_number" class="block mt-1 w-full" type="text" required />
                                    <x-input-error :messages="$errors->get('passport_number')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="passport_expiry_date" :value="__('Passport Expiry Date')" />
                                    <x-text-input wire:model="passport_expiry_date" id="passport_expiry_date" class="block mt-1 w-full" type="date" required />
                                    <x-input-error :messages="$errors->get('passport_expiry_date')" class="mt-2" />
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Contact Information --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('3. Contact Information') }}</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required dusk="email-input" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="phone_number" :value="__('Phone Number')" />
                                <x-text-input wire:model="phone_number" id="phone_number" class="block mt-1 w-full" type="tel" placeholder="+55 11 987654321" required dusk="phone-number-input" />
                                <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="address_street" :value="__('Street Address')" />
                                <x-text-input wire:model="address_street" id="address_street" class="block mt-1 w-full" type="text" required dusk="street-address-input" />
                                <x-input-error :messages="$errors->get('address_street')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="address_city" :value="__('City')" />
                                <x-text-input wire:model="address_city" id="address_city" class="block mt-1 w-full" type="text" required dusk="city-input" />
                                <x-input-error :messages="$errors->get('address_city')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="address_state_province" :value="__('State/Province')" />
                                <x-text-input wire:model="address_state_province" id="address_state_province" class="block mt-1 w-full" type="text" required dusk="state-province-input" />
                                <x-input-error :messages="$errors->get('address_state_province')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="address_country" :value="__('Country')" />
                                <select wire:model.live="address_country" id="address_country" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-usp-blue-pri dark:focus:border-usp-blue-sec focus:ring-usp-blue-pri dark:focus:ring-usp-blue-sec rounded-md shadow-sm block mt-1 w-full" required dusk="country-select">
                                    @foreach($countries as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('address_country')" class="mt-2" />
                                @if($address_country === 'OTHER')
                                    <div class="mt-2">
                                        <x-text-input wire:model="other_address_country" placeholder="{{ __('Please specify the country') }}" class="block w-full" type="text" required dusk="other-address-country-input" />
                                        <x-input-error :messages="$errors->get('other_address_country')" class="mt-2" />
                                    </div>
                                @endif
                            </div>

                            <div>
                                <x-input-label for="address_postal_code" :value="__('Postal Code')" />
                                <x-text-input wire:model="address_postal_code" id="address_postal_code" class="block mt-1 w-full" type="text" required dusk="postal-code-input" />
                                <x-input-error :messages="$errors->get('address_postal_code')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- Professional Details --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('4. Professional Details') }}</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div class="sm:col-span-2">
                                <x-input-label for="affiliation" :value="__('Affiliation (University/Organization)')" />
                                <x-text-input wire:model="affiliation" id="affiliation" class="block mt-1 w-full" type="text" required dusk="affiliation-input" />
                                <x-input-error :messages="$errors->get('affiliation')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('Position')" />
                                <div class="mt-2 space-y-2 sm:space-y-3">
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="undergraduate_student" name="position" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="position-undergraduate">
                                        <span class="ml-2">{{ __('Undergraduate Student') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="graduate_student" name="position" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="position-graduate">
                                        <span class="ml-2">{{ __('Graduate Student') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="researcher" name="position" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="position-postgraduate">
                                        <span class="ml-2">{{ __('Researcher') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="professor" name="position" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="position-professor">
                                        <span class="ml-2">{{ __('Professor') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="professional" name="position" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="position-professional">
                                        <span class="ml-2">{{ __('Professional') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="other" name="position" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="position-other">
                                        <span class="ml-2">{{ __('Other') }}</span>
                                    </label>
                                </div>
                                @if($position === 'other')
                                    <div class="mt-2">
                                        <x-text-input wire:model="other_position" placeholder="{{ __('Please specify') }}" class="block w-full" type="text" required />
                                        <x-input-error :messages="$errors->get('other_position')" class="mt-2" />
                                    </div>
                                @endif
                                <x-input-error :messages="$errors->get('position')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('ABE affiliation')" />
                                <div class="mt-2 space-y-2 sm:space-y-3">
                                    <label class="flex items-center">
                                        <input wire:model.live="is_abe_member" type="radio" value="yes" name="is_abe_member" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="is-abe-member-yes">
                                        <span class="ml-2">{{ __('Yes') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="is_abe_member" type="radio" value="no" name="is_abe_member" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="is-abe-member-no">
                                        <span class="ml-2">{{ __('No') }}</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('is_abe_member')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- Event Participation --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('5. Event Participation') }}</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <x-input-label for="arrival_date" :value="__('Arrival Date')" />
                                <x-text-input wire:model="arrival_date" id="arrival_date" class="block mt-1 w-full" type="date" required dusk="arrival-date-input" />
                                <x-input-error :messages="$errors->get('arrival_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="departure_date" :value="__('Departure Date')" />
                                <x-text-input wire:model="departure_date" id="departure_date" class="block mt-1 w-full" type="date" required dusk="departure-date-input" />
                                <x-input-error :messages="$errors->get('departure_date')" class="mt-2" />
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label :value="__('Which events would you like to register for?')" />
                                <div class="mt-2 space-y-2 sm:space-y-3">
                                    @foreach($available_events as $code => $name)
                                        <label class="flex items-center">
                                            <input wire:model.live="selected_event_codes" type="checkbox" value="{{ $code }}" name="selected_event_codes[]" class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="event-{{ $code }}">
                                            <span class="ml-2">{{ $name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('selected_event_codes')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('Participation format')" />
                                <div class="mt-2 space-y-2 sm:space-y-3">
                                    <label class="flex items-center">
                                        <input wire:model.live="participation_format" type="radio" value="in-person" name="participation_format" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="participation-format-in-person">
                                        <span class="ml-2">{{ __('In-person') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="participation_format" type="radio" value="online" name="participation_format" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="participation-format-online">
                                        <span class="ml-2">{{ __('Online') }}</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('participation_format')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('Transportation')" />
                                <div class="mt-2 space-y-2 sm:space-y-3">
                                    <label class="flex items-center">
                                        <input wire:model="needs_transport_from_gru" type="checkbox" class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="transport-gru">
                                        <span class="ml-2">{{ __('I need transportation from GRU Airport to Maresias and back.') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model="needs_transport_from_usp" type="checkbox" class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="transport-usp">
                                        <span class="ml-2">{{ __('I need transportation from USP to Maresias and back.') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Fee Display --}}
                    @if(!empty($fee_details))
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                            <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('Registration Fees') }}</h2>
                            <div class="bg-gradient-to-r from-usp-blue-sec/10 to-usp-yellow/10 dark:from-gray-700 dark:to-gray-600 p-4 sm:p-6 rounded-xl border-l-4 border-usp-blue-pri">
                                @foreach($fee_details as $detail)
                                    <div class="flex flex-col sm:flex-row sm:justify-between py-2 space-y-1 sm:space-y-0">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $detail['event_name'] }}</span>
                                        <span class="font-semibold text-usp-blue-pri dark:text-usp-blue-sec">R$ {{ number_format($detail['calculated_price'], 2, ',', '.') }}</span>
                                    </div>
                                @endforeach
                                <hr class="my-2">
                                <div class="flex flex-col sm:flex-row sm:justify-between font-bold text-lg pt-2 space-y-1 sm:space-y-0">
                                    <span class="text-gray-900 dark:text-gray-100">{{ __('Total') }}</span>
                                    <span class="text-xl text-usp-blue-pri dark:text-usp-yellow">R$ {{ number_format($total_fee, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Dietary Restrictions --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('6. Dietary Restrictions') }}</h2>
                        
                        <div>
                            <x-input-label :value="__('Dietary Restrictions')" />
                            <div class="mt-2 space-y-2 sm:space-y-3">
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="none" name="dietary_restrictions" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="dietary-restrictions-none">
                                    <span class="ml-2">{{ __('None') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="vegetarian" name="dietary_restrictions" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="dietary-restrictions-vegetarian">
                                    <span class="ml-2">{{ __('Vegetarian') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="vegan" name="dietary_restrictions" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="dietary-restrictions-vegan">
                                    <span class="ml-2">{{ __('Vegan') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="gluten_free" name="dietary_restrictions" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="dietary-restrictions-gluten-free">
                                    <span class="ml-2">{{ __('Gluten-Free') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="other" name="dietary_restrictions" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="dietary-restrictions-other">
                                    <span class="ml-2">{{ __('Other') }}</span>
                                </label>
                            </div>
                            @if($dietary_restrictions === 'other')
                                <div class="mt-2">
                                    <x-text-input wire:model="other_dietary_restrictions" placeholder="{{ __('Please specify') }}" class="block w-full" type="text" required />
                                    <x-input-error :messages="$errors->get('other_dietary_restrictions')" class="mt-2" />
                                </div>
                            @endif
                            <x-input-error :messages="$errors->get('dietary_restrictions')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Emergency Contact --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('7. Emergency Contact') }}</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <x-input-label for="emergency_contact_name" :value="__('Name')" />
                                <x-text-input wire:model="emergency_contact_name" id="emergency_contact_name" class="block mt-1 w-full" type="text" required dusk="emergency-contact-name-input" />
                                <x-input-error :messages="$errors->get('emergency_contact_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="emergency_contact_relationship" :value="__('Relationship')" />
                                <x-text-input wire:model="emergency_contact_relationship" id="emergency_contact_relationship" class="block mt-1 w-full" type="text" required dusk="emergency-contact-relationship-input" />
                                <x-input-error :messages="$errors->get('emergency_contact_relationship')" class="mt-2" />
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="emergency_contact_phone" :value="__('Phone Number')" />
                                <x-text-input wire:model="emergency_contact_phone" id="emergency_contact_phone" class="block mt-1 w-full" type="tel" placeholder="+55 11 987654321" required dusk="emergency-contact-phone-input" />
                                <x-input-error :messages="$errors->get('emergency_contact_phone')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- Visa Support --}}
                    @if($document_country_origin !== 'BR')
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                            <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('8. Visa Support') }}</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('(For international participants only)') }}</p>
                            
                            <div>
                                <x-input-label :value="__('Do you require an invitation letter to get a Brazilian visa?')" />
                                <div class="mt-2 space-y-2 sm:space-y-3">
                                    <label class="flex items-center">
                                        <input wire:model="requires_visa_letter" type="radio" value="yes" name="requires_visa_letter" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="requires-visa-letter-yes">
                                        <span class="ml-2">{{ __('Yes') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model="requires_visa_letter" type="radio" value="no" name="requires_visa_letter" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri" dusk="requires-visa-letter-no">
                                        <span class="ml-2">{{ __('No') }}</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('requires_visa_letter')" class="mt-2" />
                            </div>
                        </div>
                    @endif

                    {{-- Declaration --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4 text-usp-blue-pri dark:text-usp-blue-sec">{{ __('9. Declaration') }}</h2>
                        
                        <div class="space-y-4">
                            <label class="flex items-start">
                                <input wire:model="confirm_information" type="checkbox" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri mt-1" dusk="confirm-information-checkbox">
                                <span class="ml-2">{{ __('I confirm that the information provided is accurate.') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('confirm_information')" class="mt-2" />

                            <label class="flex items-start">
                                <input wire:model="consent_data_processing" type="checkbox" required class="rounded border-gray-300 text-usp-blue-pri shadow-sm focus:ring-usp-blue-pri mt-1" dusk="consent-data-processing-checkbox">
                                <span class="ml-2">{{ __('I consent to the processing of my data for event logistics.') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('consent_data_processing')" class="mt-2" />
                        </div>
                    </div>



                    {{-- Submit Button --}}
                    <div class="flex flex-col sm:flex-row justify-center sm:justify-end space-y-3 sm:space-y-0 sm:space-x-4 pt-4">
                        <button type="button" onclick="window.history.back()" class="w-full sm:w-auto px-6 py-3 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" wire:loading.attr="disabled" wire:target="validateAndSubmit">
                            {{ __('Cancel') }}
                        </button>
                        <x-primary-button type="button" wire:click="validateAndSubmit" wire:loading.attr="disabled" class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-usp-blue-pri to-usp-blue-sec hover:from-usp-blue-sec hover:to-usp-blue-pri text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105" dusk="submit-registration-button">
                            <span wire:loading.remove wire:target="validateAndSubmit">{{ __('Submit Registration') }}</span>
                            <span wire:loading wire:target="validateAndSubmit" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Submitting...') }}
                            </span>
                        </x-primary-button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>