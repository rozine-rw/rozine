<?php

declare(strict_types=1);

/*
 * Review probes for PR #175 c8f30fcb, migration 2026_09_28_175455 (terminal version <-> cash binding and
 * actor expiry receipts). Copy into tests/Feature/. Every test is a PROBE: it tries a bypass and PASSES
 * when the guard (or an existing guard) refuses it.
 */

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

function r175lSource(): PostingSource
{
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    return new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
}

function r175lCash(PostingSource $source, string $movement): void
{
    $root = PrimaryReservationRecord::query()->whereKey($source->id)->sole();
    $wallets = app(WalletPostings::class);
    $wallets->{$movement}($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal), $source);
}

function r175lFlush(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
}

/** @param array<string, mixed> $attributes */
function r175lReceipt(PrimaryReservationRecord $root, array $attributes): CommandOperation
{
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();

    return CommandOperation::factory()->create([...$origin->only(['actor_key', 'actor_user_id']),
        'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id,
        'result' => ['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED'], ...$attributes]);
}

it('PROBE: a released or expired version cannot carry a commit instead of a release', function (string $state): void {
    $source = r175lSource();
    expect(fn () => DB::transaction(function () use ($source, $state): void {
        PrimaryReservationFixture::terminalVersion($source, $state);
        r175lCash($source, 'commit');
        r175lFlush();
    }))->toThrow(QueryException::class, 'terminal version and cash movement must agree');
})->with(['released', 'expired']);

it('PROBE: a requoted (still held) revision cannot carry commit or release cash', function (string $movement): void {
    $source = r175lSource();
    expect(fn () => DB::transaction(function () use ($source, $movement): void {
        PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $source->id, 'state' => 'held']);
        r175lCash($source, $movement);
        r175lFlush();
    }))->toThrow(QueryException::class, 'terminal version and cash movement must agree');
})->with(['commit', 'release']);

it('PROBE: a refund follows a confirmed commit and cannot follow a release', function (): void {
    $confirmed = r175lSource();
    DB::transaction(function () use ($confirmed): void {
        PrimaryReservationFixture::terminalVersion($confirmed, 'confirmed');
        r175lCash($confirmed, 'commit');
        r175lCash($confirmed, 'refund');
        r175lFlush();
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
    });
    $released = r175lSource();
    expect(fn () => DB::transaction(function () use ($released): void {
        PrimaryReservationFixture::terminalVersion($released, 'released');
        r175lCash($released, 'release');
        r175lCash($released, 'refund');
    }))->toThrow(App\Domain\Wallet\WalletViolation::class, 'WALLET_POSTING_STATE_INVALID');
    expect(LedgerEntry::query()->where('source_id', $confirmed->id)->pluck('kind')->sort()->values()->all())
        ->toBe(['primary_commit', 'primary_hold', 'primary_refund']);
});

it('PROBE: an actor expiry cannot borrow another reservation or another command receipt', function (string $case): void {
    $source = r175lSource();
    $root = PrimaryReservationRecord::query()->whereKey($source->id)->sole();
    $other = PrimaryReservationRecord::query()->whereKey(r175lSource()->id)->sole();
    $receipt = match ($case) {
        'other_target' => r175lReceipt($root, ['target_id' => $other->id]),
        'reserve_command' => r175lReceipt($root, ['command' => 'primary.reserve']),
        'other_actor' => r175lReceipt($root, ['actor_key' => 'party:'.$other->party_id]),
    };
    expect(fn () => DB::transaction(function () use ($root, $receipt): void {
        PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id,
            'state' => 'expired', 'created_at' => $root->expires_at, 'operation_id' => $receipt->id]);
        r175lFlush();
    }))->toThrow(QueryException::class, 'Party command and target binding');
    expect(PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->pluck('state')->all())->toBe(['held']);
})->with(['other_target', 'reserve_command', 'other_actor']);

it('PROBE: a system expiry needs released cash, and with it commits', function (): void {
    $bare = r175lSource();
    $root = PrimaryReservationRecord::query()->whereKey($bare->id)->sole();
    expect(fn () => DB::transaction(function () use ($root): void {
        PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $root->id, 'state' => 'expired',
            'created_at' => $root->expires_at, 'operation_id' => null]);
        r175lFlush();
    }))->toThrow(QueryException::class, 'terminal version and cash movement must agree');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::transaction(function () use ($root): void {
        PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id, 'state' => 'expired',
            'created_at' => $root->expires_at, 'operation_id' => null]);
        r175lFlush();
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
    });
    expect(PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->pluck('state')->all())->toBe(['held', 'expired']);
});
