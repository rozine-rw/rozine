<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\PayBusinessRepayment;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use App\Models\BusinessCampaign;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessMandate;
use App\Models\PrimaryHolding;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryHoldingFixture;

/*
 * Staging's simulated servicing (Hussain's review of #615 and #633): a Business repaying its note and a
 * resale of that note's units settling at the same moment, each in its own process and connection.
 * Collection and settlement take the same note lock, so the holders paid are exactly the holders at
 * collection, whichever commits first, and every balance adds up.
 */

/**
 * Runs each contender in its own forked process and connection; returns their exit codes in order.
 *
 * @return list<int>
 */
function servicingContenders(Closure ...$operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork a servicing contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                $operation();
                exit(0);
            } catch (Throwable $exception) {
                fwrite(STDERR, $exception::class.': '.$exception->getMessage().PHP_EOL);
                exit(1);
            }
        }
        $pids[] = $pid;
    }
    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $exit = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : false;
        $statuses[] = is_int($exit) ? $exit : throw new RuntimeException('A servicing contender did not exit normally.');
    }

    return $statuses;
}

function servicingAvailable(string $partyId): int
{
    return (int) DB::scalar("SELECT coalesce(sum(CASE WHEN line.direction = 'credit' THEN line.amount ELSE -line.amount END), 0) FROM ledger_lines line
        JOIN ledger_accounts account ON account.id = line.account_id JOIN investor_wallets wallet ON wallet.id = account.wallet_id
        WHERE account.kind = 'investor_available' AND wallet.party_id = ?", [$partyId]);
}

it('pays exactly the holders at collection when a resale of the note settles at the same moment', function (): void {
    ['campaign' => $publication] = PrimaryHoldingFixture::committed(units: ['1080', '1080'], requoted: [0]);
    $campaignId = $publication->id;
    $businessId = (string) BusinessCampaign::query()->whereKey($campaignId)->value('business_id');
    $buyer = InvestorWalletFixture::funded('1000000');
    $party = (string) BusinessMandate::query()->where('business_id', $businessId)->orderByDesc('version')->firstOrFail()->terms['people'][0]['party_id'];
    $payer = User::query()->where('party_id', $party)->sole();
    $method = BusinessFundingMethod::factory()->create(['business_id' => $businessId, 'kind' => 'bank', 'label' => 'Bank of Kigali',
        'masked' => 'Account ····6789', 'reference' => 'bk:000123456789', 'verified_at' => now('UTC')->subMinute()]);
    $deposit = app(RecordBusinessDeposit::class)->handle($payer->id, 1, $businessId, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '5000000'], $method->id);
    $intent = BusinessDepositIntent::query()->whereKey((string) $deposit['data']['intent_id'])->sole();
    app(ApplyBusinessProviderOutcome::class)->handle(app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic',
        'event_id' => 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $intent->provider_reference, 'state' => 'succeeded',
        'amount' => '5000000', 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]));

    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);
    config(['app.uat_money_simulation' => true, 'app.uat_money_simulation_businesses' => [$businessId]]);
    Artisan::call('disbursements:reconcile');
    Artisan::call('disbursements:reconcile');
    $schedule = DB::transaction(fn () => app(RetainedNoteSchedules::class)->find($campaignId)) ?? throw new RuntimeException('The note was not issued.');
    $first = $schedule->instalments[0];

    $seller = PrimaryHolding::query()->where('business_campaign_id', $campaignId)->orderBy('id')->firstOrFail();
    $sellerUser = User::query()->where('party_id', $seller->party_id)->sole();
    $market = app(EloquentSecondaryMarket::class);
    $listing = $market->list($sellerUser->id, 1, $seller->id, '4', '1000', (string) Str::uuid())['data']['listing'];
    $reservation = $market->reserve($buyer['user']->id, 1, $listing['listing_id'], '4', (string) Str::uuid())['data']['reservation'];
    $parties = PrimaryHolding::query()->where('business_campaign_id', $campaignId)->pluck('party_id')->push($buyer['party']->id)->unique()->values();
    $before = $parties->mapWithKeys(fn (string $partyId): array => [$partyId => servicingAvailable($partyId)])->all();
    $buyerHeld = (int) DB::scalar("SELECT coalesce(sum(CASE WHEN line.direction = 'credit' THEN line.amount ELSE -line.amount END), 0) FROM ledger_lines line
        JOIN ledger_accounts account ON account.id = line.account_id JOIN investor_wallets wallet ON wallet.id = account.wallet_id
        WHERE account.kind = 'investor_held' AND wallet.party_id = ?", [$buyer['party']->id]);

    expect(servicingContenders(
        fn () => app(EloquentSecondaryMarket::class)->settle($buyer['user']->id, 1, $listing['listing_id'], $reservation['reservation_id'], (string) Str::uuid())['code'] === 'SECONDARY_SETTLED'
            ?: throw new RuntimeException('Resale did not settle.'),
        fn () => app(PayBusinessRepayment::class)->handle($payer->id, 1, $businessId, (string) Str::uuid(), strtolower($campaignId), 'next_instalment', 1,
            ['currency' => 'RWF', 'amount' => $first->gross])['code'] === 'REPAYMENT_RECEIVED' ?: throw new RuntimeException('Repayment was not received.'),
    ))->toBe([0, 0]);

    $transfer = DB::table('secondary_transfers')->sole();
    $payouts = DB::table('servicing_payouts')->get();
    $bought = $payouts->firstWhere('lot_type', 'secondary_transfer');
    $sold = $payouts->firstWhere('lot_id', $seller->id);
    // Whichever committed first, every unit is paid once: the 4 units go to the buyer only if the resale settled before collection.
    expect($payouts->sum(fn (object $row): int => (int) $row->units))->toBe(2160)
        ->and((string) $payouts->sum(fn (object $row): int => (int) $row->principal))->toBe($first->principal)
        ->and((string) $payouts->sum(fn (object $row): int => (int) $row->contractual_return))->toBe($first->contractualReturn)
        ->and((int) $sold->units)->toBe($bought === null ? 1080 : 1076);
    if ($bought !== null) {
        expect($bought->lot_id)->toBe($transfer->id)->and($bought->party_id)->toBe($buyer['party']->id)->and((int) $bought->units)->toBe(4);
    }

    // Final holder balances: each moved by exactly its payouts plus its side of the resale.
    foreach ($parties as $partyId) {
        $paid = (int) $payouts->where('party_id', $partyId)->sum(fn (object $row): int => (int) $row->net);
        $resale = match ($partyId) {
            $seller->party_id => (int) $transfer->seller_receives,
            $buyer['party']->id => (int) $buyerHeld - (int) $transfer->gross,
            default => 0,
        };
        expect(servicingAvailable($partyId))->toBe($before[$partyId] + $paid + $resale);
    }
});
