<?php

namespace Database\Factories;

use App\Domain\Pulse\PulseSector;
use App\Domain\Pulse\PulseUnderwriting;
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
            'blended_yield' => PulseUnderwriting::BLENDED_YIELD,
        ];
    }

    /**
     * Indicate that the signup is an investor pledge.
     */
    public function investor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PulseSignupType::Investor,
            'projected_return' => PulseUnderwriting::projectedReturn((int) ($attributes['pledge_amount'] ?? 500000)),
        ]);
    }

    /**
     * Indicate that the business sized under the smallest loan Rozine writes,
     * so it joined the waitlist rather than pre-qualifying.
     */
    public function waitlisted(): static
    {
        return $this->business()->state(fn (): array => $this->persistedSizing(
            PulseUnderwriting::size(
                16_000_000,
                15_400_000,
                PulseSector::Other,
                2024,
                3,
                (int) now()->year,
            ),
        ));
    }

    /**
     * Indicate that the signup is a business pre-qualification.
     */
    public function business(): static
    {
        return $this->state(function (): array {
            // Comfortably clear of the smallest loan Rozine writes, so a
            // factory business is one an investor could actually back.
            $annualRevenue = fake()->numberBetween(120, 320) * 1000000;
            $annualCosts = (int) round($annualRevenue * fake()->randomFloat(2, 0.55, 0.75));

            return [
                'type' => PulseSignupType::Business,
                'name' => fake()->company(),
                'pledge_amount' => null,
                'projected_return' => null,
                'blended_yield' => null,
                ...$this->persistedSizing(
                    PulseUnderwriting::size(
                        $annualRevenue,
                        $annualCosts,
                        fake()->randomElement(PulseSector::cases()),
                        fake()->numberBetween(2005, 2024),
                        fake()->randomElement(PulseUnderwriting::TERMS),
                        (int) now()->year,
                    ),
                ),
                'loan_number' => '#'.number_format(fake()->numberBetween(1470, 1509)),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $sizing
     * @return array<string, mixed>
     */
    private function persistedSizing(array $sizing): array
    {
        return [
            'annual_revenue' => $sizing['annual_revenue'],
            'annual_costs' => $sizing['annual_costs'],
            'sector' => $sizing['sector'],
            'registered_year' => $sizing['registered_year'],
            'score' => $sizing['score'],
            'qualified_amount' => $sizing['qualified_amount'],
            'term_months' => $sizing['term_months'],
            'flat_rate' => $sizing['flat_rate'],
            'rating_band' => $sizing['rating_band'],
            'rating_score' => $sizing['rating_score'],
        ];
    }
}
