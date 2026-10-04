<?php

declare(strict_types=1);

use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\DepositPolicy;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessAuthorityFixture;

/** Runs the change in its own savepoint and fires deferred checks, as a commit would. */
function businessRepaymentRejects(Closure $change, string $message): void
{
    expect(fn () => DB::transaction(function () use ($change): void {
        $change();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, $message);
}

/**
 * A Business wallet funded through a recorded and verified synthetic deposit.
 *
 * @return array{wallet_id: string, business_id: string, party_id: string}
 */
function businessRepaymentFundedWallet(string $amount = '200000'): array
{
    $authority = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    DepositPolicy::factory()->create(['fee' => '0', 'minimum' => '1000', 'maximum' => '1000000']);
    $method = BusinessFundingMethod::factory()->create(['business_id' => $business->id]);
    $result = app(RecordBusinessDeposit::class)->handle($authority['users'][0]->id, 1, $business->id, (string) Str::uuid(),
        ['currency' => 'RWF', 'amount' => $amount], $method->id);
    $intent = BusinessDepositIntent::query()->whereKey((string) $result['data']['intent_id'])->sole();
    app(ApplyBusinessProviderOutcome::class)->handle(app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic',
        'event_id' => 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $intent->provider_reference, 'state' => 'succeeded',
        'amount' => $amount, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]));

    return ['wallet_id' => $intent->wallet_id, 'business_id' => $business->id, 'party_id' => $authority['people'][0]->id];
}

/**
 * Records a repayment row and, unless told otherwise, its debit entry with the given line amounts.
 *
 * @param  array{wallet_id: string, business_id: string, party_id: string}  $wallet
 * @param  array{debit: string, credit: string}|null  $lines
 */
function businessRepaymentRecord(array $wallet, string $amount = '50000', ?array $lines = null, bool $repayment = true, bool $entry = true): string
{
    $id = strtolower((string) Str::ulid());
    $operation = CommandOperation::factory()->create()->id;
    if ($repayment) {
        DB::table('business_repayments')->insert(['id' => $id, 'wallet_id' => $wallet['wallet_id'], 'business_id' => $wallet['business_id'],
            'party_id' => $wallet['party_id'], 'note_id' => strtolower((string) Str::ulid()), 'operation_id' => $operation, 'request_id' => (string) Str::uuid(),
            'option' => 'due_now', 'amount' => $amount, 'servicing_revision' => 1, 'payload' => '{}', 'sha256' => str_repeat('0', 64), 'created_at' => now()]);
    }
    if ($entry) {
        $lines ??= ['debit' => $amount, 'credit' => $amount];
        $posted = LedgerEntry::factory()->create(['wallet_id' => $wallet['wallet_id'], 'kind' => 'business_repayment_debit',
            'source_type' => 'business_repayment', 'source_id' => $id, 'origin_operation_id' => $operation]);
        $available = LedgerAccount::query()->where('wallet_id', $wallet['wallet_id'])->where('kind', 'business_available')->sole();
        $clearing = LedgerAccount::query()->whereNull('wallet_id')->where('kind', 'repayment_clearing')->first()
            ?? LedgerAccount::factory()->system('repayment_clearing')->create();
        LedgerLine::factory()->create(['entry_id' => $posted->id, 'account_id' => $available->id, 'direction' => 'debit', 'amount' => $lines['debit']]);
        LedgerLine::factory()->create(['entry_id' => $posted->id, 'account_id' => $clearing->id, 'direction' => 'credit', 'amount' => $lines['credit']]);
    }

    return $id;
}

it('debits a funded Business wallet by exactly its repayment into repayment clearing', function (): void {
    $wallet = businessRepaymentFundedWallet();
    $id = businessRepaymentRecord($wallet);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    $entry = LedgerEntry::query()->where('source_id', $id)->sole();
    expect([$entry->kind, $entry->getAttribute('wallet_owner')])->toBe(['business_repayment_debit', 'business'])
        ->and(DB::scalar("SELECT sum(CASE WHEN line.direction = 'credit' THEN line.amount ELSE -line.amount END)::text FROM ledger_lines line
            JOIN ledger_accounts account ON account.id = line.account_id WHERE account.wallet_id = ? AND account.kind = 'business_available'", [$wallet['wallet_id']]))
        ->toBe('150000');
});

it('refuses a repayment without its debit, a debit without its repayment, a different amount, an overdraw and a foreign source', function (): void {
    $wallet = businessRepaymentFundedWallet('60000');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    businessRepaymentRejects(fn () => businessRepaymentRecord($wallet, entry: false), 'requires exactly one debit of its wallet');
    businessRepaymentRejects(fn () => businessRepaymentRecord($wallet, repayment: false), 'must settle a recorded repayment');
    businessRepaymentRejects(fn () => businessRepaymentRecord($wallet, '50000', ['debit' => '40000', 'credit' => '40000']), 'must move exactly its repayment amount');
    businessRepaymentRejects(fn () => businessRepaymentRecord($wallet, '70000'), 'would overdraw');
    businessRepaymentRejects(fn () => LedgerEntry::factory()->create(['wallet_id' => $wallet['wallet_id'], 'kind' => 'business_repayment_debit',
        'origin_operation_id' => CommandOperation::factory()->create()->id]), 'ledger_entry_source');
    businessRepaymentRejects(fn () => LedgerEntry::factory()->create(['kind' => 'business_repayment_debit', 'source_type' => 'business_repayment',
        'origin_operation_id' => CommandOperation::factory()->create()->id]), 'needs a wallet of its own owner');
});

it('keeps repayments immutable and refuses an unknown option or a bad note', function (): void {
    $wallet = businessRepaymentFundedWallet();
    $id = businessRepaymentRecord($wallet);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    businessRepaymentRejects(fn () => DB::table('business_repayments')->where('id', $id)->update(['amount' => '1']), 'immutable');
    businessRepaymentRejects(fn () => DB::table('business_repayments')->where('id', $id)->delete(), 'immutable');
    businessRepaymentRejects(fn () => DB::table('business_repayments')->insert(['id' => strtolower((string) Str::ulid()), 'wallet_id' => $wallet['wallet_id'],
        'business_id' => $wallet['business_id'], 'party_id' => $wallet['party_id'], 'note_id' => 'NOT-A-NOTE', 'operation_id' => strtolower((string) Str::ulid()),
        'request_id' => (string) Str::uuid(), 'option' => 'pay_ahead', 'amount' => '1', 'servicing_revision' => 1, 'payload' => '{}',
        'sha256' => str_repeat('0', 64), 'created_at' => now()]), 'business_repayment_');
});

it('refuses to roll back once a repayment exists', function (): void {
    businessRepaymentRecord(businessRepaymentFundedWallet());
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(fn () => DB::transaction(fn () => (require database_path('migrations/2026_10_04_100000_create_business_repayment_records.php'))->down()))
        ->toThrow(QueryException::class, 'Recorded Business repayments require a forward migration');
});
