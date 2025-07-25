<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class CountryBasedPaymentValidation implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

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
     * Validates payment method requirements based on participant's country of residence.
     * Brazilian residents must use PIX/Bank Transfer, while foreign participants
     * must use international payment methods.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get the address country from form data
        $addressCountry = $this->data['address_country'] ?? null;
        $paymentMethod = $value;

        if (! $addressCountry || ! $paymentMethod) {
            return; // Skip validation if required data is missing
        }

        $isBrazilianResident = $addressCountry === 'Brazil';
        $isInternationalPayment = in_array($paymentMethod, ['international_card', 'paypal', 'wire_transfer']);
        $isBrazilianPayment = in_array($paymentMethod, ['pix', 'bank_transfer_br', 'boleto']);

        // Validate payment method based on country
        if ($isBrazilianResident && $isInternationalPayment) {
            $fail(__('Brazilian residents must use domestic payment methods (PIX, Bank Transfer, or Boleto).'));
        } elseif (! $isBrazilianResident && $isBrazilianPayment) {
            $fail(__('International participants must use international payment methods (Credit Card, PayPal, or Wire Transfer).'));
        }
    }
}
