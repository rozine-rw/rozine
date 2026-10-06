<?php

declare(strict_types=1);

use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Runs the change in its own savepoint and fires deferred checks, as a commit would. */
function providerReferenceRejects(Closure $change, string $message): void
{
    expect(fn () => DB::transaction(function () use ($change): void {
        $change();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, $message);
}

it('registers every new Investor intent under its own owner and reference', function (): void {
    $intent = WalletDepositIntent::factory()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect((array) DB::table('provider_references')->where('intent_id', $intent->id)->sole())->toMatchArray([
        'provider' => $intent->provider, 'reference_sha256' => $intent->provider_reference_sha256, 'owner' => 'investor', 'intent_id' => $intent->id,
    ])->and(DB::table('wallet_deposit_intents')->where('id', $intent->id)->value('owner'))->toBe('investor');
});

it('rejects a registration that routes to no intent, to another owner or to a reused reference', function (): void {
    $intent = WalletDepositIntent::factory()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $row = fn (array $overrides): array => [...['provider' => 'synthetic', 'reference_sha256' => hash('sha256', Str::random(32)), 'owner' => 'investor',
        'intent_id' => strtolower((string) Str::ulid()), 'created_at' => now()], ...$overrides];

    providerReferenceRejects(fn () => DB::table('provider_references')->insert($row([])), 'exactly one intent of its owner');
    providerReferenceRejects(fn () => DB::table('provider_references')->insert($row(['owner' => 'business'])), 'exactly one intent of its owner');
    providerReferenceRejects(fn () => DB::table('provider_references')->insert($row(['provider' => $intent->provider,
        'reference_sha256' => $intent->provider_reference_sha256, 'owner' => 'business'])), 'provider_references_pkey');
    providerReferenceRejects(fn () => DB::table('provider_references')->insert($row(['owner' => 'issuer'])), 'provider_reference_owner');
    providerReferenceRejects(fn () => DB::table('provider_references')->insert($row(['reference_sha256' => 'not-a-digest'])), 'provider_reference_digest');
    providerReferenceRejects(fn () => WalletDepositIntent::factory()->create(['owner' => 'business']), 'wallet_deposit_intent_owner');

    expect(DB::table('provider_references')->count())->toBe(DB::table('wallet_deposit_intents')->count());
});

it('keeps registrations immutable', function (): void {
    $intent = WalletDepositIntent::factory()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    providerReferenceRejects(fn () => DB::table('provider_references')->where('intent_id', $intent->id)->update(['owner' => 'business']), 'immutable');
    providerReferenceRejects(fn () => DB::table('provider_references')->where('intent_id', $intent->id)->delete(), 'immutable');
});

it('backfills retained Investor intents after auditing them, and rolls back and forward', function (): void {
    $intent = WalletDepositIntent::factory()->create();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    (require database_path('migrations/2026_10_04_100000_create_business_repayment_records.php'))->down();
    (require database_path('migrations/2026_10_03_120000_create_business_deposit_records.php'))->down();
    (require database_path('migrations/2026_10_03_110000_create_provider_reference_registry.php'))->down();
    expect(Schema::hasTable('provider_references'))->toBeFalse()->and(Schema::hasColumn('wallet_deposit_intents', 'owner'))->toBeFalse();

    (require database_path('migrations/2026_10_03_110000_create_provider_reference_registry.php'))->up();
    (require database_path('migrations/2026_10_03_120000_create_business_deposit_records.php'))->up();
    (require database_path('migrations/2026_10_04_100000_create_business_repayment_records.php'))->up();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(DB::table('provider_references')->where('intent_id', $intent->id)->value('reference_sha256'))->toBe($intent->provider_reference_sha256);
});

it('refuses the backfill over a retained reference or event binding it cannot verify, and installs nothing', function (Closure $tamper, string $message): void {
    $intent = WalletDepositIntent::factory()->create();
    WalletProviderEvent::factory()->create(['intent_id' => $intent->id, 'provider' => $intent->provider]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    (require database_path('migrations/2026_10_04_100000_create_business_repayment_records.php'))->down();
    (require database_path('migrations/2026_10_03_120000_create_business_deposit_records.php'))->down();
    (require database_path('migrations/2026_10_03_110000_create_provider_reference_registry.php'))->down();
    DB::statement('ALTER TABLE wallet_deposit_intents DISABLE TRIGGER USER');
    DB::statement('ALTER TABLE wallet_provider_events DISABLE TRIGGER USER');
    $tamper($intent);
    DB::statement('ALTER TABLE wallet_deposit_intents ENABLE TRIGGER USER');
    DB::statement('ALTER TABLE wallet_provider_events ENABLE TRIGGER USER');

    expect(fn () => (require database_path('migrations/2026_10_03_110000_create_provider_reference_registry.php'))->up())->toThrow(RuntimeException::class, $message);
    expect(Schema::hasTable('provider_references'))->toBeFalse()->and(Schema::hasColumn('wallet_deposit_intents', 'owner'))->toBeFalse();
})->with([
    'other reference' => [fn (WalletDepositIntent $intent) => DB::table('wallet_deposit_intents')->where('id', $intent->id)
        ->update(['provider_reference' => Crypt::encryptString('another-reference')]), 'does not hash to its provider reference digest'],
    'unreadable reference' => [fn (WalletDepositIntent $intent) => DB::table('wallet_deposit_intents')->where('id', $intent->id)
        ->update(['provider_reference' => 'not-encrypted']), 'unreadable provider reference'],
    'foreign event provider' => [fn (WalletDepositIntent $intent) => DB::table('wallet_provider_events')->where('intent_id', $intent->id)
        ->update(['provider' => 'other-provider']), 'names a different provider than its intent'],
]);

it('refuses to roll back once a Business registration exists', function (): void {
    DB::table('provider_references')->insert(['provider' => 'synthetic', 'reference_sha256' => hash('sha256', Str::random(32)), 'owner' => 'business',
        'intent_id' => strtolower((string) Str::ulid()), 'created_at' => now()]);

    expect(fn () => (require database_path('migrations/2026_10_03_110000_create_provider_reference_registry.php'))->down())->toThrow(QueryException::class, 'Business provider references exist');
});
