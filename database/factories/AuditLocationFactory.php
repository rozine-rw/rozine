<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auditor\VerifiedAuditLocation;
use App\Models\AuditLocation;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLocation> */
class AuditLocationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['office_party_id' => Party::factory()->verified(), 'business_id' => null, 'revision' => 1,
            'state' => app(VerifiedAuditLocation::class)->empty()];
    }
}
