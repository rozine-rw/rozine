<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\Party;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrimaryReservationRecord> */
class PrimaryReservationRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_campaign_id' => BusinessCampaign::factory(),
            'publication_sha256' => fn (array $a): string => BusinessCampaign::query()->whereKey($a['business_campaign_id'])->firstOrFail()->sha256,
            'party_id' => Party::factory(),
            'origin_operation_id' => fn (array $a): string => CommandOperation::factory()->create([
                'actor_key' => 'party:'.$a['party_id'], 'actor_user_id' => User::factory()->create(['party_id' => $a['party_id']])->id,
                'command' => 'primary.reserve', 'target_type' => 'campaign', 'target_id' => $a['business_campaign_id'],
            ])->id,
            'units' => 1, 'principal' => '5000',
            'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $a): string => hash('sha256', app(CanonicalJson::class)->encode($a['payload'])),
            'created_at' => now()->startOfSecond(),
            'expires_at' => fn (array $a) => min(CarbonImmutable::parse($a['created_at'])->addSeconds(300), BusinessCampaign::query()->whereKey($a['business_campaign_id'])->firstOrFail()->expires_at)];
    }

    public function withInitialVersion(): static
    {
        return $this->afterCreating(function (PrimaryReservationRecord $reservation): void {
            PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $reservation->id, 'revision' => 1,
                'operation_id' => $reservation->origin_operation_id, 'previous_sha256' => null, 'created_at' => $reservation->created_at]);
        });
    }
}
