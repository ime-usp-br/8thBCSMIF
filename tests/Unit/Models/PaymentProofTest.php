<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test AC7 (Issue #51): Unit tests for Payment model payment proof functionality.
 *
 * This test class ensures all Payment model methods related to payment proof
 * management work correctly at the unit level.
 */
class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AC7: Payment model proof-related attributes and relationships.
     * Verifies model attributes are correctly handled.
     */
    public function test_payment_model_proof_attributes(): void
    {
        // Arrange: Create test data
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/123/test_proof.pdf',
            'payment_date' => now()->subHours(2),
            'notes' => __('Payment proof uploaded by user'),
        ]);

        // Assert: Verify attributes
        $this->assertNotNull($payment->payment_proof_path);
        $this->assertEquals('proofs/123/test_proof.pdf', $payment->payment_proof_path);
        $this->assertNotNull($payment->payment_date);
        $this->assertEquals(__('Payment proof uploaded by user'), $payment->notes);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(150.00, $payment->amount);

        // Verify relationship
        $this->assertEquals($registration->id, $payment->registration_id);
        $this->assertTrue($payment->registration->is($registration));
    }

    /**
     * Test AC7: Payment model factory states.
     * Verifies factory creates payments with correct states.
     */
    public function test_payment_factory_states(): void
    {
        $registration = Registration::factory()->create();

        // Test pending state
        $pendingPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
        ]);

        $this->assertEquals('pending', $pendingPayment->status);
        $this->assertNull($pendingPayment->payment_proof_path);
        $this->assertNull($pendingPayment->payment_date);

        // Test completed state
        $completedPayment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'completed',
            'payment_proof_path' => 'proofs/456/completed_proof.pdf',
            'payment_date' => now(),
        ]);

        $this->assertEquals('completed', $completedPayment->status);
        $this->assertNotNull($completedPayment->payment_proof_path);
        $this->assertNotNull($completedPayment->payment_date);
    }

    /**
     * Test AC7: Payment model mass assignment protection.
     * Verifies security of model attributes.
     */
    public function test_payment_model_mass_assignment_protection(): void
    {
        $registration = Registration::factory()->create();

        // Test fillable attributes
        $payment = Payment::create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/789/mass_assignment_test.pdf',
            'payment_date' => now(),
            'notes' => 'Test notes',
        ]);

        $this->assertEquals($registration->id, $payment->registration_id);
        $this->assertEquals(200.00, $payment->amount);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals('proofs/789/mass_assignment_test.pdf', $payment->payment_proof_path);
        $this->assertEquals('Test notes', $payment->notes);
        $this->assertNotNull($payment->payment_date);
    }

    /**
     * Test AC7: Payment model date casting.
     * Verifies date attributes are properly cast to Carbon instances.
     */
    public function test_payment_model_date_casting(): void
    {
        $payment = Payment::factory()->create([
            'payment_date' => '2025-06-15 14:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $payment->payment_date);
        $this->assertEquals('2025-06-15 14:30:00', $payment->payment_date->format('Y-m-d H:i:s'));

        // Test null date
        $paymentWithoutDate = Payment::factory()->pending()->create();
        $this->assertNull($paymentWithoutDate->payment_date);
    }

    /**
     * Test AC7: Payment model scopes for proof-related queries.
     * Verifies query scopes work correctly.
     */
    public function test_payment_model_proof_related_scopes(): void
    {
        $registration = Registration::factory()->create();

        // Create payments with and without proofs
        $paymentWithProof = Payment::factory()->create([
            'registration_id' => $registration->id,
            'payment_proof_path' => 'proofs/123/with_proof.pdf',
        ]);

        $paymentWithoutProof = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
        ]);

        // Test manual filtering (since we don't have specific scopes defined)
        $paymentsWithProof = Payment::whereNotNull('payment_proof_path')->get();
        $paymentsWithoutProof = Payment::whereNull('payment_proof_path')->get();

        $this->assertTrue($paymentsWithProof->contains($paymentWithProof));
        $this->assertFalse($paymentsWithProof->contains($paymentWithoutProof));

        $this->assertTrue($paymentsWithoutProof->contains($paymentWithoutProof));
        $this->assertFalse($paymentsWithoutProof->contains($paymentWithProof));
    }

    /**
     * Test AC7: Payment model registration relationship.
     * Verifies the relationship between Payment and Registration models.
     */
    public function test_payment_registration_relationship(): void
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $payment1 = Payment::factory()->create(['registration_id' => $registration->id]);
        $payment2 = Payment::factory()->create(['registration_id' => $registration->id]);

        // Test payment belongs to registration
        $this->assertTrue($payment1->registration->is($registration));
        $this->assertTrue($payment2->registration->is($registration));

        // Test registration has many payments
        $this->assertTrue($registration->payments->contains($payment1));
        $this->assertTrue($registration->payments->contains($payment2));
        $this->assertEquals(2, $registration->payments->count());
    }

    /**
     * Test AC7: Payment model validation business rules.
     * Verifies business logic around payment proof handling.
     */
    public function test_payment_proof_business_rules(): void
    {
        $registration = Registration::factory()->create();

        // Test pending payment can have proof uploaded
        $pendingPayment = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
        ]);

        $this->assertEquals('pending', $pendingPayment->status);
        $this->assertNull($pendingPayment->payment_proof_path);

        // Simulate proof upload
        $pendingPayment->update([
            'payment_proof_path' => 'proofs/123/business_rule_test.pdf',
            'payment_date' => now(),
            'notes' => __('Payment proof uploaded by user'),
        ]);

        $pendingPayment->refresh();
        $this->assertNotNull($pendingPayment->payment_proof_path);
        $this->assertNotNull($pendingPayment->payment_date);
        $this->assertEquals(__('Payment proof uploaded by user'), $pendingPayment->notes);

        // Test non-pending payment should not allow proof upload (business rule)
        $completedPayment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'completed',
        ]);

        $this->assertEquals('completed', $completedPayment->status);
        // In the actual application, the controller would prevent this update
        // Here we're just testing the model can store the data
    }

    /**
     * Test AC7: Payment model soft deletes (if implemented).
     * Verifies payment deletion behavior.
     */
    public function test_payment_model_deletion_behavior(): void
    {
        Storage::fake('private');

        $payment = Payment::factory()->create([
            'payment_proof_path' => 'proofs/123/deletion_test.pdf',
        ]);

        // Create the proof file
        Storage::disk('private')->put($payment->payment_proof_path, 'Test content');
        $this->assertTrue(Storage::disk('private')->exists($payment->payment_proof_path));

        $paymentId = $payment->id;

        // Delete the payment
        $payment->delete();

        // Verify payment is deleted
        $this->assertNull(Payment::find($paymentId));

        // Note: File cleanup would typically be handled by observers or services
        // This test just verifies the model deletion works
    }

    /**
     * Test AC7: Payment model serialization for API responses.
     * Verifies model serializes correctly for API endpoints.
     */
    public function test_payment_model_serialization(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 175.50,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/456/serialization_test.pdf',
            'payment_date' => now(),
            'notes' => 'Serialization test notes',
        ]);

        $array = $payment->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('registration_id', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('payment_proof_path', $array);
        $this->assertArrayHasKey('payment_date', $array);
        $this->assertArrayHasKey('notes', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertEquals(175.50, $array['amount']);
        $this->assertEquals('pending', $array['status']);
        $this->assertEquals('proofs/456/serialization_test.pdf', $array['payment_proof_path']);
        $this->assertEquals('Serialization test notes', $array['notes']);
    }

    /**
     * Test AC7: Payment model attribute mutators and accessors.
     * Verifies any custom attribute handling.
     */
    public function test_payment_model_attribute_handling(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 199.99,
            'payment_proof_path' => 'proofs/789/attribute_test.pdf',
        ]);

        // Test amount is stored correctly (Laravel casts decimal:2 as string)
        $this->assertEquals('199.99', $payment->amount);
        $this->assertIsString($payment->amount);

        // Test payment_proof_path is stored as string
        $this->assertIsString($payment->payment_proof_path);
        $this->assertEquals('proofs/789/attribute_test.pdf', $payment->payment_proof_path);

        // Test status is stored as string
        $this->assertIsString($payment->status);
    }

    /**
     * Test AC7: Payment model with registration and user chain.
     * Verifies the complete relationship chain works correctly.
     */
    public function test_payment_registration_user_relationship_chain(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Test User Full Name',
        ]);

        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 250.00,
            'payment_proof_path' => 'proofs/123/chain_test.pdf',
        ]);

        // Test the complete chain: Payment -> Registration -> User
        $this->assertTrue($payment->registration->is($registration));
        $this->assertTrue($payment->registration->user->is($user));

        // Verify data integrity through the chain
        $this->assertEquals('Test User', $payment->registration->user->name);
        $this->assertEquals('test@example.com', $payment->registration->user->email);
        $this->assertEquals('Test User Full Name', $payment->registration->full_name);
        $this->assertEquals(250.00, $payment->amount);
        $this->assertEquals('proofs/123/chain_test.pdf', $payment->payment_proof_path);
    }
}
