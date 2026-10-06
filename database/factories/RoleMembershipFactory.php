<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Party;
use App\Models\RoleMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoleMembership>
 */
class RoleMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'party_id' => Party::factory(),
            'role' => 'investor',
            'status' => 'pending',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => 'active']);
    }
}
