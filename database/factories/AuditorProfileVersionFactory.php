<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditorProfile;
use App\Models\AuditorProfileVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditorProfileVersion> */
class AuditorProfileVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['auditor_profile_id' => AuditorProfile::factory(), 'revision' => 1,
            'snapshot' => function (array $attributes): array {
                $profile = AuditorProfile::query()->whereKey($attributes['auditor_profile_id'])->firstOrFail();

                return ['id' => $profile->id, 'party_id' => $profile->party_id, 'revision' => $profile->revision, 'state' => $profile->state];
            }, 'actor_user_id' => User::factory(), 'command' => 'accreditation.submit', 'reason' => null, 'policy_version' => 'engineering-2026-09-23.4'];
    }
}
