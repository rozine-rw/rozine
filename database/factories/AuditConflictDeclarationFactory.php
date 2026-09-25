<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditAssignment;
use App\Models\AuditConflictDeclaration;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditConflictDeclaration> */
class AuditConflictDeclarationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['assignment_id' => AuditAssignment::factory(),
            'business_id' => fn (array $attributes): string => AuditAssignment::query()->whereKey($attributes['assignment_id'])->firstOrFail()->business_id,
            'party_id' => Party::factory()->verified(), 'actor_user_id' => User::factory(), 'kind' => 'other',
            'reason' => 'Synthetic private conflict.', 'policy_version' => 'engineering-2026-09-23.4'];
    }
}
