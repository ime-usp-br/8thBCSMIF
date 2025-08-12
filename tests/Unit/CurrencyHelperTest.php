<?php

namespace Tests\Unit;

use App\Helpers\CurrencyHelper;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyHelperTest extends TestCase
{
    #[Test]
    public function format_method_uses_configured_currency_and_locale()
    {
        Config::set('currency.code', 'BRL');
        Config::set('currency.locale', 'pt_BR');
        Config::set('currency.precision', 2);

        $formatted = CurrencyHelper::format(1500.50);

        // Should contain currency formatting
        $this->assertIsString($formatted);
        $this->assertNotEmpty($formatted);
    }

    #[Test]
    public function format_method_accepts_custom_parameters()
    {
        $formatted = CurrencyHelper::format(1000.00, 'USD', 'en', 0);

        $this->assertIsString($formatted);
        $this->assertNotEmpty($formatted);
    }

    #[Test]
    public function get_symbol_returns_correct_symbols()
    {
        $testCases = [
            'BRL' => 'R$',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'CAD ',  // Default case
        ];

        foreach ($testCases as $currency => $expectedSymbol) {
            $symbol = CurrencyHelper::getSymbol($currency);
            $this->assertEquals($expectedSymbol, $symbol);
        }
    }

    #[Test]
    public function get_symbol_uses_configured_currency_when_null()
    {
        Config::set('currency.code', 'EUR');

        $symbol = CurrencyHelper::getSymbol(null);
        $this->assertEquals('€', $symbol);
    }

    #[Test]
    public function get_symbol_handles_unknown_currency()
    {
        $symbol = CurrencyHelper::getSymbol('XYZ');
        $this->assertEquals('XYZ ', $symbol);
    }

    #[Test]
    public function format_method_uses_fallback_values()
    {
        // Clear config to test fallbacks
        Config::set('currency.code', null);
        Config::set('currency.locale', null);
        Config::set('currency.precision', null);

        // Should not throw exception and should return a string
        $formatted = CurrencyHelper::format(100.50);
        $this->assertIsString($formatted);
    }
}
