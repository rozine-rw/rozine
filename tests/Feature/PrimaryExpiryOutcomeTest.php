<?php

declare(strict_types=1);

use App\Models\CommandOperation;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->freezeSecond();
});

/** @param array<string, mixed> $result */
function expiryReceipt(PrimaryReservationRecord $root, string $command, array $result): CommandOperation
{
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();

    return CommandOperation::factory()->make([...$origin->only(['actor_key', 'actor_user_id']),
        'command' => $command, 'target_type' => 'primary_reservation', 'target_id' => $root->id, 'result' => $result]);
}

it('refuses an expiry receipt that does not report the rejected expiry', function (string $command, array $result): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $receipt = expiryReceipt($root, $command, $result);
    $receipt->save();
    expect(fn () => DB::transaction(function () use ($root, $receipt): void {
        PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id,
            'state' => 'expired', 'created_at' => $root->expires_at, 'operation_id' => $receipt->id]);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, 'rejected RESERVATION_EXPIRED outcome');
    expect(PrimaryReservationVersion::query()->pluck('state')->all())->toBe(['held']);
})->with(['primary.confirm', 'primary.release'])->with([
    'completed' => [['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED']],
    'missing' => [[]], 'pending' => [['status' => 'pending']],
    'null' => [['status' => null]], 'non-string' => [['status' => true]],
    'wrong refusal' => [['status' => 'rejected', 'code' => 'INSUFFICIENT_AVAILABLE_FUNDS']],
]);

it('allows actor expiry evidence before its rejected journal receipt', function (string $command): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $receipt = expiryReceipt($root, $command, ['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED']);
    $receipt->id = strtolower((string) Str::ulid());
    PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id,
        'state' => 'expired', 'created_at' => $root->expires_at, 'operation_id' => $receipt->id]);
    $receipt->save();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(PrimaryReservationVersion::query()->where('state', 'expired')->sole()->operation_id)->toBe($receipt->id);
})->with(['primary.confirm', 'primary.release']);

it('audits and preserves actor and system expiries when installing terminal guards', function (): void {
    $migration = require database_path('migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php');
    $migration->down();
    foreach (['primary.confirm', 'primary.release', null] as $command) {
        $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
        $receipt = $command === null ? null : expiryReceipt($root, $command, ['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED']);
        $receipt?->save();
        PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id,
            'state' => 'expired', 'created_at' => $root->expires_at, 'operation_id' => $receipt?->id]);
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $before = DB::table('command_operations')->orderBy('id')->get()->toJson();
    $migration->up();
    expect(PrimaryReservationVersion::query()->where('state', 'expired')->count())->toBe(3)
        ->and(DB::table('command_operations')->orderBy('id')->get()->toJson())->toBe($before);
});

it('refuses historical expiry receipts atomically even when their cash matches', function (string $command): void {
    $migration = require database_path('migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php');
    $migration->down();
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $receipt = expiryReceipt($root, $command, ['status' => 'completed']);
    $receipt->save();
    PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id,
        'state' => 'expired', 'created_at' => $root->expires_at, 'operation_id' => $receipt->id]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $before = DB::table('command_operations')->orderBy('id')->get()->toJson();
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'rejected RESERVATION_EXPIRED outcome')
        ->and(DB::selectOne("SELECT to_regprocedure('check_primary_expiry_outcome(varchar)') AS function")->function)->toBeNull()
        ->and(DB::selectOne("SELECT to_regprocedure('check_primary_terminal_cash(varchar)') AS function")->function)->toBeNull()
        ->and(DB::table('command_operations')->orderBy('id')->get()->toJson())->toBe($before);
})->with(['primary.confirm', 'primary.release']);
