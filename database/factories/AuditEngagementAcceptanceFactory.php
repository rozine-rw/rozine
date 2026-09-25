<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\AuditEngagementAcceptance;
use App\Models\AuditEngagementRelease;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEngagementAcceptance>
 */
class AuditEngagementAcceptanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['audit_engagement_release_id' => fn (): string => AuditEngagementRelease::query()->orderByDesc('revision')->first()->id ?? AuditEngagementRelease::factory()->create()->id,
            'party_id' => Party::factory()->verified(),
            'release_revision' => fn (array $attributes): int => AuditEngagementRelease::query()->whereKey($attributes['audit_engagement_release_id'])->firstOrFail()->revision,
            'release_sha256' => fn (array $attributes): string => AuditEngagementRelease::query()->whereKey($attributes['audit_engagement_release_id'])->firstOrFail()->sha256,
            'actor_user_id' => fn (array $attributes): int => User::factory()->withTwoFactor()->create(['party_id' => $attributes['party_id']])->id,
            'payload' => fn (array $attributes): array => ['release_id' => $attributes['audit_engagement_release_id'], 'release_revision' => $attributes['release_revision'],
                'release_sha256' => $attributes['release_sha256'], 'party_id' => $attributes['party_id'], 'actor_user_id' => $attributes['actor_user_id'],
                'identity_context_revision' => 1, 'accepted_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'), 'accepted' => true, 'synthetic' => true],
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload']))];
    }
}
