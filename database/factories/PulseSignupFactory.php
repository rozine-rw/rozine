<?php

namespace Database\Factories;

use App\Enums\PulseContactMethod;
use App\Enums\PulseSignupType;
use App\Models\PulseSignup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PulseSignup>
 */
class PulseSignupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var array<string, list<string>> $districts */
        $districts = config('rwanda.districts');
        $province = fake()->randomElement(array_keys($districts));

        return [
            'type' => PulseSignupType::Investor,
            'name' => fake()->name(),
            'contact_method' => PulseContactMethod::Email,
            'contact' => fake()->unique()->safeEmail(),
            'province' => $province,
            'district' => fake()->randomElement($districts[$province]),
            'listed' => false,
            'queue_number' => '#'.str_pad((string) fake()->numberBetween(120, 299), 4, '0', STR_PAD_LEFT),
            'pledge_amount' => fake()->numberBetween(1, 2000) * 5000,
            'blended_yield' => 12.5,
        ];
    }

    /**
     * Indicate that the signup is an investor pledge.
     */
    public function investor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PulseSignupType::Investor,
            'projected_return' => (int) round(($attributes['pledge_amount'] ?? 500000) * 1.125),
        ]);
    }

    /**
     * Indicate that the signup is a business pre-qualification.
     */
    public function business(): static
    {
        return $this->state(fn (): array => [
            'type' => PulseSignupType::Business,
            'name' => fake()->company(),
            'pledge_amount' => null,
            'projected_return' => null,
            'blended_yield' => null,
            'statement_path' => 'pulse-statements/'.fake()->uuid().'.pdf',
            'annual_inflow' => fake()->numberBetween(48, 127) * 1000000,
            'qualified_amount' => fake()->numberBetween(20, 200) * 1000000,
            'term_months' => fake()->randomElement([3, 6, 9, 12]),
            'flat_rate' => fake()->randomFloat(1, 10, 15),
            'rating_band' => 'Stable',
            'rating_score' => 3.4,
            'loan_number' => '#'.number_format(fake()->numberBetween(1470, 1509)),
        ]);
    }
}
