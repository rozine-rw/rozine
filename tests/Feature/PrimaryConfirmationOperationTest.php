<?php

declare(strict_types=1);

use App\Models\CommandOperation;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->freezeSecond();
});

/** Raw-write regression: a requote has no purchase, despite the receipt claiming one. */
function orphanPrimaryConfirmation(bool $heldVersion = true): CommandOperation
{
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    $id = strtolower((string) Str::ulid());
    if ($heldVersion) {
        PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $root->id, 'operation_id' => $id]);
    }

    return CommandOperation::factory()->create(['id' => $id, 'actor_key' => $origin->actor_key, 'actor_user_id' => $origin->actor_user_id,
        'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id,
        'result' => ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'operation_id' => $id, 'revision' => 2,
            'data' => ['reservation_id' => $root->id, 'commitment_id' => strtolower((string) Str::ulid()), 'amount' => $root->principal]]]);
}

it('rejects a purchase receipt with no matching commitment at commit', function (bool $heldVersion): void {
    expect(fn () => DB::transaction(function () use ($heldVersion): void {
        orphanPrimaryConfirmation($heldVersion);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
    expect(PrimaryCommitment::query()->count())->toBe(0)->and(PrimaryReservationRecord::query()->count())->toBe(0);
})->with(['held requote' => true, 'no version' => false]);

it('accepts a matching commitment written after its receipt', function (): void {
    $commitment = PrimaryCommitment::factory()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(CommandOperation::query()->whereKey($commitment->operation_id)->sole()->result['data']['commitment_id'])->toBe($commitment->id);
});

it('audits historical orphan receipts atomically without rewriting them', function (): void {
    $migration = require database_path('migrations/2026_09_28_212446_bind_primary_confirmation_operations_to_purchases.php');
    $migration->down();
    orphanPrimaryConfirmation();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $before = DB::table('command_operations')->orderBy('id')->get()->toJson();
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
    expect(DB::selectOne("SELECT to_regprocedure('check_primary_confirmation_operation(varchar)') AS function")->function)->toBeNull()
        ->and(DB::table('command_operations')->orderBy('id')->get()->toJson())->toBe($before);
});

it('round trips without purchases but retains the guard once real receipts exist', function (): void {
    $migration = require database_path('migrations/2026_09_28_212446_bind_primary_confirmation_operations_to_purchases.php');
    $migration->down();
    $migration->up();
    $migration->down();
    PrimaryCommitment::factory()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $migration->up();
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration');
});

/** @param array<string, mixed> $changes */
function primaryReceiptAfterPurchase(array $changes = []): PrimaryCommitment
{
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    $operationId = strtolower((string) Str::ulid());
    $commitmentId = strtolower((string) Str::ulid());
    $attributes = ['id' => $operationId, 'actor_key' => $origin->actor_key, 'actor_user_id' => $origin->actor_user_id,
        'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id,
        'result' => ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'operation_id' => $operationId, 'revision' => 2,
            'data' => ['reservation_id' => $root->id, 'commitment_id' => $commitmentId, 'amount' => $root->principal]]];
    foreach ($changes as $key => $value) {
        data_set($attributes, $key, $value);
    }
    $version = PrimaryReservationVersion::factory()->confirmed()->withCashMovement()->create([
        'primary_reservation_id' => $root->id, 'operation_id' => $operationId]);
    $commitment = PrimaryCommitment::factory()->create(['id' => $commitmentId, 'primary_reservation_version_id' => $version->id]);
    CommandOperation::factory()->create($attributes);

    return $commitment;
}

it('accepts the journal after purchase order used by checkout', function (): void {
    $commitment = primaryReceiptAfterPurchase();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect($commitment->exists)->toBeTrue();
});

it('checks receipt content and command targeting independently of the commitment trigger', function (string $key, mixed $value): void {
    expect(fn () => DB::transaction(function () use ($key, $value): void {
        primaryReceiptAfterPurchase([$key => $value]);
        DB::statement('SET CONSTRAINTS primary_confirmation_operation_bound IMMEDIATE');
    }))->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
})->with([
    ['command', 'primary.release'], ['target_type', 'business_campaign'], ['target_id', '00000000000000000000000000'],
    ['result.status', 'rejected'], ['result.operation_id', '00000000000000000000000000'],
    ['result.revision', '2'], ['result.data.amount', 5000], ['result.data.reservation_id', null], ['result.data.commitment_id', null],
]);

it('does not borrow another purchases valid receipt', function (): void {
    PrimaryCommitment::factory()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(fn () => DB::transaction(function (): void {
        orphanPrimaryConfirmation();
        DB::statement('SET CONSTRAINTS primary_confirmation_operation_bound IMMEDIATE');
    }))->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
    expect(PrimaryCommitment::query()->count())->toBe(1);
});
