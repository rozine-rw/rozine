<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditAssignmentVersion> */
class AuditAssignmentVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['assignment_id' => AuditAssignment::factory(), 'revision' => 1, 'party_id' => null, 'status' => 'operations',
            'snapshot' => function (array $attributes): array {
                $record = AuditAssignment::query()->whereKey($attributes['assignment_id'])->firstOrFail();

                return ['id' => $record->id, 'business_id' => $record->business_id, 'revision' => $record->revision, 'state' => $record->state];
            }, 'selection_basis' => [], 'actor_user_id' => User::factory(), 'command' => 'audit.assignment.request',
            'reason' => 'Synthetic dispatch evidence.', 'policy_version' => 'engineering-2026-09-23.4'];
    }
}
