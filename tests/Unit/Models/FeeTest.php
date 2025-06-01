<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Fee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testes unitários para o Model Fee.
 *
 * Verifica os casts de atributos, o relacionamento com Event,
 * e a capacidade de atribuição em massa.
 */
#[CoversClass(Fee::class)]
#[Group('model')]
#[Group('fee-model')]
class FeeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function price_is_casted_to_string_with_two_decimal_places(): void
    {
        // Test with a standard decimal value
        $feeStandard = Fee::factory()->create(['price' => 123.45]);
        $this->assertIsString($feeStandard->price, 'Price should be cast to string.');
        $this->assertEquals('123.45', $feeStandard->price, 'Price should be formatted to two decimal places.');

        // Test with zero
        $feeZero = Fee::factory()->create(['price' => 0]);
        $this->assertIsString($feeZero->price, 'Zero price should be cast to string.');
        $this->assertEquals('0.00', $feeZero->price, 'Zero price should be formatted to 0.00.');

        // Test with an integer value
        $feeInteger = Fee::factory()->create(['price' => 123]);
        $this->assertIsString($feeInteger->price, 'Integer price should be cast to string.');
        $this->assertEquals('123.00', $feeInteger->price, 'Integer price should be formatted to two decimal places.');

        // Test with a float having one decimal place
        $feeOneDecimal = Fee::factory()->create(['price' => 789.1]);
        $this->assertIsString($feeOneDecimal->price, 'Price with one decimal should be cast to string.');
        $this->assertEquals('789.10', $feeOneDecimal->price, 'Price with one decimal should be formatted to two decimal places.');

        // Test with a float having more than two decimal places (database should handle storage precision)
        // The cast 'decimal:2' applies on retrieval.
        // Assuming database stores it correctly (e.g., as 456.79 or 456.78 depending on DB type/rounding)
        // For this test, we rely on the factory generating values that are then stored and retrieved.
        // The factory's randomFloat(2, ...) already implies 2 decimal places for generation.
        // If we force a value with more, the database type (DECIMAL(8,2)) will truncate/round.
        // Let's simulate a value that would be stored as x.yz by the DB.
        $feeFromPreciseFloat = Fee::factory()->create(['price' => 456.789]); // DB will store 456.79 (typical rounding)
        $feeFromPreciseFloat->refresh(); // Ensure we get the value as stored and casted
        $this->assertIsString($feeFromPreciseFloat->price, 'Price from precise float should be cast to string.');
        $this->assertEquals('456.79', $feeFromPreciseFloat->price, 'Price from precise float should be formatted correctly after DB storage and retrieval.');
    }

    #[Test]
    public function is_discount_for_main_event_participant_is_casted_to_boolean(): void
    {
        $feeTrue = Fee::factory()->create(['is_discount_for_main_event_participant' => 1]);
        $this->assertIsBool($feeTrue->is_discount_for_main_event_participant);
        $this->assertTrue($feeTrue->is_discount_for_main_event_participant);

        $feeFalse = Fee::factory()->create(['is_discount_for_main_event_participant' => 0]);
        $this->assertIsBool($feeFalse->is_discount_for_main_event_participant);
        $this->assertFalse($feeFalse->is_discount_for_main_event_participant);

        $feeExplicitTrue = Fee::factory()->create(['is_discount_for_main_event_participant' => true]);
        $this->assertIsBool($feeExplicitTrue->is_discount_for_main_event_participant);
        $this->assertTrue($feeExplicitTrue->is_discount_for_main_event_participant);

        $feeExplicitFalse = Fee::factory()->create(['is_discount_for_main_event_participant' => false]);
        $this->assertIsBool($feeExplicitFalse->is_discount_for_main_event_participant);
        $this->assertFalse($feeExplicitFalse->is_discount_for_main_event_participant);
    }

    #[Test]
    public function fee_belongs_to_an_event(): void
    {
        $event = Event::factory()->create(['code' => 'EVENT2025']);
        $fee = Fee::factory()->create(['event_code' => $event->code]);

        $this->assertInstanceOf(Event::class, $fee->event);
        $this->assertEquals($event->id, $fee->event->id);
        $this->assertEquals('EVENT2025', $fee->event->code);
    }

    #[Test]
    public function all_fillable_attributes_can_be_mass_assigned(): void
    {
        $event = Event::factory()->create();

        $fillableAttributes = (new Fee)->getFillable();
        $testData = [];

        foreach ($fillableAttributes as $attribute) {
            match ($attribute) {
                'event_code' => $testData[$attribute] = $event->code,
                'participant_category' => $testData[$attribute] = 'student_national',
                'type' => $testData[$attribute] = 'online_live',
                'period' => $testData[$attribute] = 'late_registration',
                'price' => $testData[$attribute] = 199.99,
                'is_discount_for_main_event_participant' => $testData[$attribute] = true,
                default => $testData[$attribute] = 'test_value_for_'.$attribute,
            };
        }

        $fee = Fee::create($testData);

        $this->assertDatabaseHas('fees', ['event_code' => $event->code, 'participant_category' => 'student_national']);

        foreach ($fillableAttributes as $attribute) {
            if (is_bool($testData[$attribute])) {
                $this->assertEquals((bool) $testData[$attribute], (bool) $fee->{$attribute});
            } elseif ($attribute === 'price') {
                // Price is cast to string '199.99'
                $this->assertEquals(number_format($testData[$attribute], 2, '.', ''), $fee->{$attribute});
            } else {
                $this->assertEquals($testData[$attribute], $fee->{$attribute});
            }
        }
    }
}
