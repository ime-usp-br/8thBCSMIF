<?php

use App\Models\Event;
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
    }

    public function submit(): void
    {
        $this->validate([
            'full_name' => 'required|string|max:255',
            'nationality' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string|in:male,female,other,prefer_not_to_say',
            'other_gender' => 'required_if:gender,other|string|max:255',
            'document_country_origin' => 'required|string|max:2',
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
            'departure_date' => 'required|date|after:arrival_date',
            'selected_event_codes' => 'required|array|min:1',
            'selected_event_codes.*' => 'exists:events,code',
            'participation_format' => 'required|string|in:in-person,online',
            'dietary_restrictions' => 'required|string|in:none,vegetarian,vegan,gluten_free,other',
            'other_dietary_restrictions' => 'required_if:dietary_restrictions,other|string|max:255',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_relationship' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20',
            'requires_visa_letter' => 'required_unless:document_country_origin,BR|string|in:yes,no',
            'confirm_information' => 'required|accepted',
            'consent_data_processing' => 'required|accepted',
        ]);

        // Prepare data for submission to RegistrationController
        $registrationData = [
            'full_name' => $this->full_name,
            'nationality' => $this->nationality,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender === 'other' ? $this->other_gender : $this->gender,
            'document_country_origin' => $this->document_country_origin,
            'cpf' => $this->cpf,
            'rg_number' => $this->rg_number,
            'passport_number' => $this->passport_number,
            'passport_expiry_date' => $this->passport_expiry_date,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address_street' => $this->address_street,
            'address_city' => $this->address_city,
            'address_state_province' => $this->address_state_province,
            'address_country' => $this->address_country,
            'address_postal_code' => $this->address_postal_code,
            'affiliation' => $this->affiliation,
            'position' => $this->position === 'other' ? $this->other_position : $this->position,
            'is_abe_member' => $this->is_abe_member === 'yes',
            'arrival_date' => $this->arrival_date,
            'departure_date' => $this->departure_date,
            'selected_event_codes' => $this->selected_event_codes,
            'participation_format' => $this->participation_format,
            'needs_transport_from_gru' => $this->needs_transport_from_gru,
            'needs_transport_from_usp' => $this->needs_transport_from_usp,
            'dietary_restrictions' => $this->dietary_restrictions === 'other' ? $this->other_dietary_restrictions : $this->dietary_restrictions,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'requires_visa_letter' => $this->requires_visa_letter === 'yes',
        ];

        // Redirect to store route with form data
        $this->redirect(route('event-registrations.store'), navigate: true);
    }
}; ?>

<div>
    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h1 class="text-2xl font-bold mb-6">{{ __('8th BCSMIF Registration Form') }}</h1>

                <form wire:submit="submit" class="space-y-8">
                    {{-- Personal Information --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4">{{ __('1. Personal Information') }}</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="full_name" :value="__('Full Name')" />
                                <x-text-input wire:model="full_name" id="full_name" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="nationality" :value="__('Nationality')" />
                                <x-text-input wire:model="nationality" id="nationality" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                                <x-text-input wire:model="date_of_birth" id="date_of_birth" class="block mt-1 w-full" type="date" required />
                                <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('Gender')" />
                                <div class="mt-2 space-y-2">
                                    <label class="inline-flex items-center">
                                        <input wire:model.live="gender" type="radio" value="male" name="gender" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Male') }}</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input wire:model.live="gender" type="radio" value="female" name="gender" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Female') }}</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input wire:model.live="gender" type="radio" value="other" name="gender" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Other') }}</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input wire:model.live="gender" type="radio" value="prefer_not_to_say" name="gender" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
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
                        <h2 class="text-lg font-semibold mb-4">{{ __('2. Identification Details') }}</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <x-input-label for="document_country_origin" :value="__('Country of residence')" />
                                <select wire:model.live="document_country_origin" id="document_country_origin" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full" required>
                                    @foreach($countries as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('document_country_origin')" class="mt-2" />
                            </div>

                            @if($document_country_origin === 'BR')
                                <div>
                                    <x-input-label for="cpf" :value="__('CPF')" />
                                    <x-text-input wire:model="cpf" id="cpf" class="block mt-1 w-full" type="text" placeholder="000.000.000-00" required />
                                    <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="rg_number" :value="__('RG (ID) Number')" />
                                    <x-text-input wire:model="rg_number" id="rg_number" class="block mt-1 w-full" type="text" required />
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
                        <h2 class="text-lg font-semibold mb-4">{{ __('3. Contact Information') }}</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="phone_number" :value="__('Phone Number')" />
                                <x-text-input wire:model="phone_number" id="phone_number" class="block mt-1 w-full" type="tel" placeholder="+55 11 987654321" required />
                                <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="address_street" :value="__('Street Address')" />
                                <x-text-input wire:model="address_street" id="address_street" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('address_street')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="address_city" :value="__('City')" />
                                <x-text-input wire:model="address_city" id="address_city" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('address_city')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="address_state_province" :value="__('State/Province')" />
                                <x-text-input wire:model="address_state_province" id="address_state_province" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('address_state_province')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="address_country" :value="__('Country')" />
                                <select wire:model="address_country" id="address_country" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full" required>
                                    @foreach($countries as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('address_country')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="address_postal_code" :value="__('Postal Code')" />
                                <x-text-input wire:model="address_postal_code" id="address_postal_code" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('address_postal_code')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- Professional Details --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4">{{ __('4. Professional Details') }}</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <x-input-label for="affiliation" :value="__('Affiliation (University/Organization)')" />
                                <x-text-input wire:model="affiliation" id="affiliation" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('affiliation')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('Position')" />
                                <div class="mt-2 space-y-2">
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="undergraduate_student" name="position" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Undergraduate Student') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="graduate_student" name="position" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Graduate Student') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="researcher" name="position" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Researcher') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="professor" name="position" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Professor') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="professional" name="position" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Professional') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="position" type="radio" value="other" name="position" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
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
                                <div class="mt-2 space-y-2">
                                    <label class="flex items-center">
                                        <input wire:model.live="is_abe_member" type="radio" value="yes" name="is_abe_member" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Yes') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="is_abe_member" type="radio" value="no" name="is_abe_member" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('No') }}</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('is_abe_member')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- Event Participation --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4">{{ __('5. Event Participation') }}</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="arrival_date" :value="__('Arrival Date')" />
                                <x-text-input wire:model="arrival_date" id="arrival_date" class="block mt-1 w-full" type="date" required />
                                <x-input-error :messages="$errors->get('arrival_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="departure_date" :value="__('Departure Date')" />
                                <x-text-input wire:model="departure_date" id="departure_date" class="block mt-1 w-full" type="date" required />
                                <x-input-error :messages="$errors->get('departure_date')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label :value="__('Which events would you like to register for?')" />
                                <div class="mt-2 space-y-2">
                                    @foreach($available_events as $code => $name)
                                        <label class="flex items-center">
                                            <input wire:model.live="selected_event_codes" type="checkbox" value="{{ $code }}" name="selected_event_codes[]" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            <span class="ml-2">{{ $name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('selected_event_codes')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('Participation format')" />
                                <div class="mt-2 space-y-2">
                                    <label class="flex items-center">
                                        <input wire:model.live="participation_format" type="radio" value="in-person" name="participation_format" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('In-person') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="participation_format" type="radio" value="online" name="participation_format" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Online') }}</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('participation_format')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('Transportation')" />
                                <div class="mt-2 space-y-2">
                                    <label class="flex items-center">
                                        <input wire:model="needs_transport_from_gru" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('I need transportation from GRU Airport to Maresias and back.') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model="needs_transport_from_usp" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('I need transportation from USP to Maresias and back.') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Fee Display --}}
                    @if(!empty($fee_details))
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                            <h2 class="text-lg font-semibold mb-4">{{ __('Registration Fees') }}</h2>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                @foreach($fee_details as $detail)
                                    <div class="flex justify-between py-2">
                                        <span>{{ $detail['event_name'] }}</span>
                                        <span>R$ {{ number_format($detail['calculated_price'], 2, ',', '.') }}</span>
                                    </div>
                                @endforeach
                                <hr class="my-2">
                                <div class="flex justify-between font-bold text-lg">
                                    <span>{{ __('Total') }}</span>
                                    <span>R$ {{ number_format($total_fee, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Dietary Restrictions --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4">{{ __('6. Dietary Restrictions') }}</h2>
                        
                        <div>
                            <x-input-label :value="__('Dietary Restrictions')" />
                            <div class="mt-2 space-y-2">
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="none" name="dietary_restrictions" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2">{{ __('None') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="vegetarian" name="dietary_restrictions" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2">{{ __('Vegetarian') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="vegan" name="dietary_restrictions" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2">{{ __('Vegan') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="gluten_free" name="dietary_restrictions" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2">{{ __('Gluten-Free') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input wire:model.live="dietary_restrictions" type="radio" value="other" name="dietary_restrictions" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
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
                        <h2 class="text-lg font-semibold mb-4">{{ __('7. Emergency Contact') }}</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="emergency_contact_name" :value="__('Name')" />
                                <x-text-input wire:model="emergency_contact_name" id="emergency_contact_name" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('emergency_contact_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="emergency_contact_relationship" :value="__('Relationship')" />
                                <x-text-input wire:model="emergency_contact_relationship" id="emergency_contact_relationship" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('emergency_contact_relationship')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="emergency_contact_phone" :value="__('Phone Number')" />
                                <x-text-input wire:model="emergency_contact_phone" id="emergency_contact_phone" class="block mt-1 w-full" type="tel" placeholder="+55 11 987654321" required />
                                <x-input-error :messages="$errors->get('emergency_contact_phone')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- Visa Support --}}
                    @if($document_country_origin !== 'BR')
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                            <h2 class="text-lg font-semibold mb-4">{{ __('8. Visa Support') }}</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('(For international participants only)') }}</p>
                            
                            <div>
                                <x-input-label :value="__('Do you require an invitation letter to get a Brazilian visa?')" />
                                <div class="mt-2 space-y-2">
                                    <label class="flex items-center">
                                        <input wire:model="requires_visa_letter" type="radio" value="yes" name="requires_visa_letter" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('Yes') }}</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model="requires_visa_letter" type="radio" value="no" name="requires_visa_letter" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">{{ __('No') }}</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('requires_visa_letter')" class="mt-2" />
                            </div>
                        </div>
                    @endif

                    {{-- Declaration --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                        <h2 class="text-lg font-semibold mb-4">{{ __('9. Declaration') }}</h2>
                        
                        <div class="space-y-4">
                            <label class="flex items-start">
                                <input wire:model="confirm_information" type="checkbox" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1">
                                <span class="ml-2">{{ __('I confirm that the information provided is accurate.') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('confirm_information')" class="mt-2" />

                            <label class="flex items-start">
                                <input wire:model="consent_data_processing" type="checkbox" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1">
                                <span class="ml-2">{{ __('I consent to the processing of my data for event logistics.') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('consent_data_processing')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex justify-end">
                        <x-primary-button type="submit" class="px-8 py-3">
                            {{ __('Submit Registration') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>