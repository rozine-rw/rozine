<?php

declare(strict_types=1);

use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Models\InvestorWallet;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/*
 * A committed deposit_credit entry must be the exact posting of its intent (#96 5874790523 item 7):
 * one clearing debit of the gross, one available credit of the net, a fee revenue credit of the fee
 * only when a fee applies, on the intent's wallet, bound by one credit receipt to an applied success
 * of the intent's amount and currency. These write the rows with raw SQL, as a caller bypassing the
 * provider callback would, and commit for real: the checks are deferred to COMMIT.
 */

/**
 * An Investor whose wallet already holds one real 50000 deposit credit (so bucket moves cannot overdraw),
 * a second recorded 50000 intent to post by hand, and a second Party's funded wallet.
 *
 * @return array{intent: WalletDepositIntent, accounts: array<string, string>, other_wallet: string, funded: string}
 */
function depositBindingScenario(string $fee): array
{
    $fixture = InvestorWalletFixture::ready($fee);
    InvestorWalletFixture::settle((string) InvestorWalletFixture::deposit($fixture)['data']['intent_id']);
    $intent = WalletDepositIntent::query()->whereKey((string) InvestorWalletFixture::deposit($fixture)['data']['intent_id'])->sole();
    $other = InvestorWalletFixture::ready($fee);
    InvestorWalletFixture::settle((string) InvestorWalletFixture::deposit($other)['data']['intent_id']);
    $otherWallet = (string) InvestorWallet::query()->where('party_id', $other['party']->id)->value('id');
    LedgerAccount::factory()->create(['wallet_id' => $intent->wallet_id, 'kind' => 'investor_held']);
    if (! LedgerAccount::query()->whereNull('wallet_id')->where('kind', 'deposit_fee_revenue')->exists()) {
        LedgerAccount::factory()->system('deposit_fee_revenue')->create();
    }
    $owned = fn (string $wallet, string $kind): string => (string) LedgerAccount::query()->where('wallet_id', $wallet)->where('kind', $kind)->value('id');

    return ['intent' => $intent, 'other_wallet' => $otherWallet, 'funded' => depositBindingAvailable($intent->wallet_id), 'accounts' => [
        'clearing' => (string) LedgerAccount::query()->whereNull('wallet_id')->where('kind', 'deposit_clearing')->value('id'),
        'fee' => (string) LedgerAccount::query()->whereNull('wallet_id')->where('kind', 'deposit_fee_revenue')->value('id'),
        'available' => $owned($intent->wallet_id, 'investor_available'),
        'held' => $owned($intent->wallet_id, 'investor_held'),
        'other available' => $owned($otherWallet, 'investor_available'),
    ]];
}

function depositBindingId(): string
{
    return strtolower((string) Str::ulid());
}

/**
 * Writes one hand-made deposit credit in a single transaction: the provider event, the entry, its
 * lines and the credit receipt, with the receipt before or after the lines, then optionally flushes
 * deferred checks mid-transaction (and continues, deferred again, to commit).
 *
 * @param  array{intent: WalletDepositIntent, accounts: array<string, string>, other_wallet: string, funded: string}  $scenario
 * @param  array<array{0: string, 1: string, 2: string}>  $lines  account key, direction, amount
 * @param  array{event?: bool, credit?: bool, wallet?: string, source?: string, event_amount?: string, event_currency?: string, flush?: string}  $options
 */
function depositBindingWrite(array $scenario, array $lines, string $order, array $options = []): void
{
    $intent = $scenario['intent'];
    DB::transaction(function () use ($scenario, $intent, $lines, $order, $options): void {
        $eventId = null;
        if ($options['event'] ?? true) {
            $eventId = depositBindingId();
            DB::insert("INSERT INTO wallet_provider_events (id, provider, provider_event_id, intent_id, content_sha256, state, amount, currency, environment,
                observed_at, disposition, evidence, created_at) VALUES (?, 'synthetic', ?, ?, ?, 'succeeded', ?, ?, 'testing', now(), 'applied', '{}', now())",
                [$eventId, 'raw-'.$eventId, $intent->id, str_repeat('a', 64), $options['event_amount'] ?? $intent->amount, $options['event_currency'] ?? 'RWF']);
        }
        $entryId = depositBindingId();
        DB::insert("INSERT INTO ledger_entries (id, wallet_id, kind, source_type, source_id, currency, payload, sha256, created_at, origin_operation_id)
            VALUES (?, ?, 'deposit_credit', 'wallet_deposit_intent', ?, 'RWF', '{}', ?, now(), ?)",
            [$entryId, $options['wallet'] ?? $intent->wallet_id, $options['source'] ?? $intent->id, str_repeat('b', 64), $intent->operation_id]);
        $credit = function () use ($intent, $entryId, $eventId, $options): void {
            if ($eventId !== null && ($options['credit'] ?? true)) {
                DB::insert("INSERT INTO wallet_deposit_credits (id, intent_id, wallet_id, ledger_entry_id, provider_event_id, operation_id, request_id, amount,
                    payload, sha256, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '{}', ?, now())",
                    [depositBindingId(), $intent->id, $intent->wallet_id, $entryId, $eventId, $intent->operation_id, $intent->request_id, $intent->credited, str_repeat('c', 64)]);
            }
        };
        if ($order === 'credit first') {
            $credit();
        }
        foreach ($lines as [$account, $direction, $amount]) {
            DB::insert('INSERT INTO ledger_lines (id, entry_id, account_id, direction, amount, created_at) VALUES (?, ?, ?, ?, ?, now())',
                [depositBindingId(), $entryId, $scenario['accounts'][$account], $direction, $amount]);
        }
        if ($order === 'ledger first') {
            $credit();
        }
        if (isset($options['flush'])) {
            DB::statement('SET CONSTRAINTS '.$options['flush'].' IMMEDIATE');
            DB::statement('SET CONSTRAINTS '.$options['flush'].' DEFERRED');
        }
    });
}

/** The wallet's available balance, straight from the committed lines. */
function depositBindingAvailable(string $walletId): string
{
    return (string) (int) LedgerLine::query()->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
        ->where('ledger_accounts.wallet_id', $walletId)->where('ledger_accounts.kind', 'investor_available')
        ->selectRaw("coalesce(sum(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0) AS balance")->value('balance');
}

$depositBindingModes = [
    'ledger then credit, at commit' => ['ledger first', null],
    'credit then lines, at commit' => ['credit first', null],
    'ledger then credit, all flushed mid-transaction' => ['ledger first', 'ALL'],
    'credit then lines, all flushed mid-transaction' => ['credit first', 'ALL'],
];

$depositBindingExact = [['clearing', 'debit', '50000'], ['available', 'credit', '49500'], ['fee', 'credit', '500']];

it('commits a hand-made deposit credit only when it is the exact bound posting of its intent', function (string $fee, array $lines, string $order, ?string $flush): void {
    $scenario = depositBindingScenario($fee);
    depositBindingWrite($scenario, $lines, $order, array_filter(['flush' => $flush]));

    expect(LedgerEntry::query()->where('source_id', $scenario['intent']->id)->count())->toBe(1)
        ->and(WalletDepositCredit::query()->where('intent_id', $scenario['intent']->id)->count())->toBe(1)
        ->and(depositBindingAvailable($scenario['intent']->wallet_id))->toBe((string) (2 * (int) $scenario['intent']->credited));
})->with([
    'with a fee' => ['500', $depositBindingExact],
    'with no fee and no fee line' => ['0', [['clearing', 'debit', '50000'], ['available', 'credit', '50000']]],
])->with($depositBindingModes);

it('refuses a hand-made deposit credit that is orphaned, mispriced or misshapen, and keeps nothing of it', function (string $fee, array $lines, array $options, string $message, string $order, ?string $flush): void {
    $scenario = depositBindingScenario($fee);
    $options = [...$options, ...array_filter(['flush' => $flush])];
    if (($options['wallet'] ?? null) === 'other') {
        $options['wallet'] = $scenario['other_wallet'];
    }
    if (($options['source'] ?? null) === 'unrecorded') {
        $options['source'] = depositBindingId();
    }

    expect(fn () => depositBindingWrite($scenario, $lines, $order, $options))->toThrow(PDOException::class, $message)
        ->and(LedgerEntry::query()->where('source_id', $options['source'] ?? $scenario['intent']->id)->count())->toBe(0)
        ->and(WalletDepositCredit::query()->where('intent_id', $scenario['intent']->id)->count())->toBe(0)
        ->and(WalletProviderEvent::query()->where('intent_id', $scenario['intent']->id)->count())->toBe(0)
        ->and(depositBindingAvailable($scenario['intent']->wallet_id))->toBe($scenario['funded'])
        ->and(depositBindingAvailable($scenario['other_wallet']))->toBe($scenario['funded']);
})->with([
    'an orphan with no event or credit' => ['500', $depositBindingExact, ['event' => false], 'must be bound to one applied success of its intent'],
    'an orphan beside an applied success but no credit' => ['500', $depositBindingExact, ['credit' => false], 'must be bound to one applied success of its intent'],
    'an entry for an unrecorded intent' => ['500', $depositBindingExact, ['event' => false, 'source' => 'unrecorded'], 'must settle a recorded deposit intent'],
    'an entry on another Party wallet' => ['500', [['clearing', 'debit', '50000'], ['other available', 'credit', '49500'], ['fee', 'credit', '500']],
        ['wallet' => 'other', 'credit' => false], 'must post to its intent wallet'],
    'a net credit to another Party account' => ['500', [['clearing', 'debit', '50000'], ['other available', 'credit', '49500'], ['fee', 'credit', '500']],
        [], 'Ledger line account must belong to the entry wallet'],
    'a mispriced gross (and net)' => ['500', [['clearing', 'debit', '50100'], ['available', 'credit', '49600'], ['fee', 'credit', '500']], [], 'must debit clearing by its intent gross'],
    'a mispriced net (and fee)' => ['500', [['clearing', 'debit', '50000'], ['available', 'credit', '49400'], ['fee', 'credit', '600']], [], 'must credit available by its intent net'],
    'a mispriced fee (and gross)' => ['500', [['clearing', 'debit', '50100'], ['available', 'credit', '49500'], ['fee', 'credit', '600']], [], 'must debit clearing by its intent gross'],
    'the net credited to the held bucket' => ['500', [['clearing', 'debit', '50000'], ['held', 'credit', '49500'], ['fee', 'credit', '500']], [],
        'must debit clearing once, credit available once and credit fee revenue only for a fee'],
    'the gross debited from fee revenue' => ['500', [['fee', 'debit', '50000'], ['available', 'credit', '49500'], ['fee', 'credit', '500']], [],
        'must debit clearing once, credit available once and credit fee revenue only for a fee'],
    'an extra balanced pair of lines' => ['500', [...$depositBindingExact, ['available', 'debit', '100'], ['held', 'credit', '100']], [],
        'must debit clearing once, credit available once and credit fee revenue only for a fee'],
    'no fee line where a fee applies' => ['500', [['clearing', 'debit', '50000'], ['available', 'credit', '50000']], [],
        'must debit clearing once, credit available once and credit fee revenue only for a fee'],
    'a fee line where no fee applies' => ['0', [['clearing', 'debit', '50000'], ['available', 'credit', '49900'], ['fee', 'credit', '100']], [],
        'must debit clearing once, credit available once and credit fee revenue only for a fee'],
    'an applied success of another amount' => ['500', $depositBindingExact, ['event_amount' => '49000'], 'must be bound to one applied success of its intent'],
    'an applied success in another currency' => ['500', $depositBindingExact, ['event_currency' => 'USD'], 'must be bound to one applied success of its intent'],
])->with($depositBindingModes);

it('checks the fee on its own when only the deposit credit checks are flushed', function (string $order): void {
    $scenario = depositBindingScenario('500');

    expect(fn () => depositBindingWrite($scenario, [['clearing', 'debit', '50000'], ['available', 'credit', '49500'], ['fee', 'credit', '600']], $order,
        ['flush' => 'ledger_entries_deposit_credit_bound, ledger_lines_entry_deposit_credit_bound']))
        ->toThrow(PDOException::class, 'must credit fee revenue by its intent fee 500 (credited 600)')
        ->and(LedgerEntry::query()->where('source_id', $scenario['intent']->id)->count())->toBe(0);
})->with(['ledger first', 'credit first']);

it('checks a line appended after the deposit credit checks were flushed early', function (): void {
    $scenario = depositBindingScenario('500');

    expect(fn () => DB::transaction(function () use ($scenario): void {
        depositBindingWrite($scenario, [['clearing', 'debit', '50000'], ['available', 'credit', '49500'], ['fee', 'credit', '500']], 'ledger first',
            ['flush' => 'ledger_entries_deposit_credit_bound, ledger_lines_entry_deposit_credit_bound']);
        $entry = LedgerEntry::query()->where('source_id', $scenario['intent']->id)->sole();
        foreach ([['clearing', 'debit'], ['available', 'credit']] as [$account, $direction]) {
            DB::insert('INSERT INTO ledger_lines (id, entry_id, account_id, direction, amount, created_at) VALUES (?, ?, ?, ?, 100, now())',
                [depositBindingId(), $entry->id, $scenario['accounts'][$account], $direction]);
        }
    }))->toThrow(PDOException::class, 'must debit clearing once, credit available once and credit fee revenue only for a fee')
        ->and(LedgerEntry::query()->where('source_id', $scenario['intent']->id)->count())->toBe(0)
        ->and(depositBindingAvailable($scenario['intent']->wallet_id))->toBe($scenario['funded']);
});

it('fails closed when every check is flushed before the credit receipt exists', function (): void {
    $scenario = depositBindingScenario('500');

    expect(fn () => DB::transaction(function () use ($scenario): void {
        depositBindingWrite($scenario, [['clearing', 'debit', '50000'], ['available', 'credit', '49500'], ['fee', 'credit', '500']], 'ledger first',
            ['credit' => false, 'flush' => 'ALL']);
    }))->toThrow(PDOException::class, 'must be bound to one applied success of its intent')
        ->and(LedgerEntry::query()->where('source_id', $scenario['intent']->id)->count())->toBe(0);
});

it('refuses a second credit for one intent however it is written', function (Closure $duplicate, string $message): void {
    $fixture = InvestorWalletFixture::ready('500');
    $intentId = (string) InvestorWalletFixture::deposit($fixture)['data']['intent_id'];
    InvestorWalletFixture::settle($intentId);
    $credit = WalletDepositCredit::query()->where('intent_id', $intentId)->sole();

    expect(fn () => DB::transaction(fn () => $duplicate($credit)))->toThrow(PDOException::class, $message)
        ->and(LedgerEntry::query()->where('source_id', $intentId)->count())->toBe(1)
        ->and(WalletDepositCredit::query()->where('intent_id', $intentId)->count())->toBe(1)
        ->and(depositBindingAvailable($credit->wallet_id))->toBe('49500');
})->with([
    'a second entry for the intent' => [fn (WalletDepositCredit $credit) => DB::insert("INSERT INTO ledger_entries (id, wallet_id, kind, source_type, source_id,
        currency, payload, sha256, created_at) VALUES (?, ?, 'deposit_credit', 'wallet_deposit_intent', ?, 'RWF', '{}', ?, now())",
        [depositBindingId(), $credit->wallet_id, $credit->intent_id, str_repeat('b', 64)]), 'ledger_entries_kind_source_type_source_id_unique'],
    'a second receipt for the same entry' => [fn (WalletDepositCredit $credit) => DB::insert('INSERT INTO wallet_deposit_credits (id, intent_id, wallet_id,
        ledger_entry_id, provider_event_id, operation_id, request_id, amount, payload, sha256, created_at) SELECT ?, intent_id, wallet_id, ledger_entry_id,
        provider_event_id, operation_id, request_id, amount, payload, sha256, now() FROM wallet_deposit_credits WHERE id = ?', [depositBindingId(), $credit->id]),
        'duplicate key value violates unique constraint "wallet_deposit_credits_'],
    'a second applied success' => [fn (WalletDepositCredit $credit) => DB::insert("INSERT INTO wallet_provider_events (id, provider, provider_event_id, intent_id,
        content_sha256, state, amount, currency, environment, observed_at, disposition, evidence, created_at) VALUES (?, 'synthetic', 'raw-second', ?, ?,
        'succeeded', 50000, 'RWF', 'testing', now(), 'applied', '{}', now())", [depositBindingId(), $credit->intent_id, str_repeat('a', 64)]),
        'wallet_provider_events_final'],
]);

it('still credits a real synthetic deposit exactly once and keeps a replay idempotent', function (string $fee, string $credited, int $lines): void {
    $fixture = InvestorWalletFixture::ready($fee);
    $intent = WalletDepositIntent::query()->whereKey((string) InvestorWalletFixture::deposit($fixture)['data']['intent_id'])->sole();
    $message = app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic', 'event_id' => 'synthetic-event-binding', 'reference' => $intent->provider_reference,
        'state' => 'succeeded', 'amount' => $intent->amount, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => '2026-09-28T10:00:00+00:00']);
    $apply = app(ApplyProviderOutcome::class);

    expect($apply->handle($message))->toMatchArray(['disposition' => 'applied', 'credited' => true, 'replayed' => false])
        ->and($apply->handle($message))->toMatchArray(['disposition' => 'applied', 'credited' => false, 'replayed' => true])
        ->and(InvestorWalletFixture::settle($intent->id))->toMatchArray(['disposition' => 'duplicate', 'credited' => false])
        ->and(LedgerEntry::query()->where('source_id', $intent->id)->count())->toBe(1)
        ->and(LedgerLine::query()->whereIn('entry_id', LedgerEntry::query()->where('source_id', $intent->id)->select('id'))->count())->toBe($lines)
        ->and(WalletDepositCredit::query()->where('intent_id', $intent->id)->count())->toBe(1)
        ->and(depositBindingAvailable($intent->wallet_id))->toBe($credited);
})->with([
    'with a fee' => ['500', '49500', 3],
    'with no fee' => ['0', '50000', 2],
]);

/** How many of the binding's three functions and two triggers exist. */
function depositBindingObjects(): int
{
    return (int) DB::scalar("SELECT (SELECT count(*) FROM pg_proc WHERE proname IN ('deposit_credit_entry_check', 'assert_deposit_credit_entry_bound',
        'assert_deposit_credit_line_entry_bound')) + (SELECT count(*) FROM pg_trigger WHERE tgname IN ('ledger_entries_deposit_credit_bound',
        'ledger_lines_entry_deposit_credit_bound'))");
}

it('installs nothing and releases every lock when an in-flight writer outlasts the install lock timeout', function (array $held): void {
    $intent = WalletDepositIntent::query()->whereKey((string) InvestorWalletFixture::deposit(InvestorWalletFixture::ready('500'))['data']['intent_id'])->sole();
    $migration = require database_path('migrations/2026_09_28_175521_bind_deposit_credits_to_their_intent_amounts.php');
    $migration->down();
    config(['database.connections.wallet_writer' => config('database.connections.pgsql')]);
    $writer = DB::connection('wallet_writer');
    try {
        $writer->beginTransaction();
        foreach ($held as $statement) {
            $writer->statement($statement, str_contains($statement, '?') ? [$intent->wallet_id] : []);
        }
        DB::statement("SET lock_timeout = '300ms'");
        expect(fn () => $migration->up())->toThrow(QueryException::class, 'lock timeout')
            ->and(depositBindingObjects())->toBe(0)
            ->and(DB::scalar('SELECT count(*) FROM pg_locks WHERE pid = pg_backend_pid() AND relation IS NOT NULL AND mode = ?', ['ExclusiveLock']))->toBe(0);
    } finally {
        $writer->rollBack();
        DB::purge('wallet_writer');
        DB::statement('RESET lock_timeout');
        if (depositBindingObjects() === 0) {
            $migration->up();
        }
    }

    expect(depositBindingObjects())->toBe(5)
        ->and(InvestorWalletFixture::settle($intent->id)['credited'])->toBeTrue()
        ->and(WalletDepositCredit::query()->where('intent_id', $intent->id)->count())->toBe(1);
})->with([
    'a provider callback holding its event and wallet locks' => [[
        "SELECT pg_advisory_xact_lock(hashtextextended('wallet-provider-event|synthetic|in-flight', 0))",
        'SELECT id FROM investor_wallets WHERE id = ? FOR UPDATE',
    ]],
    'a writer holding only the last table the install locks' => [['LOCK TABLE ledger_lines IN ROW EXCLUSIVE MODE']],
]);

it('keeps a provider callback out while the install holds its locks, then credits it under the binding', function (): void {
    $intent = WalletDepositIntent::query()->whereKey((string) InvestorWalletFixture::deposit(InvestorWalletFixture::ready('500'))['data']['intent_id'])->sole();
    $migration = require database_path('migrations/2026_09_28_175521_bind_deposit_credits_to_their_intent_amounts.php');
    $migration->down();
    config(['database.connections.wallet_callback' => config('database.connections.pgsql')]);
    $callback = DB::connection('wallet_callback');
    try {
        DB::beginTransaction();
        $migration->up();
        $callback->statement("SET lock_timeout = '300ms'");
        $callback->beginTransaction();
        $callback->select("SELECT pg_advisory_xact_lock(hashtextextended('wallet-provider-event|synthetic|waiting', 0))");
        expect(fn () => $callback->select('SELECT id FROM investor_wallets WHERE id = ? FOR UPDATE', [$intent->wallet_id]))
            ->toThrow(QueryException::class, 'lock timeout');
        $callback->rollBack();
        DB::commit();
    } finally {
        DB::purge('wallet_callback');
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if (depositBindingObjects() === 0) {
            $migration->up();
        }
    }

    expect(depositBindingObjects())->toBe(5)
        ->and(InvestorWalletFixture::settle($intent->id)['credited'])->toBeTrue()
        ->and(LedgerEntry::query()->where('source_id', $intent->id)->count())->toBe(1)
        ->and(depositBindingAvailable($intent->wallet_id))->toBe('49500');
});
