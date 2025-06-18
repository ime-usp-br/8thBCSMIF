<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payment_reference' => Payment::generatePaymentReference(),
            'payment_method' => fake()->randomElement(['bank_transfer', 'international_invoice']),
            'payment_status' => fake()->randomElement(['pending_payment', 'paid_br', 'paid_international']),
            'total_amount' => fake()->randomFloat(2, 50, 1000),
            'payment_proof_path' => fake()->optional()->filePath(),
            'payment_uploaded_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'invoice_sent_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the payment is paid (Brazilian).
     */
    public function paidBr(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid_br',
            'payment_method' => 'bank_transfer',
            'payment_uploaded_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the payment is paid (International).
     */
    public function paidInternational(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid_international',
            'payment_method' => 'international_invoice',
            'invoice_sent_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'pending_payment',
            'payment_uploaded_at' => null,
            'invoice_sent_at' => null,
        ]);
    }
}
