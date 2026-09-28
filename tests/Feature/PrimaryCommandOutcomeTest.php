<?php

declare(strict_types=1);

use App\Models\CommandOperation;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->freezeSecond();
});

/** @param array<string, mixed> $result */
function primaryOperationWithResult(string $original, array $result): string
{
    $operation = CommandOperation::query()->whereKey($original)->sole();

    return CommandOperation::factory()->create([...$operation->only(['actor_key', 'actor_user_id', 'command', 'target_type', 'target_id']), 'result' => $result])->id;
}

/** @param array<string, mixed> $result */
function retainPrimaryOutcome(string $state, array $result): void
{
    if ($state === 'root') {
        $root = PrimaryReservationRecord::factory()->make();
        PrimaryReservationRecord::factory()->withInitialVersion()->create([
            ...$root->only(['business_campaign_id', 'party_id']), 'origin_operation_id' => primaryOperationWithResult($root->origin_operation_id, $result),
        ]);

        return;
    }
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $version = PrimaryReservationVersion::factory()->make(['primary_reservation_id' => $root->id, 'state' => $state]);
    $version = PrimaryReservationVersion::factory()->withCashMovement()->create([
        ...$version->only(['primary_reservation_id', 'state', 'created_at']),
        'operation_id' => primaryOperationWithResult($version->operation_id, $result),
    ]);
    if ($state === 'confirmed') {
        PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $version->id]);
    }
}

it('requires a completed receipt for roots requotes confirmations and releases', function (string $state, array $result): void {
    expect(fn () => DB::transaction(function () use ($state, $result): void {
        retainPrimaryOutcome($state, $result);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, 'completed command outcome');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(PrimaryReservationVersion::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(0);
})->with(['root', 'held', 'confirmed', 'released'])->with([
    'rejected' => [['status' => 'rejected']], 'missing' => [[]],
    'unknown' => [['status' => 'pending']], 'null' => [['status' => null]], 'non-string' => [['status' => true]],
]);

it('accepts completed evidence and preserves immutable receipts', function (string $state): void {
    retainPrimaryOutcome($state, ['status' => 'completed', 'code' => 'SYNTHETIC_PRIMARY_RESULT']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(PrimaryReservationRecord::query()->count())->toBe(1);
    expect(fn () => DB::transaction(fn () => CommandOperation::query()->update(['result' => ['status' => 'rejected']])))
        ->toThrow(QueryException::class, 'immutable');
})->with(['root', 'held', 'confirmed', 'released']);

it('allows an expiry observation to retain the rejected confirmation or release attempt', function (string $command): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    $operation = CommandOperation::factory()->create([...$origin->only(['actor_key', 'actor_user_id']),
        'command' => $command, 'target_type' => 'primary_reservation', 'target_id' => $root->id,
        'result' => ['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED']]);
    PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id, 'state' => 'expired',
        'created_at' => $root->expires_at, 'operation_id' => $operation->id]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(PrimaryReservationVersion::query()->where('state', 'expired')->sole()->operation_id)->toBe($operation->id);
})->with(['primary.confirm', 'primary.release']);

it('audits old outcome bindings atomically and refuses to remove protection with retained history', function (string $state): void {
    $migration = require database_path('migrations/2026_09_28_163057_require_completed_primary_command_outcomes.php');
    $migration->down();
    retainPrimaryOutcome($state, ['status' => 'rejected']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $before = DB::table('command_operations')->orderBy('id')->get()->toJson();
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'completed command outcome');
    expect(DB::selectOne("SELECT to_regprocedure('require_completed_primary_outcome()') AS function")->function)->toBeNull()
        ->and(DB::table('command_operations')->orderBy('id')->get()->toJson())->toBe($before);
})->with(['root', 'held', 'confirmed', 'released']);

it('can install over matching completed history but cannot roll it back', function (): void {
    $migration = require database_path('migrations/2026_09_28_163057_require_completed_primary_command_outcomes.php');
    $migration->down();
    retainPrimaryOutcome('confirmed', ['status' => 'completed']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $migration->up();
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration');
});
