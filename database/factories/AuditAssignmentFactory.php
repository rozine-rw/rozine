<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auditor\AuditEngagementState;
use App\Models\AuditAssignment;
use App\Models\BusinessProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditAssignment> */
class AuditAssignmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_id' => BusinessProfile::factory(), 'party_id' => null, 'revision' => 1, 'status' => 'operations',
            'state' => app(AuditEngagementState::class)->start('routine', now()->toDateTimeImmutable()), 'completed_at' => null];
    }
}
