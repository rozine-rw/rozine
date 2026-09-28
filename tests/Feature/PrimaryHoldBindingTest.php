<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

function holdBindingWallet(): LockedWallet
{
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture, '50000')['data']['intent_id']);

    return app(WalletPostings::class)->lockForParty($fixture['party']->id);
}

function flushHoldBinding(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

it('requires the hold even when retained command version and ordinal evidence are valid', function (): void {
    expect(fn () => DB::transaction(function (): void {
        PrimaryReservationRecord::factory()->withInitialVersion(false)->create();
        flushHoldBinding();
    }))->toThrow(QueryException::class, 'requires its matching Party wallet hold');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(DB::table('primary_ordinal_claims')->count())->toBe(0);
});

it('refuses dangling foreign mispriced or misidentified holds in both directions', function (string $case): void {
    $wallet = holdBindingWallet();
    $foreign = $case === 'party' ? holdBindingWallet() : $wallet;
    expect(fn () => DB::transaction(function () use ($wallet, $foreign, $case): void {
        $root = $case === 'dangling' ? null : PrimaryReservationRecord::factory()->withInitialVersion(false)->create(['party_id' => $wallet->partyId]);
        $source = new PostingSource($case === 'type' ? 'primary_commitment' : 'primary_reservation',
            $case === 'dangling' || $case === 'source' ? strtolower((string) Str::ulid()) : $root->id,
            $case === 'dangling' || $case === 'operation' ? strtolower((string) Str::ulid()) : $root->origin_operation_id);
        $amount = match ($case) {
            'partial' => '4999', 'excessive' => '5001', default => '5000',
        };
        app(WalletPostings::class)->hold($foreign, WalletMoney::of($amount), $source);
        flushHoldBinding();
    }))->toThrow(QueryException::class, 'Primary');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0);
})->with(['dangling', 'source', 'type', 'party', 'operation', 'partial', 'excessive']);

it('defers the binding until reservation hold and journal evidence are all present', function (string $order): void {
    $wallet = holdBindingWallet();
    $root = PrimaryReservationRecord::factory()->make(['id' => strtolower((string) Str::ulid()), 'party_id' => $wallet->partyId]);
    $hold = fn () => app(WalletPostings::class)->hold($wallet, WalletMoney::of($root->principal),
        new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    if ($order === 'hold_first') {
        $hold();
    }
    $root->save();
    PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $root->id, 'revision' => 1,
        'operation_id' => $root->origin_operation_id, 'previous_sha256' => null, 'created_at' => $root->created_at]);
    if ($order === 'root_first') {
        $hold();
    }
    flushHoldBinding();
    expect(LedgerEntry::query()->where('kind', 'primary_hold')->sole()->source_id)->toBe($root->id);
})->with(['hold_first', 'root_first']);

it('checks the hold shape and exact principal independently of the wallet balance trigger', function (string $case): void {
    $wallet = holdBindingWallet();
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER ledger_entries_balanced');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER ledger_lines_entry_balanced');
    expect(fn () => DB::transaction(function () use ($wallet, $case): void {
        $root = PrimaryReservationRecord::factory()->withInitialVersion(false)->create(['party_id' => $wallet->partyId]);
        $entry = LedgerEntry::factory()->create(['wallet_id' => $wallet->walletId, 'kind' => 'primary_hold', 'source_type' => 'primary_reservation',
            'source_id' => $root->id, 'origin_operation_id' => $root->origin_operation_id]);
        $lines = match ($case) {
            'empty' => [],
            'partial' => [['investor_available', 'debit', '1'], ['investor_held', 'credit', '1']],
            'unbalanced' => [['investor_available', 'debit', '5000'], ['investor_held', 'credit', '4999']],
            'reversed' => [['investor_held', 'debit', '5000'], ['investor_available', 'credit', '5000']],
            'wrong_bucket' => [['investor_available', 'debit', '5000'], ['investor_committed', 'credit', '5000']],
            'split' => [['investor_available', 'debit', '5000'], ['investor_held', 'credit', '3000'], ['investor_held', 'credit', '2000']],
            default => throw new InvalidArgumentException('Unknown hold damage.'),
        };
        foreach ($lines as [$kind, $direction, $amount]) {
            $account = LedgerAccount::query()->where('wallet_id', $wallet->walletId)->where('kind', $kind)->first()
                ?? LedgerAccount::factory()->create(['wallet_id' => $wallet->walletId, 'kind' => $kind]);
            LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $account->id, 'direction' => $direction, 'amount' => $amount]);
        }
        flushHoldBinding();
    }))->toThrow(QueryException::class, 'exact principal');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0);
})->with(['empty', 'partial', 'unbalanced', 'reversed', 'wrong_bucket', 'split']);

it('keeps the retained original hold binding across later wallet movements', function (string $terminal): void {
    $wallet = holdBindingWallet();
    $root = PrimaryReservationRecord::factory()->withInitialVersion(false)->create(['party_id' => $wallet->partyId]);
    $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
    $postings = app(WalletPostings::class);
    $postings->hold($wallet, WalletMoney::of('5000'), $source);
    flushHoldBinding();
    if ($terminal === 'refund') {
        $postings->commit($wallet, WalletMoney::of('5000'), $source);
    }
    $postings->{$terminal}($wallet, WalletMoney::of('5000'), $source);
    flushHoldBinding();
    expect(LedgerEntry::query()->where('kind', 'primary_hold')->sole()->source_id)->toBe($root->id)
        ->and(LedgerEntry::query()->where('kind', 'primary_'.$terminal)->sole()->origin_operation_id)->toBe($root->origin_operation_id);
})->with(['commit', 'release', 'refund']);

it('validates retained holds on installation and refuses to drop protection after evidence exists', function (): void {
    $migration = require database_path('migrations/2026_09_28_161335_bind_primary_reservations_to_wallet_holds.php');
    $migration->down();
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    flushHoldBinding();
    $before = DB::table('ledger_entries')->where('source_id', $root->id)->value('payload');
    $migration->up();
    expect(DB::table('ledger_entries')->where('source_id', $root->id)->value('payload'))->toBe($before);
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration');
});

it('aborts installation atomically over incomplete or mismatched historical evidence', function (string $case): void {
    $migration = require database_path('migrations/2026_09_28_161335_bind_primary_reservations_to_wallet_holds.php');
    $migration->down();
    $wallet = holdBindingWallet();
    $other = $case === 'party' ? holdBindingWallet() : $wallet;
    $root = $case === 'dangling' ? null : PrimaryReservationRecord::factory()->withInitialVersion(false)->create(['party_id' => $wallet->partyId]);
    if ($case !== 'missing') {
        app(WalletPostings::class)->hold($other, WalletMoney::of($case === 'amount' ? '1000' : '5000'),
            new PostingSource('primary_reservation', $root->id ?? strtolower((string) Str::ulid()),
                $case === 'operation' ? strtolower((string) Str::ulid()) : ($root->origin_operation_id ?? strtolower((string) Str::ulid()))));
    }
    flushHoldBinding();
    $entries = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'Primary');
    expect(DB::selectOne("SELECT to_regprocedure('check_primary_hold_binding(character varying)') AS function")->function)->toBeNull()
        ->and(DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname IN ('primary_reservation_wallet_bound', 'ledger_primary_reservation_bound')")->total)->toBe(0)
        ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($entries);
})->with(['missing', 'dangling', 'party', 'operation', 'amount']);
