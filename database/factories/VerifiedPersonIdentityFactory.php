<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Party;
use App\Models\VerifiedPersonIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VerifiedPersonIdentity> */
class VerifiedPersonIdentityFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'identity_digest' => hash('sha256', fake()->uuid()),
            'party_id' => Party::factory()->state(['verified_at' => now()]),
            'evidence_reference' => 'fixture:'.fake()->uuid(),
        ];
    }
}
