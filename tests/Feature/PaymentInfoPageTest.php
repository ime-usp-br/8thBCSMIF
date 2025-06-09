<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentInfoPageTest extends TestCase
{
    /**
     * Test that the payment info page loads successfully.
     */
    public function test_payment_info_page_returns_successful_response(): void
    {
        $response = $this->get('/payment-info');

        $response->assertStatus(200);
    }

    /**
     * Test that the payment info page contains payment information titles and key content.
     */
    public function test_payment_info_page_contains_payment_information(): void
    {
        $response = $this->get('/payment-info');

        $response->assertStatus(200);
        $response->assertSee('Payment Information');
        $response->assertSee('Payment instructions and banking details');
        $response->assertSee('For Brazilian Participants');
        $response->assertSee('For International Participants');
    }

    /**
     * Test that the payment info page contains Brazilian bank details.
     */
    public function test_payment_info_page_contains_brazilian_bank_details(): void
    {
        $response = $this->get('/payment-info');

        $response->assertStatus(200);
        $response->assertSee('Bank Details');
        $response->assertSee('Banco Santander');
        $response->assertSee('0658');
        $response->assertSee('13006798-9');
        $response->assertSee('Associação Brasileira de Estatística');
        $response->assertSee('56.572.456/0001-80');
    }

    /**
     * Test that the payment info page contains payment instructions.
     */
    public function test_payment_info_page_contains_payment_instructions(): void
    {
        $response = $this->get('/payment-info');

        $response->assertStatus(200);
        $response->assertSee('Payment via bank transfer or PIX');
        $response->assertSee('Step 1: Make Payment');
        $response->assertSee('Step 2: Upload Proof');
        $response->assertSee('Step 3: Confirmation');
    }

    /**
     * Test that the payment info page contains international payment information.
     */
    public function test_payment_info_page_contains_international_payment_info(): void
    {
        $response = $this->get('/payment-info');

        $response->assertStatus(200);
        $response->assertSee('Invoice-based payment system');
        $response->assertSee('International participants will receive an invoice with payment details');
        $response->assertSee('Complete Registration');
        $response->assertSee('Receive Invoice');
        $response->assertSee('Process Payment');
    }

    /**
     * Test that the payment info page contains payment methods.
     */
    public function test_payment_info_page_contains_payment_methods(): void
    {
        $response = $this->get('/payment-info');

        $response->assertStatus(200);
        $response->assertSee('International wire transfer');
        $response->assertSee('Credit card payment');
        $response->assertSee('PayPal (where available)');
    }

    /**
     * Test that the payment info page has proper title.
     */
    public function test_payment_info_page_has_proper_title(): void
    {
        $response = $this->get('/payment-info');

        $response->assertStatus(200);
        $response->assertSee('Payment Information', false);
    }

    /**
     * Test that the payment info page contains processing time information.
     */
    public function test_payment_info_page_contains_processing_time_info(): void
    {
        $response = $this->get('/payment-info');

        $response->assertStatus(200);
        $response->assertSee('Processing Time');
        $response->assertSee('Invoices are typically sent within 24-48 hours');
    }
}
