<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for the Registration model.
 */
#[CoversClass(Registration::class)]
#[Group('model')]
#[Group('registration-model')]
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registration_factory_can_create_model_instance(): void
    {
        $registration = Registration::factory()->create();

        $this->assertInstanceOf(Registration::class, $registration);
        $this->assertNotNull($registration->id, 'Registration ID should not be null after creation.');
        $this->assertNotNull($registration->user_id, 'user_id should be filled by the factory.');
        $this->assertNotNull($registration->full_name, 'full_name should be filled by the factory.');
        $this->assertNotNull($registration->email, 'email should be filled by the factory.');
        $this->assertNotNull($registration->registration_category_snapshot, 'registration_category_snapshot should be filled.');
        // calculated_fee was removed and moved to Payment model
        $this->assertNotNull($registration->payment_status, 'payment_status should be filled.');
    }

    #[Test]
    public function date_of_birth_is_casted_to_carbon_instance_or_null(): void
    {
        $dateString = '1990-01-15';
        $registrationWithDate = Registration::factory()->create(['date_of_birth' => $dateString]);
        $this->assertInstanceOf(Carbon::class, $registrationWithDate->date_of_birth);
        $this->assertEquals($dateString, $registrationWithDate->date_of_birth->toDateString());

        $registrationNullDate = Registration::factory()->create(['date_of_birth' => null]);
        $this->assertNull($registrationNullDate->date_of_birth);
    }

    #[Test]
    public function passport_expiry_date_is_casted_to_carbon_instance_or_null(): void
    {
        $dateString = '2030-12-31';
        // Ensure passport details are generated for this test case
        $registrationWithDate = Registration::factory()->state([
            'document_country_origin' => 'USA', // Non-Brazilian to trigger passport fields
            'cpf' => null,
            'rg_number' => null,
            'passport_number' => 'P123456',
            'passport_expiry_date' => $dateString,
        ])->create();
        $this->assertInstanceOf(Carbon::class, $registrationWithDate->passport_expiry_date);
        $this->assertEquals($dateString, $registrationWithDate->passport_expiry_date->toDateString());

        $registrationNullDate = Registration::factory()->create(['passport_expiry_date' => null]);
        $this->assertNull($registrationNullDate->passport_expiry_date);
    }

    #[Test]
    public function arrival_and_departure_dates_are_casted_to_carbon_instances_or_null(): void
    {
        $arrivalDateString = '2025-09-28';
        $departureDateString = '2025-10-03';
        $registrationWithDates = Registration::factory()->create([
            'arrival_date' => $arrivalDateString,
            'departure_date' => $departureDateString,
        ]);
        $this->assertInstanceOf(Carbon::class, $registrationWithDates->arrival_date);
        $this->assertEquals($arrivalDateString, $registrationWithDates->arrival_date->toDateString());
        $this->assertInstanceOf(Carbon::class, $registrationWithDates->departure_date);
        $this->assertEquals($departureDateString, $registrationWithDates->departure_date->toDateString());

        $registrationNullDates = Registration::factory()->create(['arrival_date' => null, 'departure_date' => null]);
        $this->assertNull($registrationNullDates->arrival_date);
        $this->assertNull($registrationNullDates->departure_date);
    }

    #[Test]
    public function invoice_datetime_is_casted_to_carbon_instance_or_null(): void
    {
        $invoiceSentAt = now()->subDay();

        $registrationWithDate = Registration::factory()->create([
            'invoice_sent_at' => $invoiceSentAt,
        ]);

        $this->assertInstanceOf(Carbon::class, $registrationWithDate->invoice_sent_at);
        $this->assertEquals($invoiceSentAt->toDateTimeString(), $registrationWithDate->invoice_sent_at->toDateTimeString());

        $registrationNullDate = Registration::factory()->create([
            'invoice_sent_at' => null,
        ]);
        $this->assertNull($registrationNullDate->invoice_sent_at);
    }

    #[Test]
    public function boolean_fields_are_casted_correctly(): void
    {
        $registrationTrue = Registration::factory()->create([
            'is_abe_member' => true,
            'needs_transport_from_gru' => 1,
            'needs_transport_from_usp' => true,
            'requires_visa_letter' => 1,
        ]);
        $this->assertTrue($registrationTrue->is_abe_member);
        $this->assertTrue($registrationTrue->needs_transport_from_gru);
        $this->assertTrue($registrationTrue->needs_transport_from_usp);
        $this->assertTrue($registrationTrue->requires_visa_letter);

        $registrationFalse = Registration::factory()->create([
            'is_abe_member' => false,
            'needs_transport_from_gru' => 0,
            'needs_transport_from_usp' => false,
            'requires_visa_letter' => 0,
        ]);
        $this->assertFalse($registrationFalse->is_abe_member);
        $this->assertFalse($registrationFalse->needs_transport_from_gru);
        $this->assertFalse($registrationFalse->needs_transport_from_usp);
        $this->assertFalse($registrationFalse->requires_visa_letter);

        $registrationNull = Registration::factory()->create(['is_abe_member' => null]); // Nullable boolean in DB
        $this->assertNull($registrationNull->is_abe_member); // Casts to null if DB value is null
    }

    #[Test]
    public function registration_has_payments_relationship(): void
    {
        $registration = Registration::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $registration->payments());
    }

    #[Test]
    public function registration_payments_relationship_returns_empty_collection_by_default(): void
    {
        $registration = Registration::factory()->create();

        $this->assertCount(0, $registration->payments);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $registration->payments);
    }

    #[Test]
    public function registration_can_have_associated_payments(): void
    {
        $registration = Registration::factory()->create();

        // Create a payment for this registration
        $payment = \App\Models\Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 500.00,
            'status' => 'pending',
        ]);

        $registration->refresh();

        $this->assertCount(1, $registration->payments);
        $this->assertEquals($payment->id, $registration->payments->first()->id);
        $this->assertEquals('500.00', $registration->payments->first()->amount);
        $this->assertEquals('pending', $registration->payments->first()->status);
    }

    #[Test]
    public function registration_can_have_multiple_payments(): void
    {
        $registration = Registration::factory()->create();

        // Create multiple payments for this registration
        $payment1 = \App\Models\Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 300.00,
            'status' => 'paid',
        ]);

        $payment2 = \App\Models\Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
            'status' => 'pending',
        ]);

        $registration->refresh();

        $this->assertCount(2, $registration->payments);

        $paymentIds = $registration->payments->pluck('id')->toArray();
        $this->assertContains($payment1->id, $paymentIds);
        $this->assertContains($payment2->id, $paymentIds);

        $totalAmount = $registration->payments->sum('amount');
        $this->assertEquals(500.00, $totalAmount);
    }

    #[Test]
    public function registration_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $registration->user);
        $this->assertEquals($user->id, $registration->user->id);
    }

    #[Test]
    public function all_fillable_attributes_can_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $fillableAttributes = (new Registration)->getFillable();
        $testData = Registration::factory()->make()->toArray(); // Use factory to generate a full set of data

        // Ensure user_id is set correctly from the created user
        $testData['user_id'] = $user->id;
        // Ensure email matches the user's email for consistency
        $testData['email'] = $user->email;

        // Remove attributes not in $fillable or handled by DB (id, timestamps)
        unset($testData['id'], $testData['created_at'], $testData['updated_at']);

        // Ensure all keys in testData are in fillable (or are relationships)
        $validatedData = array_intersect_key($testData, array_flip($fillableAttributes));

        $registration = Registration::create($validatedData);
        $this->assertNotNull($registration->id);

        foreach ($validatedData as $key => $value) {
            if (is_bool($value)) {
                $this->assertEquals($value, (bool) $registration->{$key});
            } elseif (($registration->getCasts()[$key] ?? null) === 'date') {
                if ($value === null) {
                    $this->assertNull($registration->{$key});
                } else {
                    $this->assertInstanceOf(Carbon::class, $registration->{$key}, "Attribute {$key} should be Carbon instance.");
                    $this->assertEquals(Carbon::parse($value)->toDateString(), $registration->{$key}->toDateString());
                }
            } elseif (($registration->getCasts()[$key] ?? null) === 'datetime') {
                if ($value === null) {
                    $this->assertNull($registration->{$key});
                } else {
                    $this->assertInstanceOf(Carbon::class, $registration->{$key}, "Attribute {$key} should be Carbon instance.");
                    $this->assertEquals(Carbon::parse($value)->toDateTimeString(), $registration->{$key}->toDateTimeString());
                }
            } elseif (in_array($key, ['amount'])) {
                // Skip amount validation as it's moved to Payment model
            } else {
                $this->assertEquals($value, $registration->{$key});
            }
        }
    }

    #[Test]
    public function registration_can_have_events_associated_with_pivot_data(): void
    {
        $registration = Registration::factory()->create();
        $event = Event::factory()->create();
        $price = 200.50;

        $registration->events()->attach($event->code, ['price_at_registration' => $price]);

        $this->assertCount(1, $registration->events);
        $this->assertTrue($registration->events->contains($event));

        $pivotData = $registration->events->first()->pivot;
        $this->assertEquals($price, (float) $pivotData->price_at_registration);
        $this->assertInstanceOf(Carbon::class, $pivotData->created_at);
        $this->assertInstanceOf(Carbon::class, $pivotData->updated_at);
    }

    #[Test]
    public function registration_can_have_multiple_events_associated(): void
    {
        $registration = Registration::factory()->create();
        $events = Event::factory()->count(2)->create();
        $prices = [100.00, 50.75];

        $attachData = [];
        foreach ($events as $index => $event) {
            $attachData[$event->code] = ['price_at_registration' => $prices[$index]];
        }
        $registration->events()->attach($attachData);

        $this->assertCount(2, $registration->events);
        foreach ($events as $index => $event) {
            $this->assertTrue($registration->events->contains($event));
            $retrievedEvent = $registration->events()->where('events.code', $event->code)->first();
            $this->assertEquals($prices[$index], (float) $retrievedEvent->pivot->price_at_registration);
        }
    }

    #[Test]
    public function event_can_be_detached_from_registration(): void
    {
        $registration = Registration::factory()->create();
        $event = Event::factory()->create();

        $registration->events()->attach($event->code, ['price_at_registration' => 75.00]);
        $this->assertCount(1, $registration->events);

        $registration->events()->detach($event->code);
        $registration->refresh();

        $this->assertCount(0, $registration->events);
    }
}
