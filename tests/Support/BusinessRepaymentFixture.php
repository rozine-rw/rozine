<?php

declare(strict_types=1);

namespace Tests\Support;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** A Business repayment exactly as the S4-C1 schema records it: the repayment row and its wallet debit. */
final class BusinessRepaymentFixture
{
    /**
     * A Business wallet funded through a recorded and verified synthetic deposit.
     *
     * @return array{wallet_id: string, business_id: string, party_id: string}
     */
    public static function fundedWallet(string $amount = '200000'): array
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
    public static function record(array $wallet, string $amount = '50000', ?array $lines = null, bool $repayment = true, bool $entry = true): string
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
}
