<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

function committedTerminalSource(): PostingSource
{
    $root = DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create());

    return new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
}

function postTerminalCash(PostingSource $source, string $movement): void
{
    $root = PrimaryReservationRecord::query()->whereKey($source->id)->sole();
    $wallets = app(WalletPostings::class);
    $wallets->{$movement}($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal), $source);
}

it('commits matching terminal versions and cash together in either insert order', function (string $state, bool $cashFirst): void {
    $source = committedTerminalSource();
    DB::transaction(function () use ($source, $state, $cashFirst): void {
        $movement = $state === 'confirmed' ? 'commit' : 'release';
        if ($cashFirst) {
            postTerminalCash($source, $movement);
        }
        PrimaryReservationFixture::terminalVersion($source, $state);
        if (! $cashFirst) {
            postTerminalCash($source, $movement);
        }
    });
    expect(PrimaryReservationVersion::query()->orderBy('revision')->pluck('state')->all())->toBe(['held', $state])
        ->and(PrimaryCommitment::query()->count())->toBe($state === 'confirmed' ? 1 : 0)
        ->and(LedgerEntry::query()->where('source_id', $source->id)->pluck('kind')->all())
        ->toEqualCanonicalizing(['primary_hold', $state === 'confirmed' ? 'primary_commit' : 'primary_release']);
})->with(['confirmed', 'released', 'expired'])->with([true, false]);

it('rejects a terminal version without cash at the real outer commit and rolls back all evidence', function (string $state): void {
    $source = committedTerminalSource();
    expect(fn () => DB::transaction(fn () => PrimaryReservationFixture::terminalVersion($source, $state)))
        ->toThrow(PDOException::class, 'terminal version and cash movement must agree');
    expect(DB::transactionLevel())->toBe(0)
        ->and(PrimaryReservationVersion::query()->pluck('state')->all())->toBe(['held'])
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('source_id', $source->id)->pluck('kind')->all())->toBe(['primary_hold']);
})->with(['confirmed', 'released', 'expired']);

it('rejects cash without a terminal version at the real outer commit and keeps the hold intact', function (string $movement): void {
    $source = committedTerminalSource();
    $before = DB::table('ledger_lines')->orderBy('id')->get()->toJson();
    expect(fn () => DB::transaction(fn () => postTerminalCash($source, $movement)))
        ->toThrow(PDOException::class, 'terminal version and cash movement must agree');
    expect(DB::transactionLevel())->toBe(0)
        ->and(PrimaryReservationVersion::query()->pluck('state')->all())->toBe(['held'])
        ->and(LedgerEntry::query()->where('source_id', $source->id)->pluck('kind')->all())->toBe(['primary_hold'])
        ->and(DB::table('ledger_lines')->orderBy('id')->get()->toJson())->toBe($before);
})->with(['commit', 'release']);

it('keeps a nested terminal success provisional until its caller commits', function (string $state): void {
    $source = committedTerminalSource();
    expect(fn () => DB::transaction(function () use ($source, $state): void {
        DB::transaction(function () use ($source, $state): void {
            PrimaryReservationFixture::terminalVersion($source, $state);
            postTerminalCash($source, $state === 'confirmed' ? 'commit' : 'release');
        });
        throw new RuntimeException('caller failed');
    }))->toThrow(RuntimeException::class, 'caller failed');
    expect(PrimaryReservationVersion::query()->pluck('state')->all())->toBe(['held'])
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('source_id', $source->id)->pluck('kind')->all())->toBe(['primary_hold']);
})->with(['confirmed', 'released', 'expired']);

it('rejects a completed actor expiry receipt at the outer commit despite matching released cash', function (string $command): void {
    $source = committedTerminalSource();
    $root = PrimaryReservationRecord::query()->whereKey($source->id)->sole();
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    expect(fn () => DB::transaction(function () use ($root, $origin, $command): void {
        $receipt = CommandOperation::factory()->create([...$origin->only(['actor_key', 'actor_user_id']),
            'command' => $command, 'target_type' => 'primary_reservation', 'target_id' => $root->id, 'result' => ['status' => 'completed']]);
        PrimaryReservationVersion::factory()->withCashMovement()->create(['primary_reservation_id' => $root->id,
            'state' => 'expired', 'created_at' => $root->expires_at, 'operation_id' => $receipt->id]);
    }))->toThrow(PDOException::class, 'rejected RESERVATION_EXPIRED outcome');
    expect(PrimaryReservationVersion::query()->pluck('state')->all())->toBe(['held'])
        ->and(LedgerEntry::query()->where('source_id', $source->id)->pluck('kind')->all())->toBe(['primary_hold']);
})->with(['primary.confirm', 'primary.release']);
