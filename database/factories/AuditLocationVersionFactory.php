<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLocation;
use App\Models\AuditLocationVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLocationVersion> */
class AuditLocationVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['audit_location_id' => AuditLocation::factory(), 'revision' => 1,
            'snapshot' => function (array $attributes): array {
                $location = AuditLocation::query()->whereKey($attributes['audit_location_id'])->firstOrFail();

                return ['id' => $location->id, 'kind' => $location->business_id === null ? 'office' : 'premises',
                    'subject_id' => $location->business_id ?? $location->office_party_id, 'revision' => $location->revision, 'state' => $location->state];
            }, 'actor_user_id' => User::factory(), 'command' => 'audit.location.verify',
            'reason' => 'Synthetic location evidence.', 'policy_version' => 'engineering-2026-09-23.4'];
    }
}
