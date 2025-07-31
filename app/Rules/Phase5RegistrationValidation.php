<?php

namespace App\Rules;

use App\Models\Event;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class Phase5RegistrationValidation implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * The user being validated.
     */
    protected ?User $user;

    /**
     * Create a new rule instance.
     */
    public function __construct(?User $user = null)
    {
        $this->user = $user;
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * Comprehensive validation for Phase 5 registration requirements including:
     * 1. Accompanying person restrictions (workshops and online participation)
     * 2. Student upload requirements
     * 3. Country-based payment validation
     * 4. Workshop discount eligibility
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this->validateAccompanyingPersonRestrictions($fail);
        $this->validateStudentUploadRequirements($fail);
        $this->validateCountryBasedPayment($fail);
        $this->validateWorkshopDiscountEligibility($fail);
    }

    /**
     * Validate accompanying person cannot register for workshops or online participation.
     */
    protected function validateAccompanyingPersonRestrictions(Closure $fail): void
    {
        $registrationCategory = $this->data['registration_category_snapshot'] ?? null;
        $selectedEventCodes = $this->data['selected_event_codes'] ?? [];
        $participationFormat = $this->data['participation_format'] ?? null;

        if ($registrationCategory !== 'accompanying_person') {
            return;
        }

        // Check if online participation is selected
        if ($participationFormat === 'online') {
            $fail(__('Accompanying persons cannot participate online. Only in-person participation is allowed.'));
        }

        // Check if any selected events are workshops
        if (is_array($selectedEventCodes)) {
            $workshopEvents = Event::whereIn('code', $selectedEventCodes)
                ->where('is_main_conference', false)
                ->get();

            if ($workshopEvents->isNotEmpty()) {
                $workshopNames = $workshopEvents->pluck('name')->join(', ');
                $fail(__('Accompanying persons cannot register for workshops. Workshops found: :workshops', [
                    'workshops' => $workshopNames,
                ]));
            }
        }
    }

    /**
     * Validate student enrollment proof upload requirements.
     */
    protected function validateStudentUploadRequirements(Closure $fail): void
    {
        $registrationCategory = $this->data['registration_category_snapshot'] ?? null;

        if (! in_array($registrationCategory, ['undergrad_student', 'grad_student'])) {
            return;
        }

        // For Phase 5: Both undergraduate and graduate students must upload documents
        if ($this->user) {
            $hasValidEnrollmentProof = $this->user->registration()
                ->whereHas('enrollmentProof', function ($query) {
                    $query->whereIn('status', ['pending_approval', 'approved']);
                })->exists();

            if (! $hasValidEnrollmentProof) {
                $fail(__('Phase 5: All students (undergraduate and graduate) must upload enrollment proof documents in the my-registration page before completing registration.'));
            }
        }
    }

    /**
     * Validate payment method based on country of residence.
     */
    protected function validateCountryBasedPayment(Closure $fail): void
    {
        $addressCountry = $this->data['address_country'] ?? null;
        $paymentMethod = $this->data['payment_method'] ?? null;

        if (! $addressCountry || ! $paymentMethod) {
            return;
        }

        $isBrazilianResident = $addressCountry === 'Brazil';
        $isInternationalPayment = in_array($paymentMethod, ['international_card', 'paypal', 'wire_transfer']);
        $isBrazilianPayment = in_array($paymentMethod, ['pix', 'bank_transfer_br', 'boleto']);

        if ($isBrazilianResident && $isInternationalPayment) {
            $fail(__('Brazilian residents must use domestic payment methods (PIX, Bank Transfer, or Boleto).'));
        } elseif (! $isBrazilianResident && $isBrazilianPayment) {
            $fail(__('International participants must use international payment methods (Credit Card, PayPal, or Wire Transfer).'));
        }
    }

    /**
     * Validate workshop discount eligibility.
     */
    protected function validateWorkshopDiscountEligibility(Closure $fail): void
    {
        $selectedEventCodes = $this->data['selected_event_codes'] ?? [];
        $registrationCategory = $this->data['registration_category_snapshot'] ?? null;

        if (! is_array($selectedEventCodes) || $registrationCategory === 'accompanying_person') {
            return;
        }

        $mainConferenceCode = config('fee_calculation.main_conference_code', 'BCSMIF2025');
        $isAttendingMainConference = in_array($mainConferenceCode, $selectedEventCodes);

        // Check for workshop events
        $workshopEvents = Event::whereIn('code', $selectedEventCodes)
            ->where('is_main_conference', false)
            ->get();

        // Phase 5: Workshop discount logic validation
        if ($workshopEvents->isNotEmpty() && ! $isAttendingMainConference) {
            // This is informational - workshops without main conference participation
            // The fee calculation service will handle pricing, but we can warn users
            // about potentially higher rates
        }
    }
}
