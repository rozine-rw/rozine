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

function retainConfirmationReceipt(string $mutation = ''): PrimaryCommitment
{
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    $operationId = strtolower((string) Str::ulid());
    $commitmentId = strtolower((string) Str::ulid());
    $receipt = ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'operation_id' => $operationId,
        'revision' => 2, 'data' => ['reservation_id' => $root->id, 'commitment_id' => $commitmentId, 'amount' => $root->principal]];
    if ($mutation !== '') {
        $value = match ($mutation) {
            'code' => 'RESERVATION_REQUOTED', 'revision' => 1, 'revision_type' => '2',
            'data.amount' => '10000', 'amount_type' => 5000, 'missing_commitment' => null,
            default => strtolower((string) Str::ulid()),
        };
        data_set($receipt, match ($mutation) {
            'revision_type' => 'revision', 'amount_type' => 'data.amount', 'missing_commitment' => 'data.commitment_id',
            default => $mutation,
        }, $value);
    }
    $version = PrimaryReservationVersion::factory()->confirmed()->withCashMovement()->create([
        'primary_reservation_id' => $root->id, 'operation_id' => $operationId,
    ]);
    $commitment = PrimaryCommitment::factory()->create(['id' => $commitmentId, 'primary_reservation_version_id' => $version->id]);
    CommandOperation::factory()->create(['id' => $operationId, 'actor_key' => $origin->actor_key, 'actor_user_id' => $origin->actor_user_id,
        'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id, 'result' => $receipt]);

    return $commitment;
}

it('refuses completed receipts that disagree with retained confirmation identity', function (string $mutation): void {
    expect(fn () => DB::transaction(function () use ($mutation): void {
        retainConfirmationReceipt($mutation);
        DB::statement('SET CONSTRAINTS primary_commitment_receipt_bound IMMEDIATE');
    }))->toThrow(QueryException::class, 'receipt must identify the retained purchase');
    expect(PrimaryCommitment::query()->count())->toBe(0)->and(PrimaryReservationRecord::query()->count())->toBe(0);
})->with(['code', 'operation_id', 'revision', 'revision_type', 'data.reservation_id', 'data.commitment_id', 'missing_commitment', 'data.amount', 'amount_type']);

it('accepts exact receipts inserted after evidence and preserves receipt immutability', function (): void {
    $commitment = retainConfirmationReceipt();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect($commitment->exists)->toBeTrue();
    expect(fn () => DB::transaction(fn () => CommandOperation::query()->whereKey($commitment->operation_id)->update(['result' => ['status' => 'completed']])))
        ->toThrow(QueryException::class, 'immutable');
});

it('validates historical receipts atomically without rewriting evidence', function (): void {
    $migration = require database_path('migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php');
    $migration->down();
    retainConfirmationReceipt('code');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $before = DB::table('command_operations')->orderBy('id')->get()->toJson();
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'receipt must identify the retained purchase');
    expect(DB::selectOne("SELECT to_regprocedure('check_primary_confirmation_receipt(varchar)') AS function")->function)->toBeNull()
        ->and(DB::table('command_operations')->orderBy('id')->get()->toJson())->toBe($before);
});

it('round trips an empty guard and installs over valid receipts but refuses rollback with purchases', function (): void {
    $migration = require database_path('migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php');
    $migration->down();
    $migration->up();
    $migration->down();
    retainConfirmationReceipt();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $migration->up();
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration');
});
