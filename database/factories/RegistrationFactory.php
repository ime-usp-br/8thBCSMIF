<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Registration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $position = $this->faker->randomElement(['undergrad_student', 'grad_student', 'researcher', 'professor', 'professional', 'other']);
        $isBrazilian = $this->faker->boolean(70); // 70% chance of being Brazilian for testing variety

        return [
            'user_id' => $user->id,
            'full_name' => $user->name,
            'nationality' => $isBrazilian ? 'Brazilian' : $this->faker->country(),
            'date_of_birth' => $this->faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other', 'prefer_not_to_say']),

            'document_country_origin' => $isBrazilian ? 'Brazil' : $this->faker->countryCode(),
            'cpf' => $isBrazilian ? $this->faker->numerify('###########') : null,
            'rg_number' => $isBrazilian ? $this->faker->numerify('##########') : null,
            'passport_number' => ! $isBrazilian ? $this->faker->bothify('??#######') : null,
            'passport_expiry_date' => ! $isBrazilian ? Carbon::instance($this->faker->dateTimeBetween('+1 year', '+10 years'))->format('Y-m-d') : null,

            'email' => $user->email,
            'phone_number' => $this->faker->phoneNumber(),
            'address_street' => $this->faker->streetAddress(),
            'address_city' => $this->faker->city(),
            'address_state_province' => $this->faker->state(),
            'address_country' => $isBrazilian ? 'Brazil' : $this->faker->country(),
            'address_postal_code' => $this->faker->postcode(),

            'affiliation' => $this->faker->company(),
            'position' => $position,
            'is_abe_member' => $this->faker->boolean(20),

            'arrival_date' => Carbon::instance($this->faker->dateTimeBetween('+1 month', '+2 months'))->format('Y-m-d'),
            'departure_date' => function (array $attributes) {
                return Carbon::parse($attributes['arrival_date'])->addDays($this->faker->numberBetween(1, 7))->format('Y-m-d');
            },
            'participation_format' => $this->faker->randomElement(['in-person', 'online']),
            'needs_transport_from_gru' => $this->faker->boolean(10),
            'needs_transport_from_usp' => $this->faker->boolean(15),

            'dietary_restrictions' => $this->faker->randomElement(['none', 'vegetarian', 'vegan', 'gluten-free', 'other']),
            'other_dietary_restrictions' => function (array $attributes) {
                return $attributes['dietary_restrictions'] === 'other' ? $this->faker->sentence() : null;
            },

            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_relationship' => $this->faker->randomElement(['parent', 'sibling', 'spouse', 'friend']),
            'emergency_contact_phone' => $this->faker->phoneNumber(),

            'requires_visa_letter' => ! $isBrazilian && $this->faker->boolean(30),

            'registration_category_snapshot' => $position,
            'payment_status' => $this->faker->randomElement(['pending_payment', 'pending_br_proof_approval', 'paid_br', 'invoice_sent_int', 'paid_int', 'free', 'cancelled']),
            'invoice_sent_at' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
