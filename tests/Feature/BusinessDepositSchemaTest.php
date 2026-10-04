<?php

declare(strict_types=1);

use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\DepositPolicy;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\Party;
use App\Models\WalletDepositIntent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Runs the change in its own savepoint and fires deferred checks, as a commit would. */
function businessDepositRejects(Closure $change, string $message): void
{
    expect(fn () => DB::transaction(function () use ($change): void {
        $change();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, $message);
}

function businessDepositId(): string
{
    return strtolower((string) Str::ulid());
}

/** @return array{wallet_id: string, business_id: string} */
function businessDepositWallet(): array
{
    $businessId = BusinessProfile::factory()->create()->id;
    $walletId = businessDepositId();
    DB::table('business_wallets')->insert(['id' => $walletId, 'business_id' => $businessId, 'currency' => 'RWF', 'created_at' => now()]);

    return ['wallet_id' => $walletId, 'business_id' => $businessId];
}

/** @param array<string, mixed> $overrides */
function businessDepositMethod(string $businessId, array $overrides = []): string
{
    $id = businessDepositId();
    DB::table('business_funding_methods')->insert([...['id' => $id, 'business_id' => $businessId, 'kind' => 'mtn', 'label' => 'Synthetic MTN',
        'masked' => '07** *** 123', 'reference' => 'synthetic-method', 'verification_source' => 'synthetic', 'verified_at' => now(),
        'revoked_at' => null, 'created_at' => now()], ...$overrides]);

    return $id;
}

/**
 * A recorded Business deposit intent with its wallet, verified method and active policy.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function businessDepositIntent(array $overrides = []): array
{
    $wallet = businessDepositWallet();
    $reference = 'synthetic-'.Str::lower(Str::random(24));
    $intent = [...['id' => businessDepositId(), 'wallet_id' => $wallet['wallet_id'], 'business_id' => $wallet['business_id'],
        'party_id' => Party::factory()->verified()->create()->id, 'operation_id' => CommandOperation::factory()->create()->id,
        'request_id' => (string) Str::uuid(), 'method_id' => businessDepositMethod($wallet['business_id']), 'policy_id' => DepositPolicy::factory()->create()->id,
        'amount' => '5000', 'fee' => '200', 'credited' => '4800', 'currency' => 'RWF', 'provider' => 'synthetic', 'provider_reference' => $reference,
        'provider_reference_sha256' => hash('sha256', $reference), 'payload' => '{}', 'sha256' => str_repeat('0', 64), 'created_at' => now()], ...$overrides];
    DB::table('business_deposit_intents')->insert($intent);

    return $intent;
}

/** @param array<string, mixed> $intent */
function businessDepositEvent(array $intent, string $state = 'succeeded', string $disposition = 'applied'): string
{
    $id = businessDepositId();
    DB::table('business_provider_events')->insert(['id' => $id, 'provider' => $intent['provider'], 'provider_event_id' => 'synthetic-event-'.Str::random(12),
        'intent_id' => $intent['id'], 'content_sha256' => hash('sha256', Str::random(16)), 'state' => $state, 'amount' => $intent['amount'],
        'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now(), 'disposition' => $disposition, 'evidence' => '{}', 'created_at' => now()]);

    return $id;
}

/**
 * Posts the credit entry for an intent with the given line amounts and records its credit binding.
 *
 * @param  array<string, mixed>  $intent
 * @param  array{gross: string, net: string, fee: string}  $lines
 */
function businessDepositCredit(array $intent, array $lines, bool $bind = true): LedgerEntry
{
    $event = businessDepositEvent($intent);
    $entry = LedgerEntry::factory()->create(['wallet_id' => $intent['wallet_id'], 'kind' => 'business_deposit_credit',
        'source_type' => 'business_deposit_intent', 'source_id' => $intent['id']]);
    $clearing = LedgerAccount::factory()->system()->create();
    $available = LedgerAccount::factory()->create(['wallet_id' => $intent['wallet_id'], 'kind' => 'business_available']);
    LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => $lines['gross']]);
    LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => $lines['net']]);
    if ($lines['fee'] !== '0') {
        $revenue = LedgerAccount::factory()->system('deposit_fee_revenue')->create();
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $revenue->id, 'direction' => 'credit', 'amount' => $lines['fee']]);
    }
    if ($bind) {
        DB::table('business_deposit_credits')->insert(['id' => businessDepositId(), 'intent_id' => $intent['id'], 'wallet_id' => $intent['wallet_id'],
            'ledger_entry_id' => $entry->id, 'provider_event_id' => $event, 'operation_id' => $intent['operation_id'], 'request_id' => $intent['request_id'],
            'amount' => $intent['credited'], 'payload' => '{}', 'sha256' => str_repeat('0', 64), 'created_at' => now()]);
    }

    return $entry;
}

it('registers a Business intent under its own owner and refuses a reference already routed to an Investor', function (): void {
    $intent = businessDepositIntent();
    $investor = WalletDepositIntent::factory()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    expect(DB::table('provider_references')->where('intent_id', $intent['id'])->value('owner'))->toBe('business');
    businessDepositRejects(fn () => businessDepositIntent(['provider' => $investor->provider,
        'provider_reference_sha256' => $investor->provider_reference_sha256]), 'provider_references_pkey');
    businessDepositRejects(fn () => businessDepositIntent(['owner' => 'investor']), 'business_deposit_intent_owner');
});

it('binds an intent to its own Business wallet, a verified method of that Business and an active policy', function (): void {
    $other = businessDepositWallet();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    businessDepositRejects(fn () => businessDepositIntent(['business_id' => $other['business_id'],
        'method_id' => businessDepositMethod($other['business_id'])]), 'business_deposit_intent_wallet');
    businessDepositRejects(fn () => businessDepositIntent(['method_id' => businessDepositMethod($other['business_id'])]), 'verified method and an active policy');
    businessDepositRejects(fn () => businessDepositIntent(['policy_id' => DepositPolicy::factory()->withdrawn()->create()->id]), 'verified method and an active policy');
    businessDepositRejects(function (): void {
        $wallet = businessDepositWallet();
        businessDepositIntent(['wallet_id' => $wallet['wallet_id'], 'business_id' => $wallet['business_id'],
            'method_id' => businessDepositMethod($wallet['business_id'], ['verified_at' => null])]);
    }, 'verified method and an active policy');
    businessDepositRejects(fn () => businessDepositIntent(['fee' => '100']), 'business_deposit_intent_amounts');
});

it('keeps Business deposit records immutable, dispatch phases ordered and method revocation one-way', function (): void {
    $intent = businessDepositIntent();
    $event = businessDepositEvent($intent, 'pending');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    businessDepositRejects(fn () => DB::table('business_deposit_intents')->where('id', $intent['id'])->update(['amount' => '6000']), 'immutable');
    businessDepositRejects(fn () => DB::table('business_provider_events')->where('id', $event)->delete(), 'immutable');
    businessDepositRejects(fn () => DB::table('business_deposit_dispatches')->insert(['id' => businessDepositId(), 'intent_id' => $intent['id'],
        'phase' => 'claimed', 'created_at' => now()]), 'Dispatch phases must follow');
    DB::table('business_funding_methods')->where('id', $intent['method_id'])->update(['revoked_at' => now()]);
    businessDepositRejects(fn () => DB::table('business_funding_methods')->where('id', $intent['method_id'])->update(['revoked_at' => null]), 'one-way revocation');
    businessDepositRejects(fn () => DB::table('business_funding_methods')->where('id', $intent['method_id'])->update(['label' => 'Other']), 'one-way revocation');
});

it('credits a Business wallet by exactly its intent net, fee and gross once bound to an applied success', function (): void {
    $intent = businessDepositIntent();
    $entry = businessDepositCredit($intent, ['gross' => '5000', 'net' => '4800', 'fee' => '200']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect($entry->fresh()?->getAttribute('wallet_owner'))->toBe('business')
        ->and(DB::table('business_deposit_credits')->where('ledger_entry_id', $entry->id)->count())->toBe(1);
});

it('refuses a Business credit with the wrong amounts, no applied binding, the wrong source or an Investor wallet', function (): void {
    businessDepositRejects(fn () => businessDepositCredit(businessDepositIntent(), ['gross' => '5000', 'net' => '4900', 'fee' => '100']),
        'must debit clearing by its gross');
    businessDepositRejects(fn () => businessDepositCredit(businessDepositIntent(), ['gross' => '5000', 'net' => '4800', 'fee' => '200'], bind: false),
        'bound to one applied success');
    businessDepositRejects(fn () => LedgerEntry::factory()->create(['wallet_id' => businessDepositIntent()['wallet_id'], 'kind' => 'business_deposit_credit']),
        'ledger_entry_source');
    businessDepositRejects(fn () => LedgerEntry::factory()->create(['kind' => 'business_deposit_credit', 'source_type' => 'business_deposit_intent']),
        'needs a wallet of its own owner');
});

it('refuses to roll back once a Business deposit record exists', function (): void {
    businessDepositIntent();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(fn () => DB::transaction(fn () => (require database_path('migrations/2026_10_03_120000_create_business_deposit_records.php'))->down()))
        ->toThrow(QueryException::class, 'Recorded Business deposits require a forward migration');
});
