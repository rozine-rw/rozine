<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Party;
use App\Models\VerifiedOrganizationIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerifiedOrganizationIdentity>
 */
class VerifiedOrganizationIdentityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registry_digest' => hash('sha256', 'rdb:'.fake()->uuid()),
            'party_id' => Party::factory()->state(['kind' => 'organization', 'verified_at' => now()]),
            'evidence_reference' => 'fixture:'.fake()->uuid(),
        ];
    }
}
