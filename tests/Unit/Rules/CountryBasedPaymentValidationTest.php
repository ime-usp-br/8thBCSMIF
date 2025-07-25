<?php

namespace Tests\Unit\Rules;

use App\Rules\CountryBasedPaymentValidation;
use Tests\TestCase;

class CountryBasedPaymentValidationTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_brazilian_resident_uses_international_payment(): void
    {
        $rule = new CountryBasedPaymentValidation;
        $rule->setData([
            'address_country' => 'Brazil',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('payment_method', 'paypal', function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('Brazilian residents must use domestic payment methods', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_international_participant_uses_brazilian_payment(): void
    {
        $rule = new CountryBasedPaymentValidation;
        $rule->setData([
            'address_country' => 'United States',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('payment_method', 'pix', function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('International participants must use international payment methods', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_when_brazilian_resident_uses_domestic_payment(): void
    {
        $rule = new CountryBasedPaymentValidation;
        $rule->setData([
            'address_country' => 'Brazil',
        ]);

        $failed = false;

        $rule->validate('payment_method', 'pix', function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_when_international_participant_uses_international_payment(): void
    {
        $rule = new CountryBasedPaymentValidation;
        $rule->setData([
            'address_country' => 'Canada',
        ]);

        $failed = false;

        $rule->validate('payment_method', 'international_card', function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_validation_when_address_country_is_missing(): void
    {
        $rule = new CountryBasedPaymentValidation;
        $rule->setData([]);

        $failed = false;

        $rule->validate('payment_method', 'paypal', function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_validation_when_payment_method_is_missing(): void
    {
        $rule = new CountryBasedPaymentValidation;
        $rule->setData([
            'address_country' => 'Brazil',
        ]);

        $failed = false;

        $rule->validate('payment_method', null, function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_all_brazilian_payment_methods(): void
    {
        $rule = new CountryBasedPaymentValidation;
        $rule->setData([
            'address_country' => 'Brazil',
        ]);

        $brazilianPaymentMethods = ['pix', 'bank_transfer_br', 'boleto'];

        foreach ($brazilianPaymentMethods as $paymentMethod) {
            $failed = false;

            $rule->validate('payment_method', $paymentMethod, function ($message) use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Payment method {$paymentMethod} should be valid for Brazilian residents");
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_all_international_payment_methods(): void
    {
        $rule = new CountryBasedPaymentValidation;
        $rule->setData([
            'address_country' => 'Germany',
        ]);

        $internationalPaymentMethods = ['international_card', 'paypal', 'wire_transfer'];

        foreach ($internationalPaymentMethods as $paymentMethod) {
            $failed = false;

            $rule->validate('payment_method', $paymentMethod, function ($message) use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Payment method {$paymentMethod} should be valid for international participants");
        }
    }
}
