<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testes unitários para o Model Event.
 */
#[CoversClass(Event::class)]
#[Group('model')]
#[Group('event-model')]
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
     */
    #[Test]
    public function all_fillable_attributes_can_be_mass_assigned(): void
    {
        $fillableAttributes = (new Event)->getFillable();
        $testData = [];

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
        if (in_array('registration_deadline_late', $fillableAttributes)) {
            $testData['registration_deadline_late'] = null;
        }

        $event = Event::create($testData);
        $this->assertDatabaseHas('events', ['code' => 'TESTCODE123']);

        foreach ($fillableAttributes as $attribute) {
            if ($attribute === 'registration_deadline_late' && $testData[$attribute] === null) {
                $this->assertNull($event->{$attribute});
            } elseif (in_array($attribute, ['start_date', 'end_date', 'registration_deadline_early', 'registration_deadline_late'])) {
                $this->assertEquals(Carbon::parse($testData[$attribute])->toDateString(), $event->{$attribute}->toDateString());
            } else {
                $this->assertEquals($testData[$attribute], $event->{$attribute});
            }
        }
    }

    #[Test]
    public function event_can_have_registrations_associated_with_pivot_data(): void
    {
        $event = Event::factory()->create();
        $registration = Registration::factory()->create();
        $price = 150.75;

        $event->registrations()->attach($registration->id, ['price_at_registration' => $price]);

        $this->assertCount(1, $event->registrations);
        $this->assertTrue($event->registrations->contains($registration));

        $pivotData = $event->registrations->first()->pivot;
        $this->assertEquals($price, (float) $pivotData->price_at_registration);
        $this->assertInstanceOf(Carbon::class, $pivotData->created_at);
        $this->assertInstanceOf(Carbon::class, $pivotData->updated_at);
    }

    #[Test]
    public function event_can_have_multiple_registrations_associated(): void
    {
        $event = Event::factory()->create();
        $registrations = Registration::factory()->count(3)->create();
        $prices = [99.00, 120.50, 75.25];

        $attachData = [];
        foreach ($registrations as $index => $registration) {
            $attachData[$registration->id] = ['price_at_registration' => $prices[$index]];
        }
        $event->registrations()->attach($attachData);

        $this->assertCount(3, $event->registrations);
        foreach ($registrations as $index => $registration) {
            $this->assertTrue($event->registrations->contains($registration));
            $retrievedRegistration = $event->registrations()->where('registrations.id', $registration->id)->first();
            $this->assertEquals($prices[$index], (float) $retrievedRegistration->pivot->price_at_registration);
        }
    }

    #[Test]
    public function registration_can_be_detached_from_event(): void
    {
        $event = Event::factory()->create();
        $registration = Registration::factory()->create();

        $event->registrations()->attach($registration->id, ['price_at_registration' => 50.00]);
        $this->assertCount(1, $event->registrations);

        $event->registrations()->detach($registration->id);
        $event->refresh();

        $this->assertCount(0, $event->registrations);
    }
}