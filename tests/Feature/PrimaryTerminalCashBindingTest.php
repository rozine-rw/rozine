<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

function flushPrimaryTerminalCash(): void
{
    DB::statement('SET CONSTRAINTS primary_version_cash_bound, ledger_primary_terminal_bound IMMEDIATE');
    DB::statement('SET CONSTRAINTS primary_version_cash_bound, ledger_primary_terminal_bound DEFERRED');
}

function terminalCashSource(): PostingSource
{
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();

    return new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
}

function terminalCashPosting(PostingSource $source, string $movement): void
{
    $root = PrimaryReservationRecord::query()->whereKey($source->id)->sole();
    $wallets = app(WalletPostings::class);
    $wallets->{$movement}($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal), $source);
}

it('accepts held evidence and either order of matching terminal evidence and cash', function (string $state, bool $cashFirst): void {
    $source = terminalCashSource();
    flushPrimaryTerminalCash();
    $movement = $state === 'confirmed' ? 'commit' : 'release';
    if ($cashFirst) {
        terminalCashPosting($source, $movement);
    }
    PrimaryReservationFixture::terminalVersion($source, $state);
    if (! $cashFirst) {
        terminalCashPosting($source, $movement);
    }
    flushPrimaryTerminalCash();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail()->state)->toBe($state)
        ->and(LedgerEntry::query()->where('source_id', $source->id)->where('kind', 'primary_'.$movement)->count())->toBe(1);
})->with(['confirmed', 'released', 'expired'])->with([true, false]);

it('refuses terminal versions without their matching cash at an early constraint flush', function (string $state): void {
    $source = terminalCashSource();
    flushPrimaryTerminalCash();
    expect(fn () => DB::transaction(function () use ($source, $state): void {
        PrimaryReservationFixture::terminalVersion($source, $state);
        flushPrimaryTerminalCash();
    }))->toThrow(QueryException::class, 'terminal version and cash movement must agree');
    expect(PrimaryReservationVersion::query()->count())->toBe(1);
})->with(['confirmed', 'released', 'expired']);

it('refuses a wallet commit or release without its matching version', function (string $movement): void {
    $source = terminalCashSource();
    flushPrimaryTerminalCash();
    expect(fn () => DB::transaction(function () use ($source, $movement): void {
        terminalCashPosting($source, $movement);
        flushPrimaryTerminalCash();
    }))->toThrow(QueryException::class, 'terminal version and cash movement must agree');
    expect(LedgerEntry::query()->where('source_id', $source->id)->count())->toBe(1);
})->with(['commit', 'release']);

it('cannot match another reservation cash or a different terminal state', function (string $case): void {
    $source = terminalCashSource();
    $other = terminalCashSource();
    flushPrimaryTerminalCash();
    expect(fn () => DB::transaction(function () use ($source, $other, $case): void {
        PrimaryReservationFixture::terminalVersion($source, 'confirmed');
        terminalCashPosting($case === 'other' ? $other : $source, $case === 'other' ? 'commit' : 'release');
        flushPrimaryTerminalCash();
    }))->toThrow(QueryException::class, 'terminal version and cash movement must agree');
})->with(['other', 'wrong_state']);

it('installs over valid held and terminal evidence without changing retained ciphertext and refuses rollback', function (): void {
    $migration = require database_path('migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php');
    $migration->down();
    terminalCashSource();
    foreach (['confirmed', 'released', 'expired'] as $state) {
        $source = terminalCashSource();
        PrimaryReservationFixture::terminalVersion($source, $state);
        terminalCashPosting($source, $state === 'confirmed' ? 'commit' : 'release');
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $before = DB::table('primary_reservation_versions')->orderBy('id')->get()->toJson();
    $migration->up();
    expect(DB::table('primary_reservation_versions')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration');
});

it('aborts installation atomically over mismatched historical terminal evidence', function (string $case): void {
    $migration = require database_path('migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php');
    $migration->down();
    $source = terminalCashSource();
    if ($case === 'version_only') {
        PrimaryReservationFixture::terminalVersion($source, 'confirmed');
    } else {
        terminalCashPosting($source, 'release');
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'terminal version and cash movement must agree')
        ->and(DB::selectOne("SELECT to_regprocedure('check_primary_terminal_cash(varchar)') AS function")->function)->toBeNull()
        ->and(PrimaryReservationRecord::query()->count())->toBe(1);
})->with(['version_only', 'cash_only']);
