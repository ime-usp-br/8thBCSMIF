<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for the Payment model.
 */
#[CoversClass(Payment::class)]
#[Group('model')]
#[Group('payment-model')]
class PaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function payment_factory_can_create_model_instance(): void
    {
        $payment = Payment::factory()->create();

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertNotNull($payment->id, 'Payment ID should not be null after creation.');
        $this->assertNotNull($payment->registration_id, 'registration_id should be filled by the factory.');
        $this->assertNotNull($payment->amount, 'amount should be filled by the factory.');
        $this->assertNotNull($payment->status, 'status should be filled by the factory.');
    }

    #[Test]
    public function amount_is_casted_to_decimal_string(): void
    {
        $payment = Payment::factory()->create(['amount' => 123.45]);
        $this->assertIsString($payment->amount);
        $this->assertEquals('123.45', $payment->amount);

        $paymentInteger = Payment::factory()->create(['amount' => 100]);
        $this->assertIsString($paymentInteger->amount);
        $this->assertEquals('100.00', $paymentInteger->amount);

        $paymentZero = Payment::factory()->create(['amount' => 0]);
        $this->assertIsString($paymentZero->amount);
        $this->assertEquals('0.00', $paymentZero->amount);
    }

    #[Test]
    public function payment_date_is_casted_to_carbon_instance_or_null(): void
    {
        $dateTime = now();
        $paymentWithDate = Payment::factory()->create(['payment_date' => $dateTime]);
        $this->assertInstanceOf(Carbon::class, $paymentWithDate->payment_date);
        $this->assertEquals($dateTime->toDateTimeString(), $paymentWithDate->payment_date->toDateTimeString());

        $paymentNullDate = Payment::factory()->create(['payment_date' => null]);
        $this->assertNull($paymentNullDate->payment_date);
    }

    #[Test]
    public function payment_belongs_to_a_registration(): void
    {
        $registration = Registration::factory()->create();
        $payment = Payment::factory()->create(['registration_id' => $registration->id]);

        $this->assertInstanceOf(Registration::class, $payment->registration);
        $this->assertEquals($registration->id, $payment->registration->id);
    }

    #[Test]
    public function all_fillable_attributes_can_be_mass_assigned(): void
    {
        $registration = Registration::factory()->create();
        $fillableAttributes = (new Payment)->getFillable();
        $testData = Payment::factory()->make(['registration_id' => $registration->id])->toArray();

        // Remove attributes not in $fillable or handled by DB (id, timestamps)
        unset($testData['id'], $testData['created_at'], $testData['updated_at']);

        // Ensure all keys in testData are in fillable
        $validatedData = array_intersect_key($testData, array_flip($fillableAttributes));

        $payment = Payment::create($validatedData);
        $this->assertNotNull($payment->id);

        foreach ($validatedData as $key => $value) {
            if (($payment->getCasts()[$key] ?? null) === 'datetime') {
                if ($value === null) {
                    $this->assertNull($payment->{$key});
                } else {
                    $this->assertInstanceOf(Carbon::class, $payment->{$key}, "Attribute {$key} should be Carbon instance.");
                    $this->assertEquals(Carbon::parse($value)->toDateTimeString(), $payment->{$key}->toDateTimeString());
                }
            } elseif ($key === 'amount') {
                $this->assertEquals(number_format((float) $value, 2, '.', ''), $payment->{$key});
            } else {
                $this->assertEquals($value, $payment->{$key});
            }
        }
    }

    #[Test]
    public function payment_status_can_be_set_to_valid_values(): void
    {
        $validStatuses = ['pending', 'paid', 'pending_approval', 'cancelled'];

        foreach ($validStatuses as $status) {
            $payment = Payment::factory()->create(['status' => $status]);
            $this->assertEquals($status, $payment->status);
        }
    }

    #[Test]
    public function payment_proof_path_can_be_null_or_string(): void
    {
        $paymentWithProof = Payment::factory()->create(['payment_proof_path' => 'uploads/payments/proof.pdf']);
        $this->assertEquals('uploads/payments/proof.pdf', $paymentWithProof->payment_proof_path);

        $paymentWithoutProof = Payment::factory()->create(['payment_proof_path' => null]);
        $this->assertNull($paymentWithoutProof->payment_proof_path);
    }

    #[Test]
    public function notes_can_be_null_or_string(): void
    {
        $paymentWithNotes = Payment::factory()->create(['notes' => 'Payment confirmed by admin']);
        $this->assertEquals('Payment confirmed by admin', $paymentWithNotes->notes);

        $paymentWithoutNotes = Payment::factory()->create(['notes' => null]);
        $this->assertNull($paymentWithoutNotes->notes);
    }
}
