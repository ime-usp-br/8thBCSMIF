<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::instance($this->faker->dateTimeBetween('+1 month', '+2 months'));
        $endDate = $startDate->copy()->addDays($this->faker->numberBetween(1, 5));

        return [
            'code' => strtoupper($this->faker->unique()->bothify('EVT###??')),
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => $this->faker->city.', '.$this->faker->stateAbbr,
            'registration_deadline_early' => $startDate->copy()->subWeeks(2),
            'registration_deadline_late' => $startDate->copy()->subWeek(),
            'is_main_conference' => $this->faker->boolean(30), // 30% chance of being main conference
        ];
    }

    /**
     * Indicate that the event is the main conference.
     */
    public function mainConference(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_main_conference' => true,
            ];
        });
    }

    /**
     * Indicate that the event is a workshop (not the main conference).
     */
    public function workshop(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_main_conference' => false,
            ];
        });
    }
}
