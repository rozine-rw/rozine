<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => 'person',
            'verified_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (): array => ['verified_at' => now()]);
    }
}
