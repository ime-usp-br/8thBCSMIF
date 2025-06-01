<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Fee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fee>
 */
class FeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Fee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_code' => fn () => Event::factory()->create()->code,
            'participant_category' => $this->faker->randomElement(['undergrad_student', 'grad_student', 'professor_abe', 'professor_non_abe_professional']),
            'type' => $this->faker->randomElement(['in-person', 'online']),
            'period' => $this->faker->randomElement(['early', 'late']),
            'price' => $this->faker->randomFloat(2, 0, 2000),
            'is_discount_for_main_event_participant' => $this->faker->boolean(20), // 20% chance of being true
        ];
    }

    /**
     * Indicate that the fee is for a specific event code.
     */
    public function forEvent(string $eventCode): static
    {
        return $this->state(fn (array $attributes) => [
            'event_code' => $eventCode,
        ]);
    }

    /**
     * Indicate that the fee is for a specific participant category.
     */
    public function forParticipantCategory(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'participant_category' => $category,
        ]);
    }

    /**
     * Indicate that the fee is for a specific type of participation.
     */
    public function forType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Indicate that the fee is for a specific registration period.
     */
    public function forPeriod(string $period): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => $period,
        ]);
    }

    /**
     * Indicate that this fee is a discounted price for main event participants.
     */
    public function withDiscountForMainEventParticipant(bool $isDiscounted = true): static
    {
        return $this->state(fn (array $attributes) => [
            'is_discount_for_main_event_participant' => $isDiscounted,
        ]);
    }
}
