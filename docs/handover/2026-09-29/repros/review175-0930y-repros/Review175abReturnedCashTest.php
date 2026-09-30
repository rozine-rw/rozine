<?php

declare(strict_types=1);

/* Review #175 range d753c794..494e12d1 (R2): retained cash-return verification. Repro only, not for merge. */

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\CommandOperation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->other = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->hold = function (array $investor, string $units = '3'): PrimaryReservationRecord {
        $result = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->confirm = function (array $investor, PrimaryReservationRecord $root): void {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        $this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1, $version->payload['terms']['disclosure_version'],
            $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    };
    $this->root = ($this->hold)($this->investor);
    $this->walletId = InvestorWallet::query()->where('party_id', $this->root->party_id)->value('id');
    $this->otherWalletId = InvestorWallet::query()->where('party_id', $this->other['party']->id)->value('id');
    $this->account = fn (string $wallet, string $kind) => DB::table('ledger_accounts')->where('wallet_id', $wallet)->where('kind', $kind)->value('id');
    $this->forge = function (Closure $change): void {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        foreach (['ledger_entries', 'ledger_lines'] as $table) {
            DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER USER');
        }
        $change();
        foreach (['ledger_entries', 'ledger_lines'] as $table) {
            DB::statement('ALTER TABLE '.$table.' ENABLE TRIGGER USER');
        }
    };
    $this->forgeHoldDebit = fn (PrimaryReservationRecord $root) => ($this->forge)(fn () => DB::table('ledger_lines')
        ->where('entry_id', LedgerEntry::query()->where('source_id', $root->id)->where('kind', 'primary_hold')->value('id'))
        ->where('direction', 'debit')->update(['account_id' => ($this->account)($this->otherWalletId, 'investor_available')]));
    $this->breakdown = fn (): array => array_map(fn (array $money): string => $money['amount'],
        array_intersect_key(app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown'], ['available' => 1, 'held' => 1, 'committed' => 1]));
    $this->verify = function (PrimaryReservationRecord $root, ?string $amount = null): string {
        $walletId = InvestorWallet::query()->where('party_id', $root->party_id)->value('id');
        try {
            DB::beginTransaction();
            $evidence = app(PrimaryReturnedCash::class)->requireReturned(new LockedWallet($walletId, $root->party_id), WalletMoney::of($amount ?? $root->principal),
                new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));

            return 'ACCEPTED '.$evidence->returnKind;
        } catch (Throwable $exception) {
            return 'REFUSED '.$exception->getMessage();
        } finally {
            DB::rollBack();
        }
    };
});

it('AB-1 lock order of actor release and system expiry, with the verifier included', function (string $path): void {
    if ($path === 'expire') {
        $this->travelTo($this->root->expires_at);
    }
    $locks = [];
    DB::listen(function (QueryExecuted $query) use (&$locks): void {
        if (str_contains($query->sql, 'for update') && preg_match('/from "([a-z_]+)"/', $query->sql, $match)) {
            $locks[] = $match[1];
        }
    });
    $path === 'release' ? $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid())
        : app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id);
    fwrite(STDERR, "\nAB-1 [".$path.'] FOR UPDATE order: '.implode(' > ', $locks)."\n");
    $firstWallet = array_search('investor_wallets', $locks, true);
    expect($firstWallet)->toBeInt()->and(array_search('business_profiles', $locks, true))->toBeLessThan($firstWallet)
        ->and(array_search('business_campaigns', $locks, true))->toBeLessThan($firstWallet)
        ->and(array_search('primary_reservations', $locks, true))->toBeLessThan($firstWallet)
        ->and(array_unique(array_slice($locks, $firstWallet)))->toBe(['investor_wallets']);
})->with(['release', 'expire']);

it('AB-2 a REAL verifier refusal (hold debit line forged to another wallet) rolls back every path', function (string $path): void {
    ($this->forgeHoldDebit)($this->root);
    if ($path !== 'release') {
        $this->travelTo($this->root->expires_at);
    }
    $before = ['versions' => PrimaryReservationVersion::query()->count(), 'entries' => LedgerEntry::query()->count(),
        'operations' => CommandOperation::query()->whereIn('command', ['primary.release', 'primary.confirm'])->count(), 'wallet' => ($this->breakdown)()];
    $thrown = null;
    try {
        $result = match ($path) {
            'release', 'expired release' => $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid()),
            'expired confirm' => $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, 'unused', 'unused', (string) Str::uuid(), PrimaryReservationFixture::terms(...)),
            'system expiry' => app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id),
            'sweep' => app(PrimaryReservations::class)->expireDue(10),
        };
        $thrown = 'NOT REFUSED: '.json_encode(is_array($result) ? $result['code'] : $result);
    } catch (Throwable $exception) {
        $thrown = $exception::class.' '.$exception->getMessage();
    }
    $after = ['versions' => PrimaryReservationVersion::query()->count(), 'entries' => LedgerEntry::query()->count(),
        'operations' => CommandOperation::query()->whereIn('command', ['primary.release', 'primary.confirm'])->count(), 'wallet' => ($this->breakdown)()];
    fwrite(STDERR, "\nAB-2 [".$path.'] '.$thrown.' | unchanged='.json_encode($before === $after).' after='.json_encode($after)
        .' failures='.json_encode(DB::table('primary_expiry_failures')->get(['primary_reservation_id', 'exception_class'])->all())."\n");
    expect($thrown)->toContain('WALLET_POSTING_CONFLICT')->and($after)->toBe($before);
})->with(['release', 'expired release', 'expired confirm', 'system expiry', 'sweep']);

it('AB-3 sweep bookkeeping when verification refuses: healthy roots still expire, the refused root is retried last and the row outlives the repair', function (): void {
    $healthy = ($this->hold)($this->other, '2');
    ($this->forgeHoldDebit)($this->root);
    $original = ($this->account)($this->walletId, 'investor_available');
    $this->travelTo($this->root->expires_at);
    $runs = [];
    foreach ([1, 2] as $run) {
        $this->travel(1)->seconds();
        try {
            $runs[] = 'returned '.app(PrimaryReservations::class)->expireDue(10);
        } catch (Throwable $exception) {
            $runs[] = 'threw '.$exception::class.' '.$exception->getMessage();
        }
        $runs[] = json_encode(DB::table('primary_expiry_failures')->get(['primary_reservation_id', 'exception_class', 'last_attempted_at'])->all());
    }
    $state = fn (PrimaryReservationRecord $root): string => PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->value('state');
    fwrite(STDERR, "\nAB-3 runs=".implode(' || ', $runs).' healthy='.$state($healthy).' forged='.$state($this->root)."\n");
    ($this->forge)(fn () => DB::table('ledger_lines')->where('entry_id', LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_hold')->value('id'))
        ->where('direction', 'debit')->update(['account_id' => $original]));
    $this->travel(1)->seconds();
    $repaired = app(PrimaryReservations::class)->expireDue(10);
    fwrite(STDERR, 'AB-3 after repair: sweep returned '.$repaired.' forged root now='.$state($this->root).' failure rows left='.DB::table('primary_expiry_failures')->count()
        .' next sweep='.app(PrimaryReservations::class)->expireDue(10)."\n");
    expect($state($healthy))->toBe('expired')->and($state($this->root))->toBe('expired')->and($repaired)->toBe(1);
});

it('AB-4 a fresh-key replay of a terminal release is not a second credit', function (): void {
    $start = ($this->breakdown)();
    $first = $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid());
    $afterFirst = ($this->breakdown)();
    $stale = $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid());
    $fresh = $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 2, (string) Str::uuid());
    $port = app(WalletPostings::class);
    $replay = $port->release($port->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal),
        new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id));
    fwrite(STDERR, "\nAB-4 start=".json_encode($start).' after first='.json_encode($afterFirst).' after replays='.json_encode(($this->breakdown)())
        ."\nAB-4 first=".json_encode($first)."\nAB-4 stale rev1 fresh key=".json_encode($stale)."\nAB-4 rev2 fresh key=".json_encode($fresh)
        ."\nAB-4 port replayed=".json_encode($replay->replayed).' same entry='.json_encode($replay->entryId === $first['data']['entry_id'])
        .' operations='.CommandOperation::query()->where('command', 'primary.release')->count().' verifier='.($this->verify)($this->root)."\n");
    expect(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1)->and(PrimaryReservationVersion::query()->count())->toBe(2)
        ->and(($this->breakdown)())->toBe($afterFirst)->and($fresh['data']['entry_id'])->toBe($first['data']['entry_id']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('AB-5 a fresh-key replay of a terminal release after its evidence is damaged now refuses instead of replaying', function (): void {
    $first = $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid());
    ($this->forge)(fn () => DB::table('ledger_lines')->where('entry_id', $first['data']['entry_id'])->where('direction', 'credit')
        ->update(['account_id' => ($this->account)($this->otherWalletId, 'investor_available')]));
    $outcome = null;
    try {
        $outcome = json_encode($this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 2, (string) Str::uuid()));
    } catch (Throwable $exception) {
        $outcome = 'threw '.$exception::class.' '.$exception->getMessage();
    }
    fwrite(STDERR, "\nAB-5 replay with a release credited to another wallet: ".$outcome."\n");
    expect($outcome)->toContain('WALLET_POSTING_CONFLICT');
});

it('AB-6 evidence matrix against the real verifier', function (): void {
    $rows = [];
    $rows['hold only'] = ($this->verify)($this->root);
    $released = ($this->hold)($this->other, '2');
    $this->checkout->release($this->other['user']->id, 1, $this->campaign->id, $released->id, 1, (string) Str::uuid());
    $rows['hold + release'] = ($this->verify)($released);
    $rows['hold + release, caller asks a smaller amount'] = ($this->verify)($released, '5000');
    $rows['hold + release, caller asks a larger amount'] = ($this->verify)($released, '15000');
    $committed = ($this->hold)($this->other, '4');
    ($this->confirm)($this->other, $committed);
    $rows['hold + commit'] = ($this->verify)($committed);
    $port = app(WalletPostings::class);
    $port->refund($port->lockForParty($committed->party_id), WalletMoney::of($committed->principal), new PostingSource('primary_reservation', $committed->id, $committed->origin_operation_id));
    $rows['hold + commit + refund'] = ($this->verify)($committed);
    $entry = fn (PrimaryReservationRecord $root, string $kind) => LedgerEntry::query()->where('source_id', $root->id)->where('kind', $kind)->value('id');
    $forgeries = [
        'release: both lines short by 1' => [$released, fn () => DB::table('ledger_lines')->where('entry_id', $entry($released, 'primary_release'))->update(['amount' => '9999'])],
        'release: credit line only short' => [$released, fn () => DB::table('ledger_lines')->where('entry_id', $entry($released, 'primary_release'))->where('direction', 'credit')->update(['amount' => '1'])],
        'release: credit to held (no cash back)' => [$released, fn () => DB::table('ledger_lines')->where('entry_id', $entry($released, 'primary_release'))->where('direction', 'credit')->update(['account_id' => ($this->account)($this->otherWalletId, 'investor_held')])],
        'release: credit to another wallet available' => [$released, fn () => DB::table('ledger_lines')->where('entry_id', $entry($released, 'primary_release'))->where('direction', 'credit')->update(['account_id' => ($this->account)($this->walletId, 'investor_available')])],
        'release: header wallet is another wallet' => [$released, fn () => DB::table('ledger_entries')->where('id', $entry($released, 'primary_release'))->update(['wallet_id' => $this->walletId])],
        'release: header origin differs' => [$released, fn () => DB::table('ledger_entries')->where('id', $entry($released, 'primary_release'))->update(['origin_operation_id' => $this->root->origin_operation_id])],
        'release: header source is another reservation' => [$released, fn () => DB::table('ledger_entries')->where('id', $entry($released, 'primary_release'))->update(['source_id' => $this->root->id])],
        'release: kind renamed primary_refund (hold + refund, no commit)' => [$released, fn () => DB::table('ledger_entries')->where('id', $entry($released, 'primary_release'))->update(['kind' => 'primary_refund'])],
        'release: an extra zero-sum line pair' => [$released, fn () => DB::table('ledger_lines')->insert([
            ['id' => strtolower((string) Str::ulid()), 'entry_id' => $entry($released, 'primary_release'), 'account_id' => ($this->account)($this->otherWalletId, 'investor_available'), 'direction' => 'debit', 'amount' => '7', 'created_at' => now()],
            ['id' => strtolower((string) Str::ulid()), 'entry_id' => $entry($released, 'primary_release'), 'account_id' => ($this->account)($this->otherWalletId, 'investor_held'), 'direction' => 'credit', 'amount' => '7', 'created_at' => now()]])],
        'release: hold entry removed' => [$released, function () use ($entry, $released): void {
            $hold = $entry($released, 'primary_hold');
            DB::table('ledger_lines')->where('entry_id', $hold)->delete();
            DB::table('ledger_entries')->where('id', $hold)->delete();
        }],
        'refund: both lines short by 1' => [$committed, fn () => DB::table('ledger_lines')->where('entry_id', $entry($committed, 'primary_refund'))->update(['amount' => '19999'])],
        'refund: commit lines short by 1' => [$committed, fn () => DB::table('ledger_lines')->where('entry_id', $entry($committed, 'primary_commit'))->update(['amount' => '19999'])],
        'refund: debit from held, not committed' => [$committed, fn () => DB::table('ledger_lines')->where('entry_id', $entry($committed, 'primary_refund'))->where('direction', 'debit')->update(['account_id' => ($this->account)($this->otherWalletId, 'investor_held')])],
        'refund: commit entry removed' => [$committed, function () use ($entry, $committed): void {
            $commit = $entry($committed, 'primary_commit');
            DB::table('ledger_lines')->where('entry_id', $commit)->delete();
            DB::table('ledger_entries')->where('id', $commit)->delete();
        }],
        'refund: kind renamed primary_release (hold + commit + release)' => [$committed, fn () => DB::table('ledger_entries')->where('id', $entry($committed, 'primary_refund'))->update(['kind' => 'primary_release'])],
    ];
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    foreach ($forgeries as $name => [$root, $change]) {
        DB::beginTransaction();
        foreach (['ledger_entries', 'ledger_lines'] as $table) {
            DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER USER');
        }
        try {
            $change();
            $rows[$name] = ($this->verify)($root);
        } catch (Throwable $exception) {
            $rows[$name] = 'FORGERY NOT POSSIBLE '.mb_substr($exception->getMessage(), 0, 90);
        }
        DB::rollBack();
    }
    fwrite(STDERR, "\n".implode("\n", array_map(fn (string $name, string $row): string => 'AB-6 ['.$name.'] '.$row, array_keys($rows), $rows))."\n");
    expect($rows['hold + release'])->toBe('ACCEPTED primary_release')->and($rows['hold + commit + refund'])->toBe('ACCEPTED primary_refund')
        ->and(array_filter($rows, fn (string $row): bool => str_starts_with($row, 'ACCEPTED')))->toHaveCount(2);
});
