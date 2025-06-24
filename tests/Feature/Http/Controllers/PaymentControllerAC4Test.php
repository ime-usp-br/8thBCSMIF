<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentControllerAC4Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AC4 (Issue #51): A "View Proof" link/button appears for payments
     * that already have a proof uploaded.
     *
     * This test specifically addresses AC4 requirements for Issue #51.
     */
    public function test_ac4_view_proof_button_appears_for_payments_with_uploaded_proof(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        // Payment with proof uploaded
        $paymentWithProof = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/123/test_proof.pdf',
            'payment_date' => now(),
        ]);

        // Payment without proof uploaded
        $paymentWithoutProof = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
        ]);

        // Act: Load the my-registration page
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Assert: Verify UI state
        $response->assertOk();

        // AC4 VERIFICATION: Payment with proof should show "View Proof" button
        $this->assertStringContainsString('payments/'.$paymentWithProof->id.'/download-proof', $content);
        $this->assertStringContainsString('view-payment-proof-button-'.$paymentWithProof->id, $content);
        $this->assertStringContainsString(__('View Proof'), $content);

        // Verify the success confirmation is also shown
        $this->assertStringContainsString(__('Payment proof uploaded successfully'), $content);

        // Payment without proof should NOT show "View Proof" button
        $this->assertStringNotContainsString('view-payment-proof-button-'.$paymentWithoutProof->id, $content);

        // But should show upload form instead
        $this->assertStringContainsString('payments/'.$paymentWithoutProof->id.'/upload-proof', $content);
        $this->assertStringContainsString('payment_proof_'.$paymentWithoutProof->id, $content);
    }

    /**
     * Test AC4 (Issue #51): Verify multiple payments with proofs show multiple "View Proof" buttons.
     *
     * This test ensures the AC4 functionality works correctly with multiple payments.
     */
    public function test_ac4_multiple_payments_with_proofs_show_multiple_view_buttons(): void
    {
        // Arrange: Create test data with multiple payments with proofs
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        // Multiple payments with proofs uploaded
        $payment1 = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 100.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/123/proof1.pdf',
            'payment_date' => now()->subHours(2),
        ]);

        $payment2 = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/123/proof2.pdf',
            'payment_date' => now()->subHours(1),
        ]);

        $payment3 = Payment::factory()->pending()->create([
            'registration_id' => $registration->id,
            'amount' => 75.00,
        ]);

        // Act: Load the my-registration page
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Assert: Verify all payments with proofs show "View Proof" buttons
        $response->assertOk();

        // AC4 VERIFICATION: Both payments with proofs should show "View Proof" buttons
        $this->assertStringContainsString('payments/'.$payment1->id.'/download-proof', $content);
        $this->assertStringContainsString('view-payment-proof-button-'.$payment1->id, $content);

        $this->assertStringContainsString('payments/'.$payment2->id.'/download-proof', $content);
        $this->assertStringContainsString('view-payment-proof-button-'.$payment2->id, $content);

        // Payment without proof should show upload form, not view button
        $this->assertStringNotContainsString('view-payment-proof-button-'.$payment3->id, $content);
        $this->assertStringContainsString('payments/'.$payment3->id.'/upload-proof', $content);

        // Verify correct count of "View Proof" buttons
        $viewProofCount = substr_count($content, __('View Proof'));
        $this->assertEquals(2, $viewProofCount, 'Should have exactly 2 "View Proof" buttons for payments with proofs');

        // Verify both success messages are shown
        $successCount = substr_count($content, __('Payment proof uploaded successfully'));
        $this->assertEquals(2, $successCount, 'Should have exactly 2 success confirmation messages');
    }

    /**
     * Test AC4 (Issue #51): Verify "View Proof" button styling and structure.
     *
     * This test ensures the button has the correct visual styling and structure.
     */
    public function test_ac4_view_proof_button_styling_and_structure(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 200.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/123/styled_proof.pdf',
            'payment_date' => now(),
        ]);

        // Act: Load the my-registration page
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Assert: Verify button styling and structure
        $response->assertOk();

        // AC4 VERIFICATION: Button should have correct CSS classes for green styling
        $this->assertStringContainsString('bg-green-600', $content);
        $this->assertStringContainsString('hover:bg-green-700', $content);
        $this->assertStringContainsString('text-white', $content);
        $this->assertStringContainsString('uppercase', $content);

        // Button should be an anchor tag (link) not a form submission
        $this->assertStringContainsString('<a href="', $content);
        $this->assertStringContainsString('/payments/'.$payment->id.'/download-proof', $content);

        // Button should have an eye icon (view icon)
        $this->assertStringContainsString('viewBox="0 0 24 24"', $content);
        $this->assertStringContainsString('M15 12a3 3 0 11-6 0', $content); // Eye icon path

        // Button should have the dusk attribute for testing
        $this->assertStringContainsString('dusk="view-payment-proof-button-'.$payment->id.'"', $content);
    }

    /**
     * Test AC4 (Issue #51): Verify layout structure with "View Proof" button.
     *
     * This test ensures the button is positioned correctly within the layout.
     */
    public function test_ac4_view_proof_button_layout_structure(): void
    {
        // Arrange: Create test data
        Storage::fake('private');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'document_country_origin' => 'Brasil',
        ]);

        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 125.00,
            'status' => 'pending',
            'payment_proof_path' => 'proofs/123/layout_test_proof.pdf',
            'payment_date' => now(),
        ]);

        // Act: Load the my-registration page
        $response = $this->actingAs($user)->get('/my-registration');
        $content = $response->getContent();

        // Assert: Verify layout structure
        $response->assertOk();

        // AC4 VERIFICATION: Button should be within the green confirmation section
        $this->assertStringContainsString('bg-green-50', $content);
        $this->assertStringContainsString('border-green-200', $content);

        // Button should be positioned to the right (justify-between layout)
        $this->assertStringContainsString('justify-between', $content);

        // Success message and button should be on the same line
        $this->assertStringContainsString(__('Payment proof uploaded successfully'), $content);

        // Date information should be below the main line
        $this->assertStringContainsString(__('Uploaded on'), $content);
        $this->assertStringContainsString($payment->payment_date->format('d/m/Y H:i'), $content);

        // Verify the overall structure: div > flex > (message + button) + date
        $this->assertMatchesRegularExpression(
            '/bg-green-50.*?justify-between.*?Payment proof uploaded successfully.*?View Proof.*?Uploaded on/s',
            $content
        );
    }
}
