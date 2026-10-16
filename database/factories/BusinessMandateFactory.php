<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Business\MandateAuthority;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessMandate> */
class BusinessMandateFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_id' => BusinessProfile::factory(), 'version' => 1,
            'profile' => fn (array $attributes): array => BusinessProfile::query()->whereKey($attributes['business_id'])->firstOrFail()->profile,
            'terms' => function (array $attributes): array {
                $business = BusinessProfile::query()->whereKey($attributes['business_id'])->firstOrFail();

                return ['people' => [['party_id' => $business->entity_party_id, 'name' => 'Synthetic owner', 'roles' => ['owner', 'signatory'], 'permissions' => MandateAuthority::PERMISSIONS]],
                    'required_signatories' => [$business->entity_party_id], 'effective_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'),
                    'expires_at' => null, 'status' => 'active', 'attested_complete' => true];
            },
            'actor_user_id' => 1, 'evidence_reference' => 'fixture:mandate', 'reason' => 'Synthetic immutability test.',
            'policy_version' => 'engineering-2026-09-23.4'];
    }
}
