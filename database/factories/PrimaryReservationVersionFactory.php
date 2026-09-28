<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\CommandOperation;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrimaryReservationVersion> */
class PrimaryReservationVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['primary_reservation_id' => PrimaryReservationRecord::factory()->withInitialVersion(), 'revision' => 2,
            'state' => 'held', 'operation_id' => function (array $a): string {
                $reservation = PrimaryReservationRecord::query()->whereKey($a['primary_reservation_id'])->firstOrFail();
                $origin = CommandOperation::query()->whereKey($reservation->origin_operation_id)->firstOrFail();

                return CommandOperation::factory()->create(['actor_key' => 'party:'.$reservation->party_id, 'actor_user_id' => $origin->actor_user_id,
                    'command' => in_array($a['state'], ['released', 'expired'], true) ? 'primary.release' : 'primary.confirm',
                    'target_type' => 'primary_reservation', 'target_id' => $reservation->id])->id;
            },
            'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $a): string => hash('sha256', app(CanonicalJson::class)->encode($a['payload'])),
            'previous_sha256' => fn (array $a): ?string => PrimaryReservationVersion::query()->where('primary_reservation_id', $a['primary_reservation_id'])->orderByDesc('revision')->value('sha256'),
            'created_at' => now()->startOfSecond()];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => ['state' => 'confirmed']);
    }
}
