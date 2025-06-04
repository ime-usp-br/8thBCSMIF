<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isBrazilianDocument = $this->input('document_country_origin') === 'Brasil';

        return [
            // Personal Information
            'full_name' => ['required', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],

            // Identification Details
            'document_country_origin' => ['required', 'string', 'max:255'],
            'cpf' => [
                'nullable',
                'string',
                'max:14',
                Rule::requiredIf($isBrazilianDocument),
            ],
            'rg_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::requiredIf($isBrazilianDocument),
            ],
            'passport_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::requiredIf(! $isBrazilianDocument),
            ],
            'passport_expiry_date' => [
                'nullable',
                'date',
                Rule::requiredIf(! $isBrazilianDocument && $this->filled('passport_number')),
                'after_or_equal:today',
            ],

            // Contact Information
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state_province' => ['nullable', 'string', 'max:255'],
            'address_country' => ['required', 'string', 'max:255'],
            'address_postal_code' => ['nullable', 'string', 'max:20'],

            // Professional Details
            'affiliation' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'string', Rule::in(['undergrad_student', 'grad_student', 'researcher', 'professor', 'professional', 'other'])],
            'is_abe_member' => ['boolean'], // Default is false, so allowing true/false/'0'/'1'

            // Event Participation
            'arrival_date' => ['nullable', 'date'],
            'departure_date' => ['nullable', 'date', 'after_or_equal:arrival_date'],
            'selected_event_codes' => ['required', 'array', 'min:1'],
            'selected_event_codes.*' => ['required', 'string', Rule::exists('events', 'code')],
            'participation_format' => ['required', 'string', Rule::in(['in-person', 'online'])],
            'needs_transport_from_gru' => ['boolean'],
            'needs_transport_from_usp' => ['boolean'],

            // Dietary Restrictions
            'dietary_restrictions' => ['nullable', 'string', Rule::in(['none', 'vegetarian', 'vegan', 'gluten-free', 'other'])],
            'other_dietary_restrictions' => ['nullable', 'string', 'max:255', 'required_if:dietary_restrictions,other'],

            // Emergency Contact
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],

            // Visa Support
            'requires_visa_letter' => ['boolean'],

            // USP Identification
            'sou_da_usp' => ['boolean'],
            'codpes' => ['nullable', 'numeric', 'digits_between:6,8'], // AC3 will add required_if

            // Declaration
            'confirm_information_accuracy' => ['accepted'],
            'confirm_data_processing_consent' => ['accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => __('validation.custom.registration.full_name_required'),
            'email.required' => __('validation.custom.registration.email_required'),
            'email.email' => __('validation.custom.registration.email_format'),
            'document_country_origin.required' => __('validation.custom.registration.document_country_origin_required'),
            'address_country.required' => __('validation.custom.registration.address_country_required'),
            'position.required' => __('validation.custom.registration.position_required'),
            'selected_event_codes.required' => __('validation.custom.registration.selected_event_codes_required'),
            'selected_event_codes.*.exists' => __('validation.custom.registration.selected_event_code_invalid'),
            'participation_format.required' => __('validation.custom.registration.participation_format_required'),
            'other_dietary_restrictions.required_if' => __('validation.custom.registration.other_dietary_restrictions_required_if'),
            'codpes.numeric' => __('validation.custom.registration.codpes_numeric'),
            'codpes.digits_between' => __('validation.custom.registration.codpes_digits_between'),
            'confirm_information_accuracy.accepted' => __('validation.custom.registration.confirm_information_accuracy_accepted'),
            'confirm_data_processing_consent.accepted' => __('validation.custom.registration.confirm_data_processing_consent_accepted'),
            'departure_date.after_or_equal' => __('validation.custom.registration.departure_date_after_or_equal_arrival_date'),
            // Messages for AC2 conditional fields (required_if)
            'cpf.required' => __('validation.custom.registration.cpf_required_if_brazil'),
            'rg_number.required' => __('validation.custom.registration.rg_number_required_if_brazil'),
            'passport_number.required' => __('validation.custom.registration.passport_number_required_if_not_brazil'),
            'passport_expiry_date.required' => __('validation.custom.registration.passport_expiry_date_required_if_not_brazil'),
            'passport_expiry_date.after_or_equal' => __('validation.custom.registration.passport_expiry_date_after_or_equal_today'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => __('Full Name'),
            'nationality' => __('Nationality'),
            'date_of_birth' => __('Date of Birth'),
            'gender' => __('Gender'),
            'document_country_origin' => __('Document Country of Origin'),
            'cpf' => __('CPF'),
            'rg_number' => __('RG Number'),
            'passport_number' => __('Passport Number'),
            'passport_expiry_date' => __('Passport Expiry Date'),
            'email' => __('Email'),
            'phone_number' => __('Phone Number'),
            'address_street' => __('Street Address'),
            'address_city' => __('City'),
            'address_state_province' => __('State/Province'),
            'address_country' => __('Country of Residence'),
            'address_postal_code' => __('Postal Code'),
            'affiliation' => __('Affiliation'),
            'position' => __('Position'),
            'is_abe_member' => __('ABE Member'),
            'arrival_date' => __('Arrival Date'),
            'departure_date' => __('Departure Date'),
            'selected_event_codes' => __('Selected Events'),
            'selected_event_codes.*' => __('Selected Event'),
            'participation_format' => __('Participation Format'),
            'needs_transport_from_gru' => __('Transport from GRU'),
            'needs_transport_from_usp' => __('Transport from USP'),
            'dietary_restrictions' => __('Dietary Restrictions'),
            'other_dietary_restrictions' => __('Other Dietary Restrictions'),
            'emergency_contact_name' => __('Emergency Contact Name'),
            'emergency_contact_relationship' => __('Emergency Contact Relationship'),
            'emergency_contact_phone' => __('Emergency Contact Phone'),
            'requires_visa_letter' => __('Visa Invitation Letter Requirement'),
            'sou_da_usp' => __('USP Affiliation Declaration'),
            'codpes' => __('USP Number (codpes)'),
            'confirm_information_accuracy' => __('Information Accuracy Confirmation'),
            'confirm_data_processing_consent' => __('Data Processing Consent'),
        ];
    }
}
