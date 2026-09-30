<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\CommandOperation;
use App\Models\InvestorFundingMethod;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    /** @param array{user: User, party: Party, method: InvestorFundingMethod}|null $investor */
    $this->purchase = function (string $units = '1080', bool $confirm = true, ?array $investor = null): PrimaryReservationRecord {
        $investor ??= PrimaryReservationFixture::investor();
        $checkout = app(PrimaryCheckout::class);
        $result = $checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        if ($confirm) {
            $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
            expect($checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1,
                $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
        }

        return $root;
    };
});

it('retains complete exact rights and original cash without creating funding or ledger writes', function (): void {
    $roots = [($this->purchase)(), ($this->purchase)()];
    $cash = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    $operations = CommandOperation::query()->count();
    $candidate = app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id);
    expect($candidate->campaignId)->toBe($this->campaign->id)->and($candidate->principal)->toBe('10800000')
        ->and($candidate->purchases)->toHaveCount(2)
        ->and(array_column($candidate->purchases, 'reservation_id'))->toBe(collect($roots)->pluck('id')->sort()->values()->all());
    foreach ($candidate->purchases as $purchase) {
        $entry = LedgerEntry::query()->where('source_id', $purchase['reservation_id'])->where('kind', 'primary_commit')->sole();
        expect($purchase['cash']->commitEntryId)->toBe($entry->id)->and($purchase['cash']->amount)->toBe('5400000')
            ->and($purchase['reservation']->state)->toBe('confirmed')
            ->and((string) $purchase['reservation']->rights->ordinals->count)->toBe('1080')
            ->and($purchase['commitment_id'])->toBe(PrimaryCommitment::query()->where('primary_reservation_id', $purchase['reservation_id'])->sole()->id);
    }
    expect(app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))->toEqual($candidate)
        ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(CommandOperation::query()->count())->toBe($operations);
});

it('refuses empty partial and held capacity as complete funding evidence', function (string $case): void {
    if ($case !== 'empty') {
        ($this->purchase)('1080', $case !== 'held');
    }
    expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))
        ->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FULLY_COMMITTED');
})->with(['empty', 'partial', 'held']);

it('refuses a full retained raise if any original committed cash has been refunded', function (): void {
    $first = ($this->purchase)();
    ($this->purchase)();
    $wallets = app(WalletPostings::class);
    $wallets->refund($wallets->lockForParty($first->party_id), WalletMoney::of($first->principal),
        new PostingSource('primary_reservation', $first->id, $first->origin_operation_id));
    expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))
        ->toThrow(WalletViolation::class, 'PRIMARY_COMMITTED_CASH_REQUIRED')
        ->and(PrimaryCommitment::query()->count())->toBe(2)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
});

it('rejects mismatched retained commitment evidence before reading cash', function (): void {
    ($this->purchase)();
    ($this->purchase)();
    $commitment = PrimaryCommitment::query()->firstOrFail();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(fn () => DB::transaction(function () use ($commitment): void {
        DB::statement('ALTER TABLE primary_commitments DISABLE TRIGGER primary_commitments_immutable');
        $commitment->forceFill(['confirmed_at' => $commitment->confirmed_at->subSecond()])->save();
        app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id);
    }))->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
});

it('locks every Primary row before wallets and every wallet before checking ledger movements', function (): void {
    $earlier = PrimaryReservationFixture::investor();
    $later = PrimaryReservationFixture::investor();
    $roots = [($this->purchase)(investor: $later), ($this->purchase)(investor: $earlier)];
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = [$query->sql, $query->bindings];
    });
    app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id);
    $rootLock = array_find_key($queries, fn (array $query): bool => str_contains($query[0], 'from "primary_reservations"') && str_contains($query[0], 'for update'));
    $commitmentLock = array_find_key($queries, fn (array $query): bool => str_contains($query[0], 'from "primary_commitments"') && str_contains($query[0], 'for update'));
    $walletLocks = array_filter($queries, fn (array $query): bool => str_contains($query[0], 'from "investor_wallets"') && str_contains($query[0], '"party_id" = ?') && str_contains($query[0], 'for update'));
    $firstLedger = array_find_key($queries, fn (array $query): bool => str_contains($query[0], 'from "ledger_entries"'));
    expect($rootLock)->toBeInt()->and($commitmentLock)->toBeInt()->and($rootLock)->toBeLessThan($commitmentLock)
        ->and($commitmentLock)->toBeLessThan(array_key_first($walletLocks));
    $firstLocks = array_slice($walletLocks, 0, 2, true);
    expect(array_map(fn (array $query): string => $query[1][0], array_values($firstLocks)))
        ->toBe(collect($roots)->pluck('party_id')->sort()->values()->all())
        ->and(array_key_last($firstLocks))->toBeLessThan($firstLedger);
});

it('keeps a fully committed candidate when the deadline passes during cash verification', function (): void {
    ($this->purchase)();
    ($this->purchase)();
    $elapsed = false;
    DB::listen(function (QueryExecuted $query) use (&$elapsed): void {
        if (! $elapsed && str_contains($query->sql, 'from "ledger_entries"')) {
            $elapsed = true;
            $this->travelTo($this->campaign->expires_at);
        }
    });
    expect(app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id)->purchases)
        ->toHaveCount(2)->and($elapsed)->toBeTrue();
});

it('keeps predeadline commitments eligible for settlement after the publication clock ends', function (bool $afterDeadline): void {
    ($this->purchase)();
    ($this->purchase)();
    $candidate = app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id);
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $operations = CommandOperation::query()->count();
    $this->travelTo($afterDeadline ? $this->campaign->expires_at->addDay() : $this->campaign->expires_at);
    expect(app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))->toEqual($candidate)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(CommandOperation::query()->count())->toBe($operations);
})->with([false, true]);

it('still refuses incomplete or returned commitment cash after the deadline', function (string $state): void {
    $first = ($this->purchase)(confirm: $state !== 'held');
    if ($state === 'refunded') {
        ($this->purchase)();
        $wallets = app(WalletPostings::class);
        $wallets->refund($wallets->lockForParty($first->party_id), WalletMoney::of($first->principal),
            new PostingSource('primary_reservation', $first->id, $first->origin_operation_id));
    }
    $this->travelTo($this->campaign->expires_at);
    expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))
        ->toThrow($state === 'refunded' ? WalletViolation::class : CommandRejection::class,
            $state === 'refunded' ? 'PRIMARY_COMMITTED_CASH_REQUIRED' : 'CAMPAIGN_NOT_FULLY_COMMITTED');
})->with(['partial', 'held', 'refunded']);
