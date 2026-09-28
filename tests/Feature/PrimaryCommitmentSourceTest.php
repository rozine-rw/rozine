<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

it('refuses every raw commitment-source movement independently of lifecycle checks', function (string $kind): void {
    $fixture = InvestorWalletFixture::ready();
    $wallet = app(WalletPostings::class)->lockForParty($fixture['party']->id);
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER ledger_entries_primary_lifecycle');
    expect(fn () => DB::transaction(fn () => LedgerEntry::factory()->create(['wallet_id' => $wallet->walletId,
        'kind' => $kind, 'source_type' => 'primary_commitment', 'source_id' => strtolower((string) Str::ulid()),
        'origin_operation_id' => strtolower((string) Str::ulid())])))
        ->toThrow(QueryException::class, 'primary_commitment_source_unavailable');
    expect(LedgerEntry::query()->where('source_type', 'primary_commitment')->count())->toBe(0);
})->with(['primary_hold', 'primary_commit', 'primary_release', 'primary_refund']);

it('atomically refuses installation over previously accepted unbound cash evidence', function (): void {
    $migration = require database_path('migrations/2026_09_28_165949_reject_unbound_primary_commitment_sources.php');
    $migration->down();
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture)['data']['intent_id']);
    $wallets = app(WalletPostings::class);
    $wallets->hold($wallets->lockForParty($fixture['party']->id), WalletMoney::of('30000'),
        new PostingSource('primary_commitment', strtolower((string) Str::ulid()), strtolower((string) Str::ulid())));
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'primary_commitment_source_unavailable')
        ->and(DB::selectOne("SELECT count(*) AS total FROM pg_constraint WHERE conname = 'primary_commitment_source_unavailable'")->total)->toBe(0)
        ->and(LedgerEntry::query()->where('source_type', 'primary_commitment')->count())->toBe(1);
});

it('allows empty rollback and reinstall but refuses rollback once Primary cash is retained', function (): void {
    $migration = require database_path('migrations/2026_09_28_165949_reject_unbound_primary_commitment_sources.php');
    $migration->down();
    $migration->up();
    PrimaryReservationRecord::factory()->withInitialVersion()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration')
        ->and(DB::selectOne("SELECT count(*) AS total FROM pg_constraint WHERE conname = 'primary_commitment_source_unavailable'")->total)->toBe(1);
});
