<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'amount' => $this->faker->randomFloat(2, 0, 2000),
            'status' => $this->faker->randomElement(['pending', 'paid', 'pending_approval', 'cancelled']),
            'payment_proof_path' => $this->faker->optional(0.3)->filePath(),
            'payment_date' => $this->faker->optional(0.5)->dateTimeBetween('-1 month', 'now'),
            'notes' => $this->faker->optional(0.4)->sentence(),
        ];
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_date' => null,
            'payment_proof_path' => null,
        ]);
    }

    /**
     * Indicate that the payment is paid.
     */
    public function paid(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'payment_proof_path' => $this->faker->filePath(),
        ]);
    }

    /**
     * Indicate that the payment is pending approval.
     */
    public function pendingApproval(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_approval',
            'payment_date' => null,
            'payment_proof_path' => $this->faker->filePath(),
        ]);
    }

    /**
     * Indicate that the payment is cancelled.
     */
    public function cancelled(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_date' => null,
            'payment_proof_path' => null,
        ]);
    }
}
