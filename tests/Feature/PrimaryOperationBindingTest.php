<?php

declare(strict_types=1);

use App\Models\CommandOperation;
use App\Models\Party;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->freezeSecond();
});

function flushPrimaryOperationBindings(): void
{
    DB::statement('SET CONSTRAINTS primary_reservation_command_bound, primary_version_command_bound IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

/** @return array<string, mixed> */
function wrongPrimaryOperation(string $field): array
{
    return match ($field) {
        'actor_key' => ['actor_key' => 'party:'.strtolower((string) Str::ulid())],
        'actor_user_id' => ['actor_user_id' => User::factory()->create(['party_id' => Party::factory()])->id],
        'staff' => ['actor_key' => 'staff:1'],
        'command' => ['command' => 'wallet.deposit'],
        'target_type' => ['target_type' => 'wallet'],
        'target_id' => ['target_id' => strtolower((string) Str::ulid())],
        default => throw new InvalidArgumentException('Unknown operation substitution.'),
    };
}

it('refuses a reservation linked to another actor command or target at commit', function (string $field): void {
    $root = PrimaryReservationRecord::factory()->make();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    $substitute = CommandOperation::factory()->create([...$origin->only(['actor_key', 'actor_user_id', 'command', 'target_type', 'target_id', 'result']), ...wrongPrimaryOperation($field)]);
    expect(fn () => DB::transaction(function () use ($root, $substitute): void {
        $root->forceFill(['origin_operation_id' => $substitute->id])->save();
        PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $root->id, 'revision' => 1,
            'operation_id' => $substitute->id, 'previous_sha256' => null, 'created_at' => $root->created_at]);
        flushPrimaryOperationBindings();
    }))->toThrow(QueryException::class, 'Party command and target binding');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(0);
})->with(['actor_key', 'actor_user_id', 'staff', 'command', 'target_type', 'target_id']);

it('binds every actor driven revision and commitment to the same Party and reservation', function (string $state, string $field): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $version = PrimaryReservationVersion::factory()->make(['primary_reservation_id' => $root->id,
        'state' => $state, 'created_at' => $state === 'expired' ? $root->expires_at : now()]);
    $operation = CommandOperation::query()->whereKey($version->operation_id)->sole();
    $substitute = CommandOperation::factory()->create([...$operation->only(['actor_key', 'actor_user_id', 'command', 'target_type', 'target_id', 'result']), ...wrongPrimaryOperation($field)]);
    expect(fn () => DB::transaction(function () use ($version, $substitute, $state): void {
        $version = PrimaryReservationVersion::factory()->withCashMovement()->create([
            ...$version->only(['primary_reservation_id', 'state', 'created_at']), 'operation_id' => $substitute->id,
        ]);
        if ($state === 'confirmed') {
            PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $version->id]);
        }
        flushPrimaryOperationBindings();
    }))->toThrow(QueryException::class, 'Party command and target binding');
    expect(PrimaryReservationVersion::query()->count())->toBe(1)->and(PrimaryCommitment::query()->count())->toBe(0);
})->with(['held', 'confirmed', 'released', 'expired'])->with(['actor_key', 'actor_user_id', 'command', 'target_type', 'target_id']);

it('does not confuse a reserve operation with a later confirmation or release', function (string $state, string $command): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $version = PrimaryReservationVersion::factory()->make(['primary_reservation_id' => $root->id, 'state' => $state]);
    $operation = CommandOperation::query()->whereKey($version->operation_id)->sole();
    $substitute = CommandOperation::factory()->create([...$operation->only(['actor_key', 'actor_user_id', 'target_type', 'target_id']), 'command' => $command]);
    expect(fn () => DB::transaction(function () use ($version, $substitute, $state): void {
        $version = PrimaryReservationVersion::factory()->withCashMovement()->create([
            ...$version->only(['primary_reservation_id', 'state', 'created_at']), 'operation_id' => $substitute->id,
        ]);
        if ($state === 'confirmed') {
            PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $version->id]);
        }
        flushPrimaryOperationBindings();
    }))->toThrow(QueryException::class, 'Party command and target binding');
})->with([['confirmed', 'primary.reserve'], ['released', 'primary.confirm'], ['held', 'primary.release']]);

it('allows the journal operation to be inserted after its confirmation evidence', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    $operation = CommandOperation::factory()->make(['id' => strtolower((string) Str::ulid()), 'actor_key' => $origin->actor_key,
        'actor_user_id' => $origin->actor_user_id, 'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id]);
    $version = PrimaryReservationVersion::factory()->confirmed()->withCashMovement()->create(['primary_reservation_id' => $root->id, 'operation_id' => $operation->id]);
    $commitment = PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $version->id]);
    $operation->forceFill(['result' => ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED',
        'operation_id' => $operation->id, 'revision' => $version->revision,
        'data' => ['reservation_id' => $root->id, 'commitment_id' => $commitment->id, 'amount' => $root->principal]]])->save();
    flushPrimaryOperationBindings();
    expect(PrimaryCommitment::query()->sole()->operation_id)->toBe($operation->id);
});

it('retains the confirmation attempt that observes an expired hold', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    $operation = CommandOperation::factory()->create(['actor_key' => $origin->actor_key, 'actor_user_id' => $origin->actor_user_id,
        'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id,
        'result' => ['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED']]);
    PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id, 'state' => 'expired',
        'created_at' => $root->expires_at, 'operation_id' => $operation->id]);
    flushPrimaryOperationBindings();
    expect(PrimaryReservationVersion::query()->where('state', 'expired')->sole()->operation_id)->toBe($operation->id);
});
