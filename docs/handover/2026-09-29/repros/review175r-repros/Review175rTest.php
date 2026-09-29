<?php

declare(strict_types=1);

/*
 * Review 175r repros for PrimaryCommittedCash (e12a6ea9). Copy into tests/Feature to run.
 * Synthetic cases drop schema guards INSIDE the RefreshDatabase transaction, so they roll back.
 */

use App\Application\Wallet\Contracts\PrimaryCommittedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->commitment = PrimaryCommitment::factory()->create();
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->wallet = app(WalletPostings::class)->lockForParty($this->root->party_id);
    $this->source = new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id);
    $this->cash = app(PrimaryCommittedCash::class);
});

/** Removes the schema guards so a movement the current schema forbids can be simulated (rolled back). */
function review175rUnguard(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source');
    DB::statement('ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entries_kind_source_type_source_id_unique');
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER USER');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

function review175rEntry(string $walletId, string $kind, string $sourceType, string $sourceId, string $origin): string
{
    $id = strtolower((string) Str::ulid());
    DB::table('ledger_entries')->insert(['id' => $id, 'wallet_id' => $walletId, 'kind' => $kind, 'source_type' => $sourceType,
        'source_id' => $sourceId, 'origin_operation_id' => $origin, 'currency' => 'RWF', 'payload' => '{}',
        'sha256' => str_repeat('0', 64), 'created_at' => now()]);

    return $id;
}

it('R1 release after a commit is refused by the posting state machine, so it cannot precede a funding read', function (): void {
    expect(fn () => app(WalletPostings::class)->release($this->wallet, WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID');
    expect($this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source)->reservationId)->toBe($this->root->id);
});

it('R2 a simulated #176 primary_issue after the commit refuses funding evidence', function (): void {
    review175rUnguard();
    review175rEntry($this->wallet->walletId, 'primary_issue', 'primary_reservation', $this->source->id, $this->source->originOperationId);
    expect(fn () => $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'PRIMARY_COMMITTED_CASH_REQUIRED');
});

it('R2b an unknown future kind after the commit refuses funding evidence', function (): void {
    review175rUnguard();
    review175rEntry($this->wallet->walletId, 'primary_adjustment', 'primary_reservation', $this->source->id, $this->source->originOperationId);
    expect(fn () => $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'PRIMARY_COMMITTED_CASH_REQUIRED');
});

it('R3 source_type case variants cannot be stored or named', function (string $variant): void {
    expect(fn () => DB::transaction(fn () => LedgerEntry::factory()->create(['wallet_id' => $this->wallet->walletId, 'kind' => 'primary_refund',
        'source_type' => $variant, 'source_id' => $this->source->id, 'origin_operation_id' => $this->source->originOperationId])))
        ->toThrow(QueryException::class);
    expect(fn () => new PostingSource($variant, $this->source->id, $this->source->originOperationId))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_SOURCE_INVALID');
    expect(fn () => new PostingSource('primary_reservation', strtoupper($this->source->id), $this->source->originOperationId))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_SOURCE_INVALID');
})->with(['Primary_Reservation', 'PRIMARY_RESERVATION', 'primary_reservation ']);

it('R3b the port relies on the schema: a variant-typed refund would be invisible to it', function (): void {
    review175rUnguard();
    review175rEntry($this->wallet->walletId, 'primary_refund', 'Primary_Reservation', $this->source->id, $this->source->originOperationId);
    // Documented dependency, not a live exploit: ledger_entry_source forbids this row in the real schema (R3).
    expect($this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source)->reservationId)->toBe($this->root->id);
});

it('R4 refuses partial and inflated amounts', function (string $amount): void {
    expect(fn () => $this->cash->requireCommitted($this->wallet, WalletMoney::of($amount), $this->source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
})->with(['0', '1', '2500', '4999', '5001', '10000']);

it('R5 a second hold/commit pair on the same source is replayed (not written) by postings and refused by the port', function (): void {
    $count = LedgerEntry::query()->count();
    expect(app(WalletPostings::class)->hold($this->wallet, WalletMoney::of('5000'), $this->source)->replayed)->toBeTrue()
        ->and(app(WalletPostings::class)->commit($this->wallet, WalletMoney::of('5000'), $this->source)->replayed)->toBeTrue()
        ->and(LedgerEntry::query()->count())->toBe($count);
    review175rUnguard();
    review175rEntry($this->wallet->walletId, 'primary_hold', 'primary_reservation', $this->source->id, $this->source->originOperationId);
    review175rEntry($this->wallet->walletId, 'primary_commit', 'primary_reservation', $this->source->id, $this->source->originOperationId);
    expect(fn () => $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'PRIMARY_COMMITTED_CASH_REQUIRED');
});

it('R6 a same-wallet same-origin movement under another source does not satisfy or poison this source', function (): void {
    $other = PrimaryReservationRecord::factory()->withInitialVersion()->create(['party_id' => $this->root->party_id]);
    $otherSource = new PostingSource('primary_reservation', $other->id, $other->origin_operation_id);
    expect(fn () => $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $otherSource))
        ->toThrow(WalletViolation::class, 'PRIMARY_COMMITTED_CASH_REQUIRED');
    // The same reservation id under this source's origin but a borrowed origin from the other source.
    expect(fn () => $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'),
        new PostingSource('primary_reservation', $this->source->id, $other->origin_operation_id)))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
});

it('R7 is read-only: only SELECTs, no tuple writes, no new rows', function (): void {
    $tables = fn (): array => collect(DB::select('SELECT relname, n_tup_ins, n_tup_upd, n_tup_del FROM pg_stat_xact_user_tables ORDER BY relname'))
        ->mapWithKeys(fn (object $row): array => [$row->relname => [(int) $row->n_tup_ins, (int) $row->n_tup_upd, (int) $row->n_tup_del]])->all();
    $rows = fn (): string => json_encode([DB::table('ledger_entries')->orderBy('id')->get(), DB::table('ledger_lines')->orderBy('id')->get(),
        DB::table('ledger_accounts')->orderBy('id')->get(), DB::table('investor_wallets')->orderBy('id')->get()]);
    $beforeStats = $tables();
    $beforeRows = $rows();
    $statements = [];
    DB::listen(function ($query) use (&$statements): void {
        $statements[] = $query->sql;
    });
    $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source);
    $mine = $statements;
    DB::flushQueryLog();
    $afterStats = $tables();
    expect($afterStats)->toBe($beforeStats)
        ->and($rows())->toBe($beforeRows)
        ->and(collect($mine)->reject(fn (string $sql): bool => str_starts_with(strtolower(ltrim($sql)), 'select'))->all())->toBe([])
        ->and(collect($mine)->filter(fn (string $sql): bool => str_contains($sql, 'for update'))->count())->toBe(1);
    fwrite(STDERR, 'R7 statements: '.count($mine)."\n".implode("\n", $mine)."\n");
});

it('R8 committedAt is the commit time, not the hold time', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create(['party_id' => $this->root->party_id]);
    $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
    $this->travel(40)->seconds();
    PrimaryReservationFixture::terminalVersion($source, 'confirmed');
    app(WalletPostings::class)->commit($this->wallet, WalletMoney::of('5000'), $source);
    $hold = LedgerEntry::query()->where('source_id', $root->id)->where('kind', 'primary_hold')->sole();
    $commit = LedgerEntry::query()->where('source_id', $root->id)->where('kind', 'primary_commit')->sole();
    $evidence = $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $source);
    expect($hold->created_at->equalTo($commit->created_at))->toBeFalse()
        ->and($evidence->committedAt)->toEqual($commit->created_at->toDateTimeImmutable())
        ->and([$evidence->holdEntryId, $evidence->commitEntryId])->toBe([$hold->id, $commit->id]);
});

it('R9 an entry whose header wallet matches but whose lines post to another wallet is refused', function (): void {
    $other = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $otherWallet = app(WalletPostings::class)->lockForParty($other->party_id);
    $otherHeld = DB::table('ledger_accounts')->where('wallet_id', $otherWallet->walletId)->where('kind', 'investor_held')->value('id');
    $commitId = LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_commit')->value('id');
    review175rUnguard();
    DB::table('ledger_lines')->where('entry_id', $commitId)->where('direction', 'debit')->update(['account_id' => $otherHeld]);
    expect(fn () => $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
});
