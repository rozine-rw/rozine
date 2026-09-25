<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditSigningKey;
use App\Models\AuditSigningKeyRevocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditSigningKeyRevocation> */
class AuditSigningKeyRevocationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['audit_signing_key_id' => AuditSigningKey::factory(), 'reason' => 'Synthetic compromised-key acceptance scenario.'];
    }
}
