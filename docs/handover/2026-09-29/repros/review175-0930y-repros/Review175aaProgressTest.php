<?php

declare(strict_types=1);

/* Review #175 range 3bb74427..d753c794 (R1): refund-aware progress projection. Repro only, not for merge. */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Brick\Math\BigInteger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->checkout = app(PrimaryCheckout::class);
    $this->investors = [PrimaryReservationFixture::investor(), PrimaryReservationFixture::investor()];
    $this->reserve = function (int $who, string $units): PrimaryReservationRecord {
        $result = $this->checkout->reserve($this->investors[$who]['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->confirm = function (int $who, PrimaryReservationRecord $root): PrimaryReservationRecord {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
        $this->checkout->confirm($this->investors[$who]['user']->id, 1, $this->campaign->id, $root->id, $version->revision,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return $root;
    };
    $this->refund = function (PrimaryReservationRecord $root): void {
        $wallets = app(WalletPostings::class);
        $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    };
    $this->page = fn (): array => app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    $this->reconciles = function (array $page): array {
        $p = $page['progress'];
        $units = BigInteger::of($p['units']['committed'])->plus($p['units']['reserved'])->plus($p['units']['available'])->plus($p['units']['unavailable']);
        $money = BigInteger::of($p['committed']['amount'])->plus($p['reserved']['amount'])->plus($p['remaining']['amount']);

        return ['units' => (string) $units === $p['units']['total'], 'money' => (string) $money === $page['principal'],
            'lifecycle_parity' => $page['lifecycle'] === $p['lifecycle'],
            'sellable_principal' => (string) BigInteger::of($p['units']['available'])->multipliedBy(5000), 'remaining' => $p['remaining']['amount']];
    };
});

it('AA-1 reconciles units and money in a mixed state and prints the exact progress shape', function (): void {
    $kept = ($this->confirm)(0, ($this->reserve)(0, '3'));
    $refunded = ($this->confirm)(0, ($this->reserve)(0, '2'));
    $other = ($this->confirm)(1, ($this->reserve)(1, '4'));
    ($this->reserve)(1, '5');
    $released = ($this->reserve)(1, '6');
    $this->checkout->release($this->investors[1]['user']->id, 1, $this->campaign->id, $released->id, 1, (string) Str::uuid());
    ($this->refund)($refunded);
    ($this->refund)($other);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $page = ($this->page)();
    fwrite(STDERR, "\nAA-1 progress=".json_encode($page['progress'])."\nAA-1 keys=".json_encode(array_keys($page['progress'])).' units keys='.json_encode(array_keys($page['progress']['units']))
        ."\nAA-1 summary=".json_encode(app(CampaignReservationSummary::class)->read($this->campaign->id, now()->toDateTimeImmutable()))
        ."\nAA-1 reconcile=".json_encode(($this->reconciles)($page))."\n");
    expect(($this->reconciles)($page))->toMatchArray(['units' => true, 'money' => true, 'lifecycle_parity' => true])
        ->and($page['progress'])->toMatchArray(['investors' => 1, 'committed' => ['currency' => 'RWF', 'amount' => '15000'], 'reserved' => ['currency' => 'RWF', 'amount' => '25000'],
            'remaining' => ['currency' => 'RWF', 'amount' => '10760000'],
            'units' => ['total' => '2160', 'available' => '2140', 'reserved' => '5', 'committed' => '3', 'unavailable' => '12']]);
});

it('AA-2 a sold-out raise with one refund: lifecycle, reconciliation and what the money says', function (): void {
    $first = ($this->confirm)(0, ($this->reserve)(0, '1080'));
    ($this->confirm)(1, ($this->reserve)(1, '1080'));
    $before = ($this->page)();
    ($this->refund)($first);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $after = ($this->page)();
    fwrite(STDERR, "\nAA-2 before lifecycle=".$before['lifecycle'].' after='.$after['lifecycle'].'/'.$after['progress']['lifecycle'].' progress='.json_encode($after['progress'])
        .' reconcile='.json_encode(($this->reconciles)($after)).' can_cancel='.json_encode($after['can_cancel'])."\n");
    $retry = null;
    try {
        $retry = $this->checkout->reserve($this->investors[1]['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
    } catch (Throwable $exception) {
        $retry = $exception->getMessage();
    }
    fwrite(STDERR, 'AA-2 new reserve after refund: '.json_encode($retry)."\n");
    $this->travelTo($this->campaign->expires_at);
    $late = ($this->page)();
    $sweep = null;
    try {
        $sweep = app(BusinessCampaignStore::class)->expireDue(10);
    } catch (Throwable $exception) {
        $sweep = $exception::class.' '.$exception->getMessage();
    }
    fwrite(STDERR, 'AA-2 at deadline lifecycle='.$late['lifecycle'].' campaigns:expire='.json_encode($sweep)."\n");
    $candidate = null;
    try {
        DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id));
        $candidate = 'returned a candidate';
    } catch (Throwable $exception) {
        $candidate = $exception::class.' '.$exception->getMessage();
    }
    fwrite(STDERR, 'AA-2 funding candidate after refund: '.$candidate."\n");
    expect($before['lifecycle'])->toBe('sold_out_pending_settlement')->and(($this->reconciles)($after))->toMatchArray(['units' => true, 'money' => true, 'lifecycle_parity' => true]);
});

it('AA-3 a funded raise cannot be refunded, so funded progress never drops below 100', function (): void {
    ($this->confirm)(0, ($this->reserve)(0, '1080'));
    $second = ($this->confirm)(1, ($this->reserve)(1, '1080'));
    $admit = fn (string $id): array => ['campaign_id' => $id, 'publication_sha256' => $this->campaign->sha256,
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'repro']])];
    app(PrimaryFunding::class)->lock($this->campaign->id, $admit);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $refused = null;
    try {
        DB::transaction(fn () => ($this->refund)($second));
    } catch (Throwable $exception) {
        $refused = $exception::class.' '.mb_substr($exception->getMessage(), 0, 120);
    }
    $page = ($this->page)();
    fwrite(STDERR, "\nAA-3 refund after funding: ".json_encode($refused).' progress='.json_encode($page['progress'])."\n");
    expect($refused)->not->toBeNull()->and($page['progress'])->toMatchArray(['phase' => 'funded', 'lifecycle' => 'funded_pending_disbursement', 'funded_pct' => '100.0'])
        ->and(($this->reconciles)($page))->toMatchArray(['units' => true, 'money' => true, 'lifecycle_parity' => true]);
});

it('AA-4 the wallet port refuses a partial or repeated-different refund', function (string $amount): void {
    $root = ($this->confirm)(0, ($this->reserve)(0, '4'));
    $wallets = app(WalletPostings::class);
    $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
    expect(fn () => $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($amount), $source))->toThrow(App\Domain\Wallet\WalletViolation::class);
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0)
        ->and(($this->page)()['progress']['committed']['amount'])->toBe('20000');
})->with(['5000', '19999', '20001']);

it('AA-5 forged refunds: which fail closed and which are silently projected', function (string $forgery): void {
    $root = ($this->confirm)(0, ($this->reserve)(0, '4'));
    $other = ($this->confirm)(1, ($this->reserve)(1, '4'));
    $held = ($this->reserve)(1, '2');
    $sameHeld = ($this->reserve)(0, '4');
    ($this->refund)($root);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $entry = LedgerEntry::query()->where('kind', 'primary_refund')->sole();
    $otherWallet = DB::table('investor_wallets')->where('party_id', $other->party_id)->value('id');
    foreach (['ledger_entries', 'ledger_lines', 'ledger_accounts'] as $table) {
        DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER USER');
    }
    $line = fn (string $direction) => DB::table('ledger_lines')->where('entry_id', $entry->id)->where('direction', $direction);
    $account = fn (string $wallet, string $kind) => DB::table('ledger_accounts')->where('wallet_id', $wallet)->where('kind', $kind)->value('id');
    match ($forgery) {
        'none (control)' => null,
        'partial both lines' => DB::table('ledger_lines')->where('entry_id', $entry->id)->update(['amount' => '19999']),
        'over-refund both lines' => DB::table('ledger_lines')->where('entry_id', $entry->id)->update(['amount' => '20001']),
        'credit line only short' => $line('credit')->update(['amount' => '1']),
        'swapped directions' => DB::statement("UPDATE ledger_lines SET direction = CASE direction WHEN 'debit' THEN 'credit' ELSE 'debit' END WHERE entry_id = ?", [$entry->id]),
        'credit to other wallet available' => $line('credit')->update(['account_id' => $account($otherWallet, 'investor_available')]),
        'debit from held not committed' => $line('debit')->update(['account_id' => $account($entry->wallet_id, 'investor_held')]),
        'credit to held not available' => $line('credit')->update(['account_id' => $account($entry->wallet_id, 'investor_held')]),
        'third zero-sum pair added' => DB::table('ledger_lines')->insert([
            ['id' => strtolower((string) Str::ulid()), 'entry_id' => $entry->id, 'account_id' => $account($entry->wallet_id, 'investor_available'), 'direction' => 'debit', 'amount' => '5', 'created_at' => now()],
            ['id' => strtolower((string) Str::ulid()), 'entry_id' => $entry->id, 'account_id' => $account($entry->wallet_id, 'investor_held'), 'direction' => 'credit', 'amount' => '5', 'created_at' => now()]]),
        'no lines at all' => DB::table('ledger_lines')->where('entry_id', $entry->id)->delete(),
        'rebound to the other investor reservation (same campaign)' => DB::table('ledger_entries')->where('id', $entry->id)->update(['source_id' => $other->id]),
        'rebound to a held reservation' => DB::table('ledger_entries')->where('id', $entry->id)->update(['source_id' => $held->id]),
        'rebound to a same-party same-amount HELD reservation with its operation' => DB::table('ledger_entries')->where('id', $entry->id)->update(['source_id' => $sameHeld->id, 'origin_operation_id' => $sameHeld->origin_operation_id]),
        'entry currency USD' => (function () use ($entry): void {
            DB::statement('ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_currency');
            DB::table('ledger_entries')->where('id', $entry->id)->update(['currency' => 'USD']);
        })(),
        'debit line only short' => $line('debit')->update(['amount' => '1']),
        'credit line only over' => $line('credit')->update(['amount' => '20001']),
        'debit line only over' => $line('debit')->update(['amount' => '20001']),
        'debit from other wallet committed' => $line('debit')->update(['account_id' => $account($otherWallet, 'investor_committed')]),
        'rebound with matching operation to other reservation' => DB::table('ledger_entries')->where('id', $entry->id)->update(['source_id' => $other->id, 'origin_operation_id' => $other->origin_operation_id]),
        'fully rebound to other investor (wallet, op, accounts)' => (function () use ($entry, $other, $otherWallet, $line, $account): void {
            DB::table('ledger_entries')->where('id', $entry->id)->update(['source_id' => $other->id, 'origin_operation_id' => $other->origin_operation_id, 'wallet_id' => $otherWallet]);
            $line('credit')->update(['account_id' => $account($otherWallet, 'investor_available')]);
            $line('debit')->update(['account_id' => $account($otherWallet, 'investor_committed')]);
        })(),
        'commit entry deleted under the refund' => (function () use ($root): void {
            $commit = DB::table('ledger_entries')->where('source_id', $root->id)->where('kind', 'primary_commit')->value('id');
            DB::table('ledger_lines')->where('entry_id', $commit)->delete();
            DB::table('ledger_entries')->where('id', $commit)->delete();
        })(),
        'account currency USD' => (function () use ($entry, $account): void {
            DB::statement('ALTER TABLE ledger_accounts DROP CONSTRAINT IF EXISTS ledger_account_currency');
            try {
                DB::table('ledger_accounts')->where('id', $account($entry->wallet_id, 'investor_available'))->update(['currency' => 'USD']);
            } catch (Throwable $exception) {
                fwrite(STDERR, 'AA-5 currency forge not possible: '.mb_substr($exception->getMessage(), 0, 160)."\n");
            }
        })(),
        'kind renamed primary_release' => DB::table('ledger_entries')->where('id', $entry->id)->update(['kind' => 'primary_release']),
        default => throw new InvalidArgumentException($forgery),
    };
    $outcome = null;
    try {
        $summary = app(CampaignReservationSummary::class)->read($this->campaign->id, now()->toDateTimeImmutable());
        $outcome = 'PROJECTED committed='.$summary['committed_principal'].' returned='.$summary['returned_principal'].' investors='.$summary['investors'].' occupied='.$summary['occupied_units'];
    } catch (Throwable $exception) {
        $outcome = 'REFUSED '.$exception->getMessage();
    }
    fwrite(STDERR, 'AA-5 ['.$forgery.'] '.$outcome."\n");
    $legitimate = ['none (control)', 'fully rebound to other investor (wallet, op, accounts)', 'commit entry deleted under the refund', 'kind renamed primary_release'];
    expect(str_starts_with($outcome, 'REFUSED'))->toBe(! in_array($forgery, $legitimate, true));
})->with(['rebound to a same-party same-amount HELD reservation with its operation', 'entry currency USD', 'debit line only short', 'credit line only over', 'debit line only over', 'debit from other wallet committed', 'none (control)', 'partial both lines', 'over-refund both lines', 'credit line only short', 'swapped directions', 'credit to other wallet available',
    'debit from held not committed', 'credit to held not available', 'third zero-sum pair added', 'no lines at all',
    'rebound to the other investor reservation (same campaign)', 'rebound to a held reservation', 'rebound with matching operation to other reservation',
    'fully rebound to other investor (wallet, op, accounts)', 'commit entry deleted under the refund', 'account currency USD', 'kind renamed primary_release']);

it('AA-6 a duplicated refund entry (unique index removed) and what the projection then says', function (): void {
    $root = ($this->confirm)(0, ($this->reserve)(0, '4'));
    ($this->confirm)(1, ($this->reserve)(1, '3'));
    ($this->refund)($root);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $entry = LedgerEntry::query()->where('kind', 'primary_refund')->sole();
    $unique = DB::selectOne("SELECT conname FROM pg_constraint WHERE conrelid = 'ledger_entries'::regclass AND contype = 'u' AND pg_get_constraintdef(oid) LIKE '%kind, source_type, source_id%'");
    fwrite(STDERR, "\nAA-6 unique constraint backing one refund per source: ".json_encode($unique?->conname)."\n");
    DB::statement('ALTER TABLE ledger_entries DROP CONSTRAINT '.$unique->conname);
    foreach (['ledger_entries', 'ledger_lines'] as $table) {
        DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER USER');
    }
    $copy = strtolower((string) Str::ulid());
    DB::statement('INSERT INTO ledger_entries (id, wallet_id, kind, source_type, source_id, origin_operation_id, currency, payload, sha256, created_at)
        SELECT ?, wallet_id, kind, source_type, source_id, origin_operation_id, currency, payload, sha256, created_at FROM ledger_entries WHERE id = ?', [$copy, $entry->id]);
    foreach (DB::table('ledger_lines')->where('entry_id', $entry->id)->get() as $line) {
        DB::table('ledger_lines')->insert(['id' => strtolower((string) Str::ulid()), 'entry_id' => $copy, 'account_id' => $line->account_id, 'direction' => $line->direction, 'amount' => $line->amount, 'created_at' => $line->created_at]);
    }
    try {
        $summary = app(CampaignReservationSummary::class)->read($this->campaign->id, now()->toDateTimeImmutable());
        fwrite(STDERR, 'AA-6 summary PROJECTED '.json_encode($summary)."\n");
        try {
            fwrite(STDERR, 'AA-6 page progress='.json_encode(($this->page)()['progress'])."\n");
        } catch (Throwable $exception) {
            fwrite(STDERR, 'AA-6 page REFUSED '.$exception->getMessage()."\n");
        }
    } catch (Throwable $exception) {
        fwrite(STDERR, 'AA-6 summary REFUSED '.$exception->getMessage()."\n");
    }
    expect(true)->toBeTrue();
});

it('AA-7 browser and token reads return the identical note.progress and lifecycle with a refund present', function (): void {
    ($this->confirm)(0, ($this->reserve)(0, '3'));
    ($this->refund)(($this->confirm)(1, ($this->reserve)(1, '2')));
    ($this->reserve)(1, '4');
    $parameters = ['business' => $this->campaign->business_id, 'campaign' => $this->campaign->id];
    $actor = User::query()->findOrFail($this->campaign->actor_user_id);
    $browser = $this->actingAs($actor)->get(route('business.campaigns.show', $parameters))->assertOk()->viewData('page')['props'];
    Sanctum::actingAs($actor, ['business:read']);
    $token = $this->getJson(route('api.v1.business.campaigns.show', $parameters))->assertOk()->json('data');
    fwrite(STDERR, "\nAA-7 browser note.progress=".json_encode($browser['note']['progress'])."\nAA-7 token   note.progress=".json_encode($token['note']['progress'])
        ."\nAA-7 browser top keys=".json_encode(array_keys($browser)).' campaign='.json_encode($browser['campaign'] ?? null)
        ."\nAA-7 token top keys=".json_encode(array_keys($token)).' campaign='.json_encode($token['campaign'] ?? null)."\n");
    expect($browser['note']['progress'])->toBe($token['note']['progress'])
        ->and($token['campaign']['lifecycle'] ?? $token['lifecycle'] ?? null)->toBe($token['note']['progress']['lifecycle'])
        ->and($browser['campaign']['lifecycle'] ?? $browser['lifecycle'] ?? null)->toBe($browser['note']['progress']['lifecycle']);
});
