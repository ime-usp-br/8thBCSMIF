<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test; // Importa o atributo Test
use Tests\TestCase;

/**
 * Testes unitários para o Model Event.
 *
 * Verifica os casts de atributos e qualquer lógica de modelo customizada.
 * O AC8 da Issue #2 requer testes para casts e accessors/mutators simples.
 * Atualmente, apenas casts estão implementados no Model Event.
 */
class EventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o atributo start_date é corretamente convertido para uma instância Carbon.
     */
    #[Test]
    public function start_date_is_casted_to_carbon_instance(): void
    {
        $dateString = '2025-09-28';
        $event = Event::factory()->create(['start_date' => $dateString]);

        $this->assertInstanceOf(Carbon::class, $event->start_date);
        $this->assertEquals($dateString, $event->start_date->toDateString());
    }

    /**
     * Testa se o atributo end_date é corretamente convertido para uma instância Carbon.
     */
    #[Test]
    public function end_date_is_casted_to_carbon_instance(): void
    {
        $dateString = '2025-10-03';
        $event = Event::factory()->create(['end_date' => $dateString]);

        $this->assertInstanceOf(Carbon::class, $event->end_date);
        $this->assertEquals($dateString, $event->end_date->toDateString());
    }

    /**
     * Testa se o atributo registration_deadline_early é corretamente convertido para Carbon.
     */
    #[Test]
    public function registration_deadline_early_is_casted_to_carbon_instance(): void
    {
        $dateString = '2025-08-15';
        $event = Event::factory()->create(['registration_deadline_early' => $dateString]);

        $this->assertInstanceOf(Carbon::class, $event->registration_deadline_early);
        $this->assertEquals($dateString, $event->registration_deadline_early->toDateString());
    }

    /**
     * Testa se o atributo registration_deadline_late é corretamente convertido para Carbon
     * e lida com valores nulos.
     */
    #[Test]
    public function registration_deadline_late_is_casted_to_carbon_instance_or_null(): void
    {
        $dateString = '2025-09-15';
        $eventWithDate = Event::factory()->create(['registration_deadline_late' => $dateString]);

        $this->assertInstanceOf(Carbon::class, $eventWithDate->registration_deadline_late);
        $this->assertEquals($dateString, $eventWithDate->registration_deadline_late->toDateString());

        $eventNullDate = Event::factory()->create(['registration_deadline_late' => null]);
        $this->assertNull($eventNullDate->registration_deadline_late);
    }

    /**
     * Testa se o atributo is_main_conference é corretamente convertido para booleano.
     */
    #[Test]
    public function is_main_conference_is_casted_to_boolean(): void
    {
        $eventTrue = Event::factory()->create(['is_main_conference' => true]);
        $this->assertIsBool($eventTrue->is_main_conference);
        $this->assertTrue($eventTrue->is_main_conference);
        $eventTrue->refresh(); // Garante que o valor foi persistido e recarregado corretamente
        $this->assertTrue($eventTrue->is_main_conference);

        $eventFalse = Event::factory()->create(['is_main_conference' => false]);
        $this->assertIsBool($eventFalse->is_main_conference);
        $this->assertFalse($eventFalse->is_main_conference);
        $eventFalse->refresh();
        $this->assertFalse($eventFalse->is_main_conference);

        // Test with integer values that should cast to boolean
        $eventOne = Event::factory()->create(['is_main_conference' => 1]);
        $this->assertIsBool($eventOne->is_main_conference);
        $this->assertTrue($eventOne->is_main_conference);
        $eventOne->refresh();
        $this->assertTrue($eventOne->is_main_conference);

        $eventZero = Event::factory()->create(['is_main_conference' => 0]);
        $this->assertIsBool($eventZero->is_main_conference);
        $this->assertFalse($eventZero->is_main_conference);
        $eventZero->refresh();
        $this->assertFalse($eventZero->is_main_conference);
    }

    /**
     * Testa se todos os campos definidos em $fillable podem ser atribuídos em massa.
     * Isso é testado indiretamente pela factory, mas um teste explícito pode ser útil.
     */
    #[Test]
    public function all_fillable_attributes_can_be_mass_assigned(): void
    {
        $fillableAttributes = (new Event)->getFillable();
        $testData = [];

        // Gera dados de teste com base nos atributos fillable
        // Adapta a lógica para os tipos esperados de cada campo
        foreach ($fillableAttributes as $attribute) {
            match ($attribute) {
                'code' => $testData[$attribute] = 'TESTCODE123',
                'name' => $testData[$attribute] = 'Test Event Name',
                'description' => $testData[$attribute] = 'Test description.',
                'start_date', 'end_date', 'registration_deadline_early', 'registration_deadline_late' => $testData[$attribute] = now()->addDays(rand(1, 30))->toDateString(),
                'location' => $testData[$attribute] = 'Test Location',
                'is_main_conference' => $testData[$attribute] = true,
                default => $testData[$attribute] = 'test_value'
            };
        }
        // Trata o caso de registration_deadline_late ser nullable
        if (in_array('registration_deadline_late', $fillableAttributes)) {
            $testData['registration_deadline_late'] = null;
        }

        $event = Event::create($testData);
        $this->assertDatabaseHas('events', ['code' => 'TESTCODE123']);

        foreach ($fillableAttributes as $attribute) {
            if ($attribute === 'registration_deadline_late' && $testData[$attribute] === null) {
                $this->assertNull($event->{$attribute});
            } elseif (in_array($attribute, ['start_date', 'end_date', 'registration_deadline_early', 'registration_deadline_late'])) {
                // Comparação de datas como objetos Carbon
                $this->assertEquals(Carbon::parse($testData[$attribute])->toDateString(), $event->{$attribute}->toDateString());
            } else {
                $this->assertEquals($testData[$attribute], $event->{$attribute});
            }
        }
    }
}
