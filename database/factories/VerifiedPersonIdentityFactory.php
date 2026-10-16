<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Party;
use App\Models\VerifiedPersonIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<VerifiedPersonIdentity> */
class VerifiedPersonIdentityFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'identity_digest' => hash('sha256', (string) Str::uuid()),
            'party_id' => Party::factory()->state(['verified_at' => now()]),
            'evidence_reference' => 'fixture:'.Str::uuid(),
        ];
    }
}
