<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
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
        ->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FULLY_COMMITTED')
        ->and(PrimaryCommitment::query()->count())->toBe(2)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
});

it('keeps damaged refund evidence distinct from an ordinary funding refusal', function (string $damage): void {
    $root = ($this->purchase)();
    ($this->purchase)();
    $wallets = app(WalletPostings::class);
    $refund = $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
        new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER USER');
    $credit = DB::table('ledger_lines')->where('entry_id', $refund->entryId)->where('direction', 'credit');
    if ($damage === 'amount') {
        $credit->update(['amount' => '1']);
    } elseif ($damage === 'missing credit') {
        $credit->delete();
    } elseif ($damage === 'origin') {
        DB::table('ledger_entries')->where('id', $refund->entryId)->update(['origin_operation_id' => $root->id]);
    } else {
        $heldAccount = DB::table('ledger_accounts')->where('wallet_id', $refund->walletId)->where('kind', 'investor_held')->value('id');
        $credit->update(['account_id' => $heldAccount]);
    }
    $before = [DB::table('ledger_entries')->orderBy('id')->get()->toJson(), DB::table('ledger_lines')->orderBy('id')->get()->toJson(),
        CommandOperation::query()->count(), DB::table('primary_campaign_fundings')->count()];
    expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and([DB::table('ledger_entries')->orderBy('id')->get()->toJson(), DB::table('ledger_lines')->orderBy('id')->get()->toJson(),
            CommandOperation::query()->count(), DB::table('primary_campaign_fundings')->count()])->toBe($before);
})->with(['amount', 'missing credit', 'origin', 'wrong bucket']);

it('preserves original cash integrity refusals when there is no refund evidence', function (bool $missingCommit): void {
    $root = ($this->purchase)();
    ($this->purchase)();
    $commit = LedgerEntry::query()->where('source_id', $root->id)->where('kind', 'primary_commit')->sole();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER USER');
    if ($missingCommit) {
        DB::table('ledger_lines')->where('entry_id', $commit->id)->delete();
        DB::table('ledger_entries')->where('id', $commit->id)->delete();
    } else {
        DB::table('ledger_lines')->where('entry_id', $commit->id)->where('direction', 'credit')->update(['amount' => '1']);
    }
    expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))
        ->toThrow(WalletViolation::class, $missingCommit ? 'PRIMARY_COMMITTED_CASH_REQUIRED' : 'WALLET_POSTING_CONFLICT')
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0)
        ->and(DB::table('primary_campaign_fundings')->count())->toBe(0);
})->with([true, false]);

it('does not classify returned hold cash as a verified refunded confirmation', function (): void {
    $root = ($this->purchase)();
    ($this->purchase)();
    $commit = LedgerEntry::query()->where('source_id', $root->id)->where('kind', 'primary_commit')->sole();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER USER');
    DB::table('ledger_entries')->where('id', $commit->id)->update(['kind' => 'primary_release']);
    $available = DB::table('ledger_accounts')->where('wallet_id', $commit->wallet_id)->where('kind', 'investor_available')->value('id');
    DB::table('ledger_lines')->where('entry_id', $commit->id)->where('direction', 'credit')->update(['account_id' => $available]);
    $before = [DB::table('ledger_entries')->orderBy('id')->get()->toJson(), DB::table('ledger_lines')->orderBy('id')->get()->toJson()];
    expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))
        ->toThrow(WalletViolation::class, 'PRIMARY_COMMITTED_CASH_REQUIRED')
        ->and(DB::table('primary_campaign_fundings')->count())->toBe(0)
        ->and([DB::table('ledger_entries')->orderBy('id')->get()->toJson(), DB::table('ledger_lines')->orderBy('id')->get()->toJson()])->toBe($before);
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
        ->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FULLY_COMMITTED');
})->with(['partial', 'held', 'refunded']);

it('replays digest-consistent confirmation stamps against the original half-open deadline', function (bool $atDeadline): void {
    $this->travelTo($this->campaign->expires_at->subSeconds(100));
    ($this->purchase)();
    $root = ($this->purchase)();
    $stamp = $atDeadline ? $this->campaign->expires_at : $this->campaign->expires_at->subMicrosecond();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $confirmed = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
    $payload = $confirmed->payload;
    $payload['recorded_at'] = $stamp->utc()->format('Y-m-d\TH:i:s.u\Z');
    DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER primary_reservation_versions_immutable');
    DB::statement('ALTER TABLE primary_commitments DISABLE TRIGGER primary_commitments_immutable');
    $confirmed->forceFill(['created_at' => $stamp, 'payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    PrimaryCommitment::query()->where('primary_reservation_version_id', $confirmed->id)->sole()->forceFill(['confirmed_at' => $stamp, 'created_at' => $stamp])->save();
    $this->travelTo($this->campaign->expires_at->addDay());
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $operations = CommandOperation::query()->count();
    if ($atDeadline) {
        expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))
            ->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
    } else {
        expect(app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id)->purchases)->toHaveCount(2);
    }
    expect(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(CommandOperation::query()->count())->toBe($operations);
})->with(['at deadline' => true, 'last microsecond' => false]);
