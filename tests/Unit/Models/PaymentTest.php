<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_belongs_to_user()
    {
        $user = User::factory()->create();
        $payment = Payment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $payment->user);
        $this->assertEquals($user->id, $payment->user->id);
    }

    public function test_payment_belongs_to_many_events()
    {
        $payment = Payment::factory()->create();
        $event = Event::factory()->create();
        $registration = Registration::factory()->create();

        $payment->events()->attach($event->code, [
            'individual_price' => 100.00,
            'registration_id' => $registration->id,
        ]);

        $this->assertCount(1, $payment->events);
        $this->assertEquals($event->code, $payment->events->first()->code);
        $this->assertEquals(100.00, $payment->events->first()->pivot->individual_price);
    }

    public function test_generate_payment_reference()
    {
        $reference = Payment::generatePaymentReference();

        $this->assertStringStartsWith('PAY-', $reference);
        $this->assertStringContainsString(now()->format('Ymd'), $reference);
        $this->assertEquals(19, strlen($reference)); // PAY- + YYYYMMDD + - + 6 chars (total: 4 + 8 + 1 + 6 = 19)
    }

    public function test_is_paid_method()
    {
        $paidPayment = Payment::factory()->paidBr()->create();
        $pendingPayment = Payment::factory()->pending()->create();

        $this->assertTrue($paidPayment->isPaid());
        $this->assertFalse($pendingPayment->isPaid());
    }

    public function test_is_pending_method()
    {
        $paidPayment = Payment::factory()->paidBr()->create();
        $pendingPayment = Payment::factory()->pending()->create();

        $this->assertFalse($paidPayment->isPending());
        $this->assertTrue($pendingPayment->isPending());
    }

    public function test_payment_factory_states()
    {
        $paidBr = Payment::factory()->paidBr()->create();
        $paidInternational = Payment::factory()->paidInternational()->create();
        $pending = Payment::factory()->pending()->create();

        $this->assertEquals('paid_br', $paidBr->payment_status);
        $this->assertEquals('bank_transfer', $paidBr->payment_method);
        $this->assertNotNull($paidBr->payment_uploaded_at);

        $this->assertEquals('paid_international', $paidInternational->payment_status);
        $this->assertEquals('international_invoice', $paidInternational->payment_method);
        $this->assertNotNull($paidInternational->invoice_sent_at);

        $this->assertEquals('pending_payment', $pending->payment_status);
        $this->assertNull($pending->payment_uploaded_at);
        $this->assertNull($pending->invoice_sent_at);
    }

    public function test_total_amount_cast_to_decimal()
    {
        $payment = Payment::factory()->create(['total_amount' => 123.45]);

        $this->assertIsString((string) $payment->total_amount);
        $this->assertEquals('123.45', $payment->total_amount);
    }

    public function test_user_has_many_payments()
    {
        $user = User::factory()->create();
        $payment1 = Payment::factory()->create(['user_id' => $user->id]);
        $payment2 = Payment::factory()->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->payments);
        $this->assertTrue($user->payments->contains($payment1));
        $this->assertTrue($user->payments->contains($payment2));
    }

    public function test_event_belongs_to_many_payments()
    {
        $event = Event::factory()->create();
        $payment1 = Payment::factory()->create();
        $payment2 = Payment::factory()->create();
        $registration = Registration::factory()->create();

        $event->payments()->attach($payment1->id, [
            'individual_price' => 50.00,
            'registration_id' => $registration->id,
        ]);

        $event->payments()->attach($payment2->id, [
            'individual_price' => 75.00,
            'registration_id' => $registration->id,
        ]);

        $this->assertCount(2, $event->payments);
        $this->assertTrue($event->payments->contains($payment1));
        $this->assertTrue($event->payments->contains($payment2));
    }
}
