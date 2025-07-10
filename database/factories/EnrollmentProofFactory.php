<?php

namespace Database\Factories;

use App\Models\EnrollmentProof;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnrollmentProof>
 */
class EnrollmentProofFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EnrollmentProof::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'file_path' => $this->faker->optional(0.7)->filePath(),
            'original_filename' => $this->faker->optional(0.7)->word().'.pdf',
            'uploaded_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['pending_approval', 'approved', 'rejected']),
            'approved_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'approved_by' => $this->faker->optional(0.3)->randomElement([User::factory(), null]),
            'rejection_reason' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the enrollment proof is pending approval.
     */
    public function pendingApproval(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_approval',
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the enrollment proof is approved.
     */
    public function approved(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'approved_by' => User::factory(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the enrollment proof is rejected.
     */
    public function rejected(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_at' => null,
            'approved_by' => User::factory(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }
}
