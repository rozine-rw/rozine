<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\CommandOperation;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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

                $operationId = strtolower((string) Str::ulid());
                $result = $a['state'] === 'confirmed'
                    ? ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'operation_id' => $operationId,
                        'revision' => $a['revision'], 'data' => ['reservation_id' => $reservation->id,
                            'commitment_id' => strtolower((string) Str::ulid()), 'amount' => $reservation->principal]]
                    : ($a['state'] === 'expired' ? ['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED'] : ['status' => 'completed', 'code' => 'FIXTURE_SAVED']);

                return CommandOperation::factory()->create(['id' => $operationId, 'actor_key' => 'party:'.$reservation->party_id, 'actor_user_id' => $origin->actor_user_id,
                    'command' => in_array($a['state'], ['released', 'expired'], true) ? 'primary.release' : 'primary.confirm',
                    'target_type' => 'primary_reservation', 'target_id' => $reservation->id,
                    'result' => $result])->id;
            },
            'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $a): string => hash('sha256', app(CanonicalJson::class)->encode($a['payload'])),
            'previous_sha256' => fn (array $a): ?string => PrimaryReservationVersion::query()->where('primary_reservation_id', $a['primary_reservation_id'])->orderByDesc('revision')->value('sha256'),
            'created_at' => fn () => now()->startOfSecond()];
    }

    /** Explicit synthetic matching cash for schema fixtures, never production admission. */
    public function withCashMovement(): static
    {
        return $this->afterCreating(function (PrimaryReservationVersion $version): void {
            if ($version->state === 'held') {
                return;
            }
            $root = PrimaryReservationRecord::query()->whereKey($version->primary_reservation_id)->sole();
            $wallets = app(WalletPostings::class);
            $movement = $version->state === 'confirmed' ? 'commit' : 'release';
            $wallets->{$movement}($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
                new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
        });
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => ['state' => 'confirmed']);
    }
}
