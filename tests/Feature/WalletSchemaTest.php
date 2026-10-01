<?php

declare(strict_types=1);

use App\Models\DepositPolicy;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorFundingMethod;
use App\Models\InvestorWallet;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/** Runs the change in its own savepoint and fires deferred checks, as a commit would. */
function walletSchemaRejects(Closure $change, string $message): void
{
    expect(fn () => DB::transaction(function () use ($change): void {
        $change();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, $message);
}

/**
 * A validated deposit credit entry: bound by its receipt to an applied success and posting exactly its intent's amount.
 *
 * @return array{entry: LedgerEntry, clearing: LedgerAccount, available: LedgerAccount}
 */
function walletSchemaBalancedEntry(string $amount = '5000'): array
{
    $credit = WalletDepositCredit::factory()->create(['intent_id' => WalletDepositIntent::factory()->state(['amount' => $amount, 'credited' => $amount])]);
    $entry = LedgerEntry::query()->findOrFail($credit->ledger_entry_id);
    $clearing = LedgerAccount::factory()->system()->create();
    $available = LedgerAccount::factory()->create(['wallet_id' => $entry->wallet_id]);
    LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => $amount]);
    LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => $amount]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    return ['entry' => $entry, 'clearing' => $clearing, 'available' => $available];
}

it('accepts a balanced entry and rejects unbalanced, one-sided or empty entries when constraints are checked', function (): void {
    $balanced = walletSchemaBalancedEntry();
    expect(LedgerLine::query()->where('entry_id', $balanced['entry']->id)->count())->toBe(2);

    walletSchemaRejects(function (): void {
        $entry = LedgerEntry::factory()->create();
        $clearing = LedgerAccount::factory()->system()->create(['kind' => 'deposit_fee_revenue']);
        $available = LedgerAccount::factory()->create(['wallet_id' => $entry->wallet_id]);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '5000']);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '4999']);
    }, 'must balance');
    walletSchemaRejects(function (): void {
        $entry = LedgerEntry::factory()->create();
        $available = LedgerAccount::factory()->create(['wallet_id' => $entry->wallet_id]);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '5000']);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '5000']);
    }, 'must balance');
    walletSchemaRejects(fn () => LedgerEntry::factory()->create(), 'must balance');
    expect(LedgerEntry::query()->count())->toBe(1);
});

it('rejects non-positive lines, foreign wallet accounts and non-RWF or unsupported ledger rows', function (): void {
    $balanced = walletSchemaBalancedEntry();
    walletSchemaRejects(fn () => LedgerLine::factory()->create(['entry_id' => LedgerEntry::factory(), 'amount' => '0']), 'ledger_line_amount');
    walletSchemaRejects(fn () => LedgerLine::factory()->create(['entry_id' => LedgerEntry::factory(), 'direction' => 'across']), 'ledger_line_direction');
    walletSchemaRejects(fn () => LedgerLine::factory()->create(['entry_id' => LedgerEntry::factory(), 'account_id' => $balanced['available']->id]),
        'must belong to the entry wallet');
    walletSchemaRejects(fn () => LedgerEntry::factory()->create(['currency' => 'USD']), 'ledger_entry_currency');
    walletSchemaRejects(fn () => LedgerEntry::factory()->create(['kind' => 'manual_adjustment']), 'ledger_entry_source');
    walletSchemaRejects(fn () => LedgerAccount::factory()->create(['kind' => 'deposit_clearing']), 'ledger_account_owner');
    walletSchemaRejects(fn () => LedgerAccount::factory()->system('investor_available')->create(), 'ledger_account_owner');
    walletSchemaRejects(fn () => LedgerAccount::factory()->system()->create(), 'ledger_accounts_system_kind');
    walletSchemaRejects(fn () => LedgerAccount::factory()->create(['wallet_id' => $balanced['entry']->wallet_id]), 'ledger_accounts_wallet_kind');
    walletSchemaRejects(fn () => InvestorWallet::factory()->create(['party_id' => InvestorWallet::query()->sole()->party_id]), 'investor_wallets_party_id_unique');
    walletSchemaRejects(fn () => InvestorWallet::factory()->create(['currency' => 'USD']), 'investor_wallet_currency');
});

it('seals an entry once its balance is validated, so no later line joins it in the same transaction', function (): void {
    $balanced = walletSchemaBalancedEntry();
    walletSchemaRejects(fn () => LedgerLine::factory()->create(['entry_id' => $balanced['entry']->id, 'account_id' => $balanced['clearing']->id,
        'direction' => 'debit', 'amount' => '100']), 'its balance was already validated in this transaction');
    walletSchemaRejects(function () use ($balanced): void {
        LedgerLine::factory()->create(['entry_id' => $balanced['entry']->id, 'account_id' => $balanced['clearing']->id, 'direction' => 'debit', 'amount' => '100']);
        LedgerLine::factory()->create(['entry_id' => $balanced['entry']->id, 'account_id' => $balanced['available']->id, 'direction' => 'credit', 'amount' => '100']);
    }, 'its balance was already validated in this transaction');
    walletSchemaRejects(function (): void {
        $entry = LedgerEntry::factory()->create();
        $clearing = LedgerAccount::query()->where('kind', 'deposit_clearing')->sole();
        $available = LedgerAccount::factory()->create(['wallet_id' => $entry->wallet_id]);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '5000']);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '5000']);
        DB::statement('SET CONSTRAINTS ledger_lines_entry_balanced IMMEDIATE');
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '1']);
    }, 'its balance was already validated in this transaction');
    expect(LedgerLine::query()->where('entry_id', $balanced['entry']->id)->count())->toBe(2);
});

it('posts each source once', function (): void {
    $balanced = walletSchemaBalancedEntry();
    walletSchemaRejects(fn () => LedgerEntry::factory()->create(['source_id' => $balanced['entry']->source_id]), 'ledger_entries_kind_source_type_source_id_unique');
});

it('rejects update and delete of every immutable wallet record', function (Closure $record, string $message): void {
    walletSchemaBalancedEntry();
    /** @var Model $model */
    $model = $record();
    walletSchemaRejects(fn () => $model->forceFill(['created_at' => now()->subYear()])->save(), $message);
    walletSchemaRejects(fn () => $model->delete(), $message);
})->with([
    'wallet' => [fn (): Model => InvestorWallet::query()->firstOrFail(), 'immutable'],
    'account' => [fn (): Model => LedgerAccount::query()->firstOrFail(), 'immutable'],
    'entry' => [fn (): Model => LedgerEntry::query()->firstOrFail(), 'immutable'],
    'line' => [fn (): Model => LedgerLine::query()->firstOrFail(), 'immutable'],
    'policy' => [fn (): Model => DepositPolicy::factory()->create(), 'append-only'],
    'restriction' => [fn (): Model => InvestorAccountRestriction::factory()->create(), 'append-only'],
    'intent' => [fn (): Model => WalletDepositIntent::factory()->create(), 'immutable'],
    'dispatch' => [fn (): Model => WalletDepositDispatch::factory()->create(), 'immutable'],
    'event' => [fn (): Model => WalletProviderEvent::factory()->create(), 'immutable'],
]);

it('allows only a one-way revocation of a funding method', function (): void {
    $method = InvestorFundingMethod::factory()->create();
    walletSchemaRejects(fn () => $method->forceFill(['label' => 'Changed'])->save(), 'only a one-way revocation');
    walletSchemaRejects(fn () => $method->refresh()->forceFill(['label' => 'Changed', 'revoked_at' => now()])->save(), 'only a one-way revocation');
    walletSchemaRejects(fn () => $method->delete(), 'only a one-way revocation');
    $method->refresh()->forceFill(['revoked_at' => now()])->save();
    walletSchemaRejects(fn () => $method->refresh()->forceFill(['revoked_at' => now()->addMinute()])->save(), 'only a one-way revocation');
    walletSchemaRejects(fn () => InvestorFundingMethod::factory()->unverified()->revoked()->create(), 'funding_method_revocation');
    expect($method->refresh()->revoked_at)->not->toBeNull()
        ->and($method->toArray())->not->toHaveKey('reference');
});

it('keeps deposit inputs synthetic and internally consistent', function (Closure $change, string $message): void {
    walletSchemaRejects($change, $message);
})->with([
    'live policy' => [fn () => DepositPolicy::factory()->create(['synthetic' => false]), 'deposit_policy_synthetic'],
    'withdrawn policy with terms' => [fn () => DepositPolicy::factory()->withdrawn()->create(['fee' => '0']), 'deposit_policy_terms'],
    'active policy without fee' => [fn () => DepositPolicy::factory()->create(['fee' => null]), 'deposit_policy_terms'],
    'fee without minimum above it' => [fn () => DepositPolicy::factory()->create(['fee' => '100', 'minimum' => '100']), 'deposit_policy_terms'],
    'maximum below minimum' => [fn () => DepositPolicy::factory()->create(['minimum' => '5000', 'maximum' => '4000']), 'deposit_policy_terms'],
    'live method' => [fn () => InvestorFundingMethod::factory()->create(['verification_source' => 'provider']), 'funding_method_synthetic'],
    'unknown method kind' => [fn () => InvestorFundingMethod::factory()->create(['kind' => 'card']), 'funding_method_kind'],
    'live restriction' => [fn () => InvestorAccountRestriction::factory()->create(['source' => 'staff']), 'account_restriction_synthetic'],
    'hold blocking deposits' => [fn () => InvestorAccountRestriction::factory()->create(['scope' => ['withdrawals', 'primary_commitments', 'secondary_trading', 'deposits']]), 'account_restriction_scope'],
    'hold narrower than 11.4' => [fn () => InvestorAccountRestriction::factory()->create(['scope' => ['withdrawals']]), 'account_restriction_scope'],
    'unknown scope' => [fn () => InvestorAccountRestriction::factory()->externalOrder(['everything'])->create(), 'account_restriction_scope'],
    'empty window' => [fn () => InvestorAccountRestriction::factory()->create(['expires_at' => now()->subMinutes(2)]), 'account_restriction_window'],
]);

it('records a deposit intent only for a verified unrevoked method and an active policy, with exact amounts', function (): void {
    expect(WalletDepositIntent::factory()->create()->toArray())->not->toHaveKeys(['provider_reference', 'payload']);
    walletSchemaRejects(fn () => WalletDepositIntent::factory()->create(['amount' => '5000', 'fee' => '1', 'credited' => '5000']), 'deposit_intent_amounts');
    walletSchemaRejects(fn () => WalletDepositIntent::factory()->create(['policy_id' => DepositPolicy::factory()->withdrawn()]), 'verified method and an active policy');
    $wallet = InvestorWallet::factory()->create();
    walletSchemaRejects(fn () => WalletDepositIntent::factory()->create(['wallet_id' => $wallet->id,
        'method_id' => InvestorFundingMethod::factory()->unverified()->create(['party_id' => $wallet->party_id])->id]), 'verified method and an active policy');
    walletSchemaRejects(fn () => WalletDepositIntent::factory()->create(['wallet_id' => $wallet->id,
        'method_id' => InvestorFundingMethod::factory()->create()->id]), 'verified method and an active policy');
    walletSchemaRejects(fn () => WalletDepositIntent::factory()->create(['operation_id' => strtolower((string) Str::ulid())]), 'deposit_intent_operation');
});

it('orders dispatch phases and records one dispatch outcome', function (): void {
    $intent = WalletDepositIntent::factory()->create();
    walletSchemaRejects(fn () => WalletDepositDispatch::factory()->create(['intent_id' => $intent->id, 'phase' => 'claimed']), 'must follow');
    WalletDepositDispatch::factory()->create(['intent_id' => $intent->id]);
    walletSchemaRejects(fn () => WalletDepositDispatch::factory()->create(['intent_id' => $intent->id, 'phase' => 'acknowledged']), 'must follow');
    WalletDepositDispatch::factory()->create(['intent_id' => $intent->id, 'phase' => 'claimed']);
    WalletDepositDispatch::factory()->create(['intent_id' => $intent->id, 'phase' => 'acknowledged']);
    walletSchemaRejects(fn () => WalletDepositDispatch::factory()->create(['intent_id' => $intent->id, 'phase' => 'unacknowledged']), 'wallet_deposit_dispatches_outcome');
    walletSchemaRejects(fn () => WalletDepositDispatch::factory()->create(['intent_id' => $intent->id, 'phase' => 'sent']), 'deposit_dispatch_phase');
    expect(WalletDepositDispatch::query()->where('intent_id', $intent->id)->pluck('phase')->all())->toEqualCanonicalizing(['queued', 'claimed', 'acknowledged']);
});

it('keeps one original per provider event identity and one applied final outcome per intent', function (): void {
    $event = WalletProviderEvent::factory()->create(['state' => 'succeeded']);
    walletSchemaRejects(fn () => WalletProviderEvent::factory()->create(['provider_event_id' => $event->provider_event_id, 'content_sha256' => $event->content_sha256]),
        'wallet_provider_events_content');
    walletSchemaRejects(fn () => WalletProviderEvent::factory()->create(['provider_event_id' => $event->provider_event_id, 'intent_id' => $event->intent_id]),
        'wallet_provider_events_identity');
    WalletProviderEvent::factory()->create(['provider_event_id' => $event->provider_event_id, 'intent_id' => $event->intent_id, 'disposition' => 'key_conflict']);
    walletSchemaRejects(fn () => WalletProviderEvent::factory()->create(['intent_id' => $event->intent_id, 'state' => 'failed']), 'wallet_provider_events_final');
    WalletProviderEvent::factory()->create(['intent_id' => $event->intent_id, 'state' => 'failed', 'disposition' => 'after_final']);
    walletSchemaRejects(fn () => WalletProviderEvent::factory()->create(['disposition' => 'ignored']), 'provider_event_disposition');
    walletSchemaRejects(fn () => WalletProviderEvent::factory()->create(['state' => 'redirected']), 'provider_event_state');
    walletSchemaRejects(fn () => WalletProviderEvent::factory()->create(['currency' => 'rwf']), 'provider_event_facts');
    expect(WalletProviderEvent::query()->where('intent_id', $event->intent_id)->count())->toBe(3)
        ->and($event->toArray())->not->toHaveKey('evidence');
});

it('binds a credit to its applied success, its ledger entry and its originating operation', function (): void {
    $credit = WalletDepositCredit::factory()->create();
    $intent = WalletDepositIntent::query()->findOrFail($credit->intent_id);
    expect($credit->id)->not->toBe($credit->ledger_entry_id)->and($credit->operation_id)->toBe($intent->operation_id)
        ->and($credit->toArray())->not->toHaveKey('payload');
    walletSchemaRejects(fn () => $credit->forceFill(['amount' => '1'])->save(), 'immutable');
    walletSchemaRejects(fn () => $credit->delete(), 'immutable');
    walletSchemaRejects(fn () => WalletDepositCredit::factory()->create(['amount' => '4999']), 'must bind');
    walletSchemaRejects(fn () => WalletDepositCredit::factory()->create(['operation_id' => strtolower((string) Str::ulid())]), 'must bind');
    walletSchemaRejects(function (): void {
        $intent = WalletDepositIntent::factory()->create();
        WalletDepositCredit::factory()->create(['intent_id' => $intent->id,
            'provider_event_id' => WalletProviderEvent::factory()->create(['intent_id' => $intent->id, 'state' => 'failed'])->id]);
    }, 'must bind');
    walletSchemaRejects(function (): void {
        $intent = WalletDepositIntent::factory()->create();
        WalletDepositCredit::factory()->create(['intent_id' => $intent->id,
            'ledger_entry_id' => LedgerEntry::factory()->create(['wallet_id' => $intent->wallet_id])->id]);
    }, 'must bind');
});

it('refuses to roll back wallet migrations once records exist', function (string $migration, Closure $record, string $message): void {
    $record();
    expect(fn () => DB::transaction(fn () => (require database_path('migrations/'.$migration.'.php'))->down()))
        ->toThrow(QueryException::class, $message);
})->with([
    'ledger' => ['2026_09_28_104818_create_investor_wallet_ledger_tables', fn () => InvestorWallet::factory()->create(), 'Recorded wallets require a forward migration'],
    'inputs' => ['2026_09_28_104819_create_wallet_deposit_policy_method_and_restriction_tables', fn () => DepositPolicy::factory()->create(), 'Recorded deposit inputs require a forward migration'],
    'deposits' => ['2026_09_28_104821_create_wallet_deposit_intent_and_outcome_tables', fn () => WalletDepositIntent::factory()->create(), 'Recorded deposits require a forward migration'],
    'primary postings' => ['2026_09_28_112902_add_primary_postings_to_wallet_ledger', fn () => LedgerEntry::factory()->create(['kind' => 'primary_hold',
        'source_type' => 'primary_reservation', 'origin_operation_id' => strtolower((string) Str::ulid())]), 'Recorded primary postings require a forward migration'],
]);

it('rolls wallet migrations back and forward while no records exist', function (): void {
    foreach (['2026_09_30_234802_require_complete_primary_holdings_for_issued_closings', '2026_09_30_114217_require_issue_evidence_for_primary_holdings', '2026_09_30_084737_bind_primary_holdings_to_retained_commitments', '2026_09_30_054318_create_primary_campaign_fundings', '2026_09_29_100200_add_primary_issue_to_wallet_ledger', '2026_09_29_100100_create_primary_holdings_table', '2026_09_29_100000_create_disbursement_tables', '2026_09_28_175521_bind_deposit_credits_to_their_intent_amounts', '2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements', '2026_09_28_165949_reject_unbound_primary_commitment_sources', '2026_09_28_161335_bind_primary_reservations_to_wallet_holds', '2026_09_28_140000_bind_primary_postings_to_their_source_anchor', '2026_09_28_112902_add_primary_postings_to_wallet_ledger', '2026_09_28_112500_seal_ledger_entries_once_validated', '2026_09_28_104821_create_wallet_deposit_intent_and_outcome_tables',
        '2026_09_28_104819_create_wallet_deposit_policy_method_and_restriction_tables', '2026_09_28_104818_create_investor_wallet_ledger_tables'] as $migration) {
        (require database_path('migrations/'.$migration.'.php'))->down();
    }
    expect(Schema::hasTable('investor_wallets'))->toBeFalse()->and(Schema::hasTable('wallet_deposit_intents'))->toBeFalse()
        ->and(Schema::hasTable('primary_campaign_fundings'))->toBeFalse()->and(Schema::hasTable('primary_funding_commitments'))->toBeFalse();
    foreach (['2026_09_28_104818_create_investor_wallet_ledger_tables', '2026_09_28_104819_create_wallet_deposit_policy_method_and_restriction_tables',
        '2026_09_28_104821_create_wallet_deposit_intent_and_outcome_tables', '2026_09_28_112500_seal_ledger_entries_once_validated', '2026_09_28_112902_add_primary_postings_to_wallet_ledger', '2026_09_28_140000_bind_primary_postings_to_their_source_anchor', '2026_09_28_161335_bind_primary_reservations_to_wallet_holds', '2026_09_28_165949_reject_unbound_primary_commitment_sources', '2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements', '2026_09_28_175521_bind_deposit_credits_to_their_intent_amounts',
        '2026_09_29_100000_create_disbursement_tables', '2026_09_29_100100_create_primary_holdings_table', '2026_09_29_100200_add_primary_issue_to_wallet_ledger', '2026_09_30_054318_create_primary_campaign_fundings', '2026_09_30_084737_bind_primary_holdings_to_retained_commitments', '2026_09_30_114217_require_issue_evidence_for_primary_holdings', '2026_09_30_234802_require_complete_primary_holdings_for_issued_closings'] as $migration) {
        (require database_path('migrations/'.$migration.'.php'))->up();
    }
    expect(DB::scalar("SELECT count(*) FROM pg_trigger WHERE tgname IN ('primary_issued_closing_complete', 'primary_funded_closing_complete')"))->toBe(2)
        ->and(Schema::hasTable('wallet_deposit_credits'))->toBeTrue()->and(walletSchemaDepositBindingObjects())->toBe(5)
        ->and(Schema::hasTable('primary_campaign_fundings'))->toBeTrue()->and(Schema::hasTable('primary_funding_commitments'))->toBeTrue()
        ->and(DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname IN ('primary_reservation_wallet_bound', 'ledger_primary_reservation_bound')")->total)->toBe(2)
        ->and(DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname IN ('primary_funding_refund_gate', 'primary_funding_complete', 'primary_funding_members_complete')")->total)->toBe(3);
});

/** How many of the deposit credit binding's three functions and two triggers exist. */
function walletSchemaDepositBindingObjects(): int
{
    return (int) DB::scalar("SELECT (SELECT count(*) FROM pg_proc WHERE proname IN ('deposit_credit_entry_check', 'assert_deposit_credit_entry_bound',
        'assert_deposit_credit_line_entry_bound')) + (SELECT count(*) FROM pg_trigger WHERE tgname IN ('ledger_entries_deposit_credit_bound',
        'ledger_lines_entry_deposit_credit_bound'))");
}

/** Balanced clearing-to-available lines for an entry, with no check flushed. */
function walletSchemaDepositLines(LedgerEntry $entry, string $amount): void
{
    $clearing = LedgerAccount::query()->whereNull('wallet_id')->where('kind', 'deposit_clearing')->first() ?? LedgerAccount::factory()->system()->create();
    $available = LedgerAccount::factory()->create(['wallet_id' => $entry->wallet_id]);
    LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => $amount]);
    LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => $amount]);
}

it('refuses to bind deposit credits over bad retained history and installs none of the binding', function (Closure $history, string $message): void {
    $migration = require database_path('migrations/2026_09_28_175521_bind_deposit_credits_to_their_intent_amounts.php');
    $migration->down();
    $history();

    expect(walletSchemaDepositBindingObjects())->toBe(0)
        ->and(fn () => $migration->up())->toThrow(QueryException::class, $message)
        ->and(walletSchemaDepositBindingObjects())->toBe(0);
})->with([
    'an entry for an unrecorded intent' => [fn () => walletSchemaDepositLines(LedgerEntry::factory()->create(), '5000'), 'must settle a recorded deposit intent'],
    'an entry no receipt binds' => [function (): void {
        $intent = WalletDepositIntent::factory()->create();
        walletSchemaDepositLines(LedgerEntry::factory()->create(['wallet_id' => $intent->wallet_id, 'source_id' => $intent->id]), '5000');
    }, 'must be bound to one applied success of its intent'],
    'a bound entry that moves more than its intent' => [fn () => walletSchemaDepositLines(LedgerEntry::query()->findOrFail(WalletDepositCredit::factory()->create()->ledger_entry_id), '6000'),
        'must debit clearing by its intent gross 5000 (debited 6000)'],
]);

it('binds deposit credits over good retained history while holding exclusive locks on every table it audits', function (): void {
    $intentId = (string) InvestorWalletFixture::deposit(InvestorWalletFixture::ready('500'))['data']['intent_id'];
    InvestorWalletFixture::settle($intentId);
    $migration = require database_path('migrations/2026_09_28_175521_bind_deposit_credits_to_their_intent_amounts.php');
    $migration->down();
    $migration->up();
    $locked = (int) DB::scalar("SELECT count(DISTINCT class.relname) FROM pg_locks lock JOIN pg_class class ON class.oid = lock.relation
        WHERE lock.pid = pg_backend_pid() AND lock.mode = 'ExclusiveLock' AND lock.granted AND class.relname IN ('investor_wallets', 'wallet_deposit_intents',
        'wallet_provider_events', 'ledger_entries', 'ledger_accounts', 'ledger_lines', 'wallet_deposit_credits')");

    expect(walletSchemaDepositBindingObjects())->toBe(5)
        ->and($locked)->toBe(7)
        ->and(WalletDepositCredit::query()->where('intent_id', $intentId)->count())->toBe(1);
});
