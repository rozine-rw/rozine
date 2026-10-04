<?php

declare(strict_types=1);

use App\Models\BusinessProfile;
use App\Models\InvestorWallet;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Runs the change in its own savepoint and fires deferred checks, as a commit would. */
function businessWalletSchemaRejects(Closure $change, string $message): void
{
    expect(fn () => DB::transaction(function () use ($change): void {
        $change();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, $message);
}

/** A Business wallet row inserted as its future adapter will, returning its id. */
function businessWalletSchemaWallet(?string $id = null, ?string $businessId = null): string
{
    $id ??= strtolower((string) Str::ulid());
    DB::table('business_wallets')->insert(['id' => $id, 'business_id' => $businessId ?? BusinessProfile::factory()->create()->id,
        'currency' => 'RWF', 'created_at' => now()]);

    return $id;
}

it('gives every investor and Business wallet one supertype row of its own owner', function (): void {
    $investor = InvestorWallet::factory()->create();
    $business = businessWalletSchemaWallet();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(DB::table('wallets')->whereIn('id', [$investor->id, $business])->orderBy('owner')->pluck('owner', 'id')->all())
        ->toBe([$business => 'business', $investor->id => 'investor'])
        ->and(DB::table('investor_wallets')->where('id', $investor->id)->value('owner'))->toBe('investor');
});

it('rejects a forged owner, an orphan supertype, a second subtype and a second Business wallet', function (): void {
    $investor = InvestorWallet::factory()->create();
    $profile = BusinessProfile::factory()->create();
    businessWalletSchemaWallet(businessId: $profile->id);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    businessWalletSchemaRejects(fn () => InvestorWallet::factory()->create(['owner' => 'business']), 'investor_wallet_owner');
    businessWalletSchemaRejects(fn () => DB::table('business_wallets')->insert(['id' => strtolower((string) Str::ulid()), 'owner' => 'investor',
        'business_id' => BusinessProfile::factory()->create()->id, 'currency' => 'RWF', 'created_at' => now()]), 'business_wallet_owner');
    businessWalletSchemaRejects(fn () => DB::table('wallets')->insert(['id' => strtolower((string) Str::ulid()), 'owner' => 'business',
        'created_at' => now()]), 'exactly one subtype');
    businessWalletSchemaRejects(fn () => businessWalletSchemaWallet($investor->id), 'wallets_pkey');
    businessWalletSchemaRejects(fn () => businessWalletSchemaWallet(businessId: $profile->id), 'business_wallets_business_unique');
    businessWalletSchemaRejects(fn () => DB::table('business_wallets')->insert(['id' => strtolower((string) Str::ulid()),
        'business_id' => BusinessProfile::factory()->create()->id, 'currency' => 'USD', 'created_at' => now()]), 'business_wallet_currency');

    expect(DB::table('wallets')->whereIn('id', [$investor->id, DB::table('business_wallets')->where('business_id', $profile->id)->value('id')])->count())->toBe(2)
        ->and(DB::table('wallets')->count())->toBe(DB::table('investor_wallets')->count() + DB::table('business_wallets')->count());
});

it('keeps wallet supertype and Business wallet rows immutable', function (): void {
    $business = businessWalletSchemaWallet();

    businessWalletSchemaRejects(fn () => DB::table('wallets')->where('id', $business)->update(['created_at' => now()->subDay()]), 'immutable');
    businessWalletSchemaRejects(fn () => DB::table('wallets')->where('id', $business)->delete(), 'immutable');
    businessWalletSchemaRejects(fn () => DB::table('business_wallets')->where('id', $business)->update(['currency' => 'RWF']), 'immutable');
    businessWalletSchemaRejects(fn () => DB::table('business_wallets')->where('id', $business)->delete(), 'immutable');
});

it('binds each wallet account and entry kind to its own wallet owner only', function (): void {
    $investor = InvestorWallet::factory()->create();
    $business = businessWalletSchemaWallet();
    $available = LedgerAccount::factory()->create(['wallet_id' => $business, 'kind' => 'business_available']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    expect($available->fresh()?->getAttribute('wallet_owner'))->toBe('business')
        ->and(LedgerAccount::factory()->system()->create()->fresh()?->getAttribute('wallet_owner'))->toBeNull();
    businessWalletSchemaRejects(fn () => LedgerAccount::factory()->create(['wallet_id' => $business]), 'ledger_account_wallet');
    businessWalletSchemaRejects(fn () => LedgerAccount::factory()->create(['wallet_id' => $investor->id, 'kind' => 'business_available']), 'ledger_account_wallet');
    businessWalletSchemaRejects(fn () => LedgerAccount::factory()->create(['wallet_id' => null, 'kind' => 'business_available']), 'ledger_account_owner');
    businessWalletSchemaRejects(fn () => LedgerAccount::factory()->system()->create(['wallet_id' => $business]), 'ledger_account_owner');
    businessWalletSchemaRejects(fn () => LedgerEntry::factory()->create(['wallet_id' => $business]), 'needs a wallet of its own owner');
    businessWalletSchemaRejects(fn () => LedgerEntry::factory()->create(['wallet_id' => $business, 'kind' => 'primary_hold',
        'source_type' => 'primary_reservation', 'origin_operation_id' => strtolower((string) Str::ulid())]), 'needs a wallet of its own owner');
    businessWalletSchemaRejects(fn () => DB::table('ledger_accounts')->where('id', $available->id)->update(['wallet_owner' => 'investor']), 'wallet_owner');
});

/**
 * Every kind a ledger CHECK constraint names, as PostgreSQL prints the constraint.
 *
 * @return list<string>
 */
function businessWalletSchemaKinds(string $constraint): array
{
    $definition = (string) DB::scalar('SELECT pg_get_constraintdef(oid) FROM pg_constraint WHERE conname = ?', [$constraint]);
    preg_match_all("/\\(kind\\)::text = '([a-z_]+)'/", $definition, $single);
    preg_match_all('/\\(kind\\)::text = ANY \\(\\(ARRAY\\[([^\\]]+)\\]/', $definition, $arrays);
    $kinds = $single[1];
    foreach ($arrays[1] as $list) {
        preg_match_all("/'([a-z_]+)'/", $list, $listed);
        array_push($kinds, ...$listed[1]);
    }
    sort($kinds);

    return array_values(array_unique($kinds));
}

it('derives an owner for exactly the wallet kinds every ledger constraint admits', function (): void {
    $accounts = businessWalletSchemaKinds('ledger_account_owner');
    $entries = businessWalletSchemaKinds('ledger_entry_source');
    $accountOwners = collect($accounts)->mapWithKeys(fn (string $kind): array => [$kind => DB::scalar('SELECT ledger_account_wallet_owner(?)', [$kind])])->all();
    $entryOwners = collect($entries)->mapWithKeys(fn (string $kind): array => [$kind => DB::scalar('SELECT ledger_entry_wallet_owner(?)', [$kind])])->all();

    expect($accountOwners)->toBe(['business_available' => 'business', 'deposit_clearing' => null, 'deposit_fee_revenue' => null,
        'disbursement_settlement' => null, 'investor_available' => 'investor', 'investor_committed' => 'investor', 'investor_held' => 'investor'])
        ->and($entryOwners)->toBe(['deposit_credit' => 'investor', 'primary_commit' => 'investor', 'primary_hold' => 'investor',
            'primary_issue' => 'investor', 'primary_refund' => 'investor', 'primary_release' => 'investor'])
        ->and(DB::scalar("SELECT ledger_entry_wallet_owner('business_deposit_credit')"))->toBe('business');
});

it('keeps every line native to its entry wallet owner in both directions', function (): void {
    $business = businessWalletSchemaWallet();
    $businessAccount = LedgerAccount::factory()->create(['wallet_id' => $business, 'kind' => 'business_available']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    businessWalletSchemaRejects(fn () => LedgerLine::factory()->create(['entry_id' => LedgerEntry::factory(), 'account_id' => $businessAccount->id]),
        'must belong to the entry wallet');
    businessWalletSchemaRejects(fn () => LedgerEntry::factory()->create(['wallet_id' => $business, 'kind' => 'business_deposit_credit']),
        'ledger_entry_source');
});

it('locks only a wallet of the entry owner and refuses an ownerless kind before any key check', function (): void {
    $business = businessWalletSchemaWallet();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    businessWalletSchemaRejects(fn () => LedgerEntry::factory()->create(['kind' => 'manual_adjustment']), 'needs a wallet owner');
    businessWalletSchemaRejects(fn () => LedgerEntry::factory()->create(['wallet_id' => $business, 'kind' => 'deposit_credit']), 'needs a wallet of its own owner');
    businessWalletSchemaRejects(fn () => LedgerEntry::factory()->create(['kind' => 'business_deposit_credit']), 'needs a wallet of its own owner');
});

it('maps every wallet kind to one owner and leaves every other kind ownerless', function (): void {
    $owners = DB::selectOne("SELECT ledger_account_wallet_owner('investor_available') AS investor_account, ledger_account_wallet_owner('business_available') AS business_account,
        ledger_account_wallet_owner('deposit_clearing') AS system_account, ledger_entry_wallet_owner('primary_issue') AS investor_entry,
        ledger_entry_wallet_owner('business_deposit_credit') AS business_entry, ledger_entry_wallet_owner('manual_adjustment') AS unknown_entry");

    expect((array) $owners)->toBe(['investor_account' => 'investor', 'business_account' => 'business', 'system_account' => null,
        'investor_entry' => 'investor', 'business_entry' => 'business', 'unknown_entry' => null]);
});

it('refuses to roll back once a Business wallet exists', function (): void {
    businessWalletSchemaWallet();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(fn () => DB::transaction(fn () => (require database_path('migrations/2026_10_03_100000_add_wallet_supertype_and_business_wallets.php'))->down()))
        ->toThrow(QueryException::class, 'Business wallets exist and require a forward migration');
    expect(DB::table('business_wallets')->count())->toBeGreaterThanOrEqual(1);
});
