<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Business\Contracts\CampaignTitles;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Business\Contracts\ServicingPayouts;
use App\Application\Business\Contracts\ServicingRecord;
use App\Application\Business\Contracts\ServicingStatements;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\Contracts\SimulatedRaiseFunding;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaignRef;
use App\Application\Disbursement\SyntheticDisbursementGuard;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\UatMoneySimulation;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Secondary\Contracts\NoteUnitHolders;
use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Application\Secondary\Contracts\SecondaryNotes;
use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\WalletStore;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Infrastructure\Business\SimulatedCommittedRaiseFunding;
use App\Infrastructure\Disbursement\SimulatedFundedCampaigns;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Infrastructure\Disbursement\SyntheticPayoutProvider;
use App\Infrastructure\Disbursement\UnavailableFundedCampaigns;
use App\Infrastructure\Disbursement\UnavailablePayoutProvider;
use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use App\Infrastructure\Secondary\SimulatedSecondaryMarket;
use App\Infrastructure\Secondary\UnavailableSecondaryMarket;
use App\Infrastructure\Wallet\SimulatedNoteServicing;
use App\Infrastructure\Wallet\SimulatedServicingPayouts;
use App\Models\BusinessCampaign;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessMandate;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use App\Models\SecondaryTransfer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\StaffFixture;

/*
 * Staging's simulated money after funding (Erastus, 14 Nov 2026): on UAT with live money off, a raise
 * that reaches 100% issues its notes and pays the Business's own linked account, and resale opens.
 * Nothing of it reaches demo or production.
 */

/** UAT with the simulation on, naming these synthetic test Businesses. */
function asUatProfile(string ...$businesses): void
{
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);
    config(['app.uat_money_simulation' => true, 'app.uat_money_simulation_businesses' => $businesses]);
}

it('stays off on UAT until staging opts in', function (): void {
    expect(config('app.uat_money_simulation'))->toBeFalse();
    app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => 'uat']));

    expect(app(UatMoneySimulation::class)->active())->toBeFalse();
});

it('is active only on UAT with live money off and its switch on', function (string $profile, bool $liveMoney, bool $switch, bool $active): void {
    app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => $profile]));
    config(['isolation.live_money_enabled' => $liveMoney, 'app.uat_money_simulation' => $switch]);

    expect(app(UatMoneySimulation::class)->active())->toBe($active)
        ->and(app(SyntheticDisbursementGuard::class)->simulating())->toBe($active);
})->with([['uat', false, true, true], ['uat', true, true, false], ['uat', false, false, false], ['testing', false, true, false],
    ['local', false, true, false], ['demo', false, true, false], ['production', false, true, false]]);

it('binds the funding source, payout provider and resale to the simulation on UAT only', function (string $profile, bool $switch, string $funding, string $provider, string $market): void {
    app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => $profile]));
    config(['app.uat_money_simulation' => $switch, 'app.secondary_trading' => false]);

    expect(get_class(app()->make(FundedCampaigns::class)))->toBe($funding)
        ->and(get_class(app()->make(PayoutProvider::class)))->toBe($provider)
        ->and(get_class(app()->make(SecondaryMarket::class)))->toBe($market);
})->with([
    ['uat', true, SimulatedFundedCampaigns::class, SyntheticPayoutProvider::class, SimulatedSecondaryMarket::class],
    ['uat', false, UnavailableFundedCampaigns::class, UnavailablePayoutProvider::class, UnavailableSecondaryMarket::class],
    ['testing', true, SyntheticDisbursementSources::class, SyntheticPayoutProvider::class, UnavailableSecondaryMarket::class],
    ['demo', true, UnavailableFundedCampaigns::class, UnavailablePayoutProvider::class, UnavailableSecondaryMarket::class],
    ['production', true, UnavailableFundedCampaigns::class, UnavailablePayoutProvider::class, UnavailableSecondaryMarket::class],
]);

it('refuses the simulated funding source and the provider scripts outside the simulation', function (): void {
    config(['app.uat_money_simulation' => true]);
    expect(fn () => (new SimulatedFundedCampaigns(app(SyntheticDisbursementSources::class), app(UatMoneySimulation::class)))->funded(null, 5))->toThrow(LogicException::class, 'UAT_SIMULATION_ONLY');

    asUatProfile();
    expect(fn () => app(SyntheticPayoutProvider::class)->scriptSend('nack'))->toThrow(LogicException::class, 'DISBURSEMENT_SYNTHETIC_ONLY');

    config(['app.uat_money_simulation' => false]);
    expect(fn () => app(SyntheticDisbursementGuard::class)->assertProviderAllowed())
        ->toThrow(LogicException::class, 'the synthetic payout provider runs only on local, testing or the UAT money simulation');
});

it('hands a failed closing to the retained funding source unchanged', function (): void {
    $funded = app(SyntheticDisbursementSources::class)->fund();
    asUatProfile();
    $retained = $this->createMock(FundedCampaigns::class);
    $closing = new FailedClosing('closing', 'disbursement', 'auto_recheck', ['dscr']);
    $retained->expects($this->once())->method('failClose')->with($funded, $closing);

    (new SimulatedFundedCampaigns($retained, app(UatMoneySimulation::class)))->failClose($funded, $closing);
});

it('pays a fully funded raise on UAT to the Business\'s linked account, issues its notes and keeps the scheduler idempotent', function (): void {
    ['campaign' => $publication] = PrimaryHoldingFixture::committed(units: ['1080', '1080'], requoted: [0]);
    $businessId = (string) BusinessCampaign::query()->whereKey($publication->id)->value('business_id');
    asUatProfile(strtoupper($businessId));

    // No linked account yet: the disbursement opens and waits for a destination; nothing is sent.
    expect(Artisan::call('disbursements:reconcile'))->toBe(0);
    $disbursement = Disbursement::query()->where('business_campaign_id', $publication->id)->sole();
    expect(DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->exists())->toBeFalse();

    BusinessFundingMethod::factory()->create(['business_id' => $businessId, 'kind' => 'bank', 'label' => 'Bank of Kigali',
        'masked' => 'Account ····6789', 'reference' => 'bk:000123456789', 'verified_at' => now('UTC')->subMinute()]);
    expect(Artisan::call('disbursements:reconcile'))->toBe(0);
    $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole();
    expect($intent->environment)->toBe('uat')
        ->and($intent->destination_sha256)->toBe(app(PayoutDestinations::class)->verified($businessId, 'uat')?->digest());

    // The next run reads the provider's answer and issues the notes.
    expect(Artisan::call('disbursements:reconcile'))->toBe(0);
    expect(DisbursementClosing::query()->where('intent_id', $intent->id)->sole()->kind)->toBe('issued')
        ->and(DB::table('primary_holdings')->where('business_campaign_id', $publication->id)->count())->toBe(2)
        ->and(app(RetainedNoteSchedules::class)->find($publication->id)?->campaignId)->toBe($publication->id);

    expect(Artisan::call('disbursements:reconcile'))->toBe(0)
        ->and(DisbursementClosing::query()->count())->toBe(1)->and(DB::table('primary_holdings')->count())->toBe(2);
});

/**
 * A raise funded, its Business wallet funded and its notes issued on UAT under the simulation.
 *
 * @return array{campaign: string, business: string, payer: User}
 */
function simulatedIssuedNote(string $deposit = '5000000'): array
{
    ['campaign' => $publication] = PrimaryHoldingFixture::committed(units: ['1080', '1080'], requoted: [0]);
    $businessId = (string) BusinessCampaign::query()->whereKey($publication->id)->value('business_id');
    $party = (string) BusinessMandate::query()->where('business_id', $businessId)->orderByDesc('version')->firstOrFail()->terms['people'][0]['party_id'];
    $payer = User::query()->where('party_id', $party)->sole();
    $method = BusinessFundingMethod::factory()->create(['business_id' => $businessId, 'kind' => 'bank', 'label' => 'Bank of Kigali',
        'masked' => 'Account ····6789', 'reference' => 'bk:000123456789', 'verified_at' => now('UTC')->subMinute()]);
    $result = app(RecordBusinessDeposit::class)->handle($payer->id, 1, $businessId, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => $deposit], $method->id);
    $intent = BusinessDepositIntent::query()->whereKey((string) $result['data']['intent_id'])->sole();
    app(ApplyBusinessProviderOutcome::class)->handle(app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic',
        'event_id' => 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $intent->provider_reference, 'state' => 'succeeded',
        'amount' => $deposit, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]));
    asUatProfile($businessId);
    Artisan::call('disbursements:reconcile');
    Artisan::call('disbursements:reconcile');
    expect(app(RetainedNoteSchedules::class)->find($publication->id))->not->toBeNull();

    return ['campaign' => $publication->id, 'business' => $businessId, 'payer' => $payer];
}

function investorAvailable(string $partyId): string
{
    return (string) DB::scalar("SELECT coalesce(sum(CASE WHEN line.direction = 'credit' THEN line.amount ELSE -line.amount END), 0)::text FROM ledger_lines line
        JOIN ledger_accounts account ON account.id = line.account_id JOIN investor_wallets wallet ON wallet.id = account.wallet_id
        WHERE account.kind = 'investor_available' AND wallet.party_id = ?", [$partyId]);
}

it('lets the Business repay its note on UAT and pays each holder its share less the earnings fee', function (): void {
    $note = simulatedIssuedNote();
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $first = $schedule->instalments[0];
    $holders = collect($schedule->holdings)->mapWithKeys(fn ($holding) => [$holding->partyId => investorAvailable($holding->partyId)])->all();

    $this->actingAs($note['payer'])->get(route('business.repayments.show', $note['business']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('allowed_actions', ['repayment.pay'])
            ->where('note.id', strtolower($note['campaign']))->where('servicing.state', 'current')->where('servicing.revision', 1)
            ->where('servicing.progress.payments_made', 0)->where('servicing.progress.payments_total', count($schedule->instalments))
            ->where('pay.options.0.key', 'next_instalment')->where('pay.options.0.instalment_indexes', [1])
            ->where('pay.options.0.amounts.total.amount', $first->gross)->where('pay.funding.sufficient.next_instalment', true)
            ->where('schedule.0.status', 'upcoming')->where('actions.pay.method', 'post')->where('recent', []));

    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $first->gross]])
        ->assertOk()->assertJsonPath('code', 'REPAYMENT_RECEIVED');

    $payouts = DB::table('servicing_payouts')->orderBy('id')->get();
    expect($payouts)->toHaveCount(2)
        ->and((string) $payouts->sum(fn (object $row): int => (int) $row->principal))->toBe($first->principal)
        ->and((string) $payouts->sum(fn (object $row): int => (int) $row->contractual_return))->toBe($first->contractualReturn);
    foreach ($payouts as $payout) {
        expect((int) $payout->fee)->toBe((int) round((int) $payout->contractual_return * $payout->rate_bps / 10000))
            ->and((int) $payout->net)->toBe((int) $payout->principal + (int) $payout->contractual_return - (int) $payout->fee)
            ->and(investorAvailable($payout->party_id))->toBe((string) ((int) $holders[$payout->party_id] + (int) $payout->net));
    }

    $this->actingAs($note['payer'])->get(route('business.repayments.show', $note['business']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('servicing.revision', 2)->where('servicing.progress.payments_made', 1)
            ->where('schedule.0.status', 'paid')->where('schedule.0.paid.total.amount', $first->gross)->where('pay.options.0.instalment_indexes', [2])
            ->where('recent.0.allocation', 'allocated')->where('recent.0.investors_paid', 2)->where('recent.0.amount.amount', $first->gross));

    // Staff see the fee on earnings in Finance and each payout typed as an investor payout (QA #646).
    $treasury = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($treasury, ['treasury'], 'Finance review.');
    $fees = (string) $payouts->sum(fn (object $row): int => (int) $row->fee);
    $this->actingAs($treasury)->get('/admin/finance')
        ->assertInertia(fn (Assert $page): Assert => $page->where('streams.3.key', 'earnings_fee')->where('streams.3.collected.amount', $fees)
            ->where('figures.investor_fees.amount', $fees)->where('figures.revenue.amount', fn (string $amount): bool => (int) $amount >= (int) $fees)->etc());
    $entry = DB::table('ledger_entries')->where('kind', 'servicing_payout')->value('id');
    // A payout is found by its Business's name and by the word staff read in its Type column.
    $business = (string) DB::table('business_profiles')->whereIn('id', DB::table('business_repayments')->select('business_id'))->value('profile->name');
    foreach ([$business, 'investor payout'] as $search) {
        $this->actingAs($treasury)->get(route('staff.ledger.index', ['q' => $search]))
            ->assertInertia(fn (Assert $page): Assert => $page->where('entries', fn (Collection $rows): bool => $rows->contains(fn (array $row): bool => $row['id'] === $entry
                && ($search === 'investor payout' || $row['reference'] === $business)))->etc());
    }
    $this->actingAs($treasury)->get(route('staff.ledger.index', ['entry' => $entry]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('entry.kind', 'distribution')->etc());
    // The disbursement's outbound leg is found by the bank it paid, as the Disbursements drawer names it (QA #663).
    $issued = DB::table('ledger_entries')->where('kind', 'disbursement_platform_fee')->value('id');
    foreach (['Bank of Kigali', 'bank of kigali', 'Account ····6789'] as $search) {
        $this->actingAs($treasury)->get(route('staff.ledger.index', ['q' => $search]))
            ->assertInertia(fn (Assert $page): Assert => $page->where('entries', fn (Collection $rows): bool => $rows->contains('id', $issued))->etc());
    }

    // Paid on once, and a stale quote is refused.
    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $first->gross]])
        ->assertStatus(409);
    $repaymentId = (string) DB::table('servicing_payouts')->value('repayment_id');
    expect(app(SimulatedServicingPayouts::class)->payOut($repaymentId))->toBe(0)
        ->and(fn () => DB::transaction(fn (): int => app(SimulatedNoteServicing::class)->applyRepayment(strtolower($note['campaign']), $repaymentId,
            'next_instalment', WalletMoney::of($first->gross), 2)))->toThrow(CommandRejection::class, 'VERSION_CONFLICT');
});

it('shows a holder the repayment paid on in its wallet, statement, holding page and portfolio', function (): void {
    $note = simulatedIssuedNote();
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $first = $schedule->instalments[0];
    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $first->gross]])
        ->assertOk();
    $holding = $schedule->holdings[0];
    $payout = DB::table('servicing_payouts')->where('lot_id', $holding->holdingId)->sole();
    $entry = DB::table('ledger_entries')->where('kind', 'servicing_payout')->where('source_id', $payout->id)->sole();
    $investor = User::query()->where('party_id', $holding->partyId)->sole();
    $title = app(CampaignTitles::class)->title($note['campaign']);

    $this->actingAs($investor)->get(route('investor.wallet'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('history.items.0.id', $entry->id)->where('history.items.0.kind', 'repayment')
            ->where('history.items.0.direction', 'in')->where('history.items.0.amount.amount', (string) $payout->net)
            ->where('history.items.0.counterparty', $title)->etc());
    $this->actingAs($investor)->get(route('investor.wallet', ['receipt' => $entry->id]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('receipt.kind', 'repayment')->where('receipt.receipt.code', 'REPAYMENT_PAID')
            ->where('receipt.receipt.amount.amount', (string) $payout->net)->where('receipt.receipt.reference', 'RZW-'.strtoupper(substr($entry->id, -10)))->etc());
    $statement = app(WalletStore::class)->statement($investor->id, null, null, null);
    expect(collect($statement['rows'])->firstWhere('kind', 'repayment'))->toMatchArray(['movement' => 'external', 'direction' => 'in',
        'counterparty' => $title, 'amount' => ['currency' => 'RWF', 'amount' => (string) $payout->net]])
        ->and(app(WalletStore::class)->statementMonths($investor->id, null))->toContain(now()->setTimezone(config('app.timezone'))->format('Y-m-01'));

    // Reading what was paid needs no simulation; off UAT, published audit seals verify against the test database.
    app()->forgetInstance(EnvironmentIsolation::class);
    config(['app.uat_money_simulation' => false]);
    $return = (int) $payout->contractual_return;
    $this->actingAs($investor)->get(route('investor.holdings.show', $holding->holdingId))
        ->assertInertia(fn (Assert $page): Assert => $page->where('holding.received.amount', (string) $payout->net)
            ->where('holding.payments_made', 1)->where('holding.payments_total', count($schedule->instalments))
            ->where('holding.gain.amount', (string) $return)->where('holding.on_time', ['made' => 1, 'on_time' => 1, 'late' => 0])
            ->where('holding.next_payment.due_on', fn (string $due): bool => str_starts_with($due, $schedule->instalments[1]->dueDate))
            ->where('holding.months_left', count($schedule->instalments) - 1)
            ->where('holding.schedule.0.paid_on', fn (string $on): bool => $on !== '')->where('holding.schedule.0.net.amount', (string) $payout->net)
            ->where('holding.schedule.0.principal.amount', (string) $payout->principal)->where('holding.schedule.1.paid_on', null)->where('holding.schedule.1.net', null)->etc());
    $this->actingAs($investor)->get(route('investor.portfolio'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('holdings.0.payments_made', 1)->where('holdings.0.gain.amount', (string) $return)
            ->where('totals.gain.amount', (string) $return)->where('totals.this_month.amount', (string) $return)->etc());
});

it('lists, reserves, buys and withdraws resale units from the Market page on UAT', function (): void {
    $buyer = InvestorWalletFixture::funded('2000000')['user'];
    $note = simulatedIssuedNote();
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $holding = $schedule->holdings[0];
    $seller = User::query()->where('party_id', $holding->partyId)->sole();
    $command = fn (array $input): array => ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, ...$input];

    $cap = null;
    $this->actingAs($seller)->get(route('investor.market'))
        ->assertInertia(function (Assert $page) use ($holding, &$cap): Assert {
            $cap = $page->toArray()['props']['market']['sellable'][0]['cap']['amount'];

            return $page->component('investor/market')->where('contract_version', 'investor-market-v1')->where('market.open', true)
                ->where('market.sellable.0.lot_id', $holding->holdingId)->where('market.sellable.0.free_units', (string) $holding->units)
                ->where('market.sellable.0.tradable', true)->where('market.listings', [])->where('actions.list.method', 'post')->etc();
        });
    $price = (string) intdiv((int) $cap, 2);
    $listed = $this->actingAs($seller)->postJson(route('investor.market_list'), $command(['lot_id' => $holding->holdingId, 'units' => '10', 'price' => $price]))
        ->assertOk()->assertJsonPath('code', 'SECONDARY_LISTED')->json('data.listing.listing_id');

    $this->actingAs($seller)->get(route('investor.market'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('market.listings.0.mine', true)->where('market.listings.0.actions.reserve', null)
            ->where('market.listings.0.actions.withdraw.method', 'post')->where('market.sellable.0.free_units', (string) ($holding->units - 10))->etc());
    $this->actingAs($buyer)->get(route('investor.market'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('market.listings.0.listing_id', $listed)->where('market.listings.0.deal_name', app(CampaignTitles::class)->title($note['campaign']))
            ->where('market.listings.0.remaining_units', '10')->where('market.listings.0.price.amount', $price)->where('market.listings.0.mine', false)
            ->where('market.listings.0.actions.reserve.url', route('investor.market_reserve', $listed, false))->where('market.listings.0.actions.withdraw', null)
            ->where('market.sellable', [])->where('actions.list', null)->etc());

    $reservation = $this->actingAs($buyer)->postJson(route('investor.market_reserve', $listed), $command(['units' => '10']))
        ->assertOk()->assertJsonPath('code', 'SECONDARY_RESERVED')->json('data.reservation.reservation_id');
    $this->actingAs($buyer)->get(route('investor.market'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('market.listings.0.reservation.reservation_id', $reservation)
            ->where('market.listings.0.actions.settle.method', 'post')->where('market.listings.0.actions.reserve', null)->etc());
    $this->actingAs($seller)->get(route('investor.market'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('market.listings.0.reserved', true)->where('market.listings.0.mine', true)
            ->where('market.listings.0.reserved_units', '10')->where('market.listings.0.actions.settle', null)->etc());
    $this->actingAs($buyer)->postJson(route('investor.market_settle', $listed), $command(['reservation_id' => $reservation]))
        ->assertOk()->assertJsonPath('code', 'SECONDARY_SETTLED');

    $this->actingAs($buyer)->get(route('investor.market'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('market.listings', [])->where('market.trades.0.side', 'buy')->where('market.trades.0.units', '10')
            ->where('market.sellable.0.free_units', '10')->etc());
    $this->actingAs($seller)->get(route('investor.market'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('market.trades.0.side', 'sell')->where('market.sellable.0.units', (string) ($holding->units - 10))->etc());

    $again = $this->actingAs($seller)->postJson(route('investor.market_list'), $command(['lot_id' => $holding->holdingId, 'units' => '5', 'price' => $price]))
        ->assertOk()->json('data.listing.listing_id');
    $this->actingAs($seller)->postJson(route('investor.market_withdraw', $again), $command([]))->assertOk()->assertJsonPath('code', 'SECONDARY_LISTING_CANCELLED');
    // A withdrawn listing stays under Orders → Cancelled (QA #663).
    $this->actingAs($seller)->get(route('investor.market'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('market.closed.0.listing_id', $again)->where('market.closed.0.state', 'cancelled')
            ->where('market.closed.0.cancelled_by', 'seller')->where('market.closed.0.listed_units', '5')->where('market.closed.0.price.amount', $price)->etc());
    $this->actingAs($buyer)->get(route('investor.market'))->assertInertia(fn (Assert $page): Assert => $page->where('market.closed', [])->etc());
    $this->actingAs($seller)->postJson(route('investor.market_list'), $command(['lot_id' => $holding->holdingId, 'units' => '0', 'price' => $price]))
        ->assertUnprocessable();
});

it('shows a resale on both sides: the buyer\'s units in Portfolio and both wallets\' lines with receipts, and the seller\'s holding less what it sold (QA #656)', function (): void {
    $buyer = InvestorWalletFixture::funded('2000000')['user'];
    $note = simulatedIssuedNote();
    $holding = app(RetainedNoteSchedules::class)->find($note['campaign'])->holdings[0];
    $seller = User::query()->where('party_id', $holding->partyId)->sole();
    $command = fn (array $input): array => ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, ...$input];
    $title = app(CampaignTitles::class)->title($note['campaign']);
    $cap = null;
    $this->actingAs($seller)->get(route('investor.market'))->assertInertia(function (Assert $page) use (&$cap): Assert {
        $cap = $page->toArray()['props']['market']['sellable'][0]['cap']['amount'];

        return $page;
    });
    $price = (string) intdiv((int) $cap, 2);
    $listed = $this->actingAs($seller)->postJson(route('investor.market_list'), $command(['lot_id' => $holding->holdingId, 'units' => '10', 'price' => $price]))
        ->assertOk()->json('data.listing.listing_id');
    $reservation = $this->actingAs($buyer)->postJson(route('investor.market_reserve', $listed), $command(['units' => '2']))
        ->assertOk()->json('data.reservation.reservation_id');
    $this->actingAs($buyer)->postJson(route('investor.market_settle', $listed), $command(['reservation_id' => $reservation]))->assertOk();
    $transfer = SecondaryTransfer::query()->where('reservation_id', $reservation)->sole();
    $paid = (string) ((int) $price * 2);

    $bought = null;
    $this->actingAs($buyer)->get(route('investor.wallet', ['movement' => 'external']))
        ->assertInertia(function (Assert $page) use ($title, $paid, &$bought): Assert {
            $bought = $page->toArray()['props']['history']['items'][0]['id'];

            return $page->where('history.items.0.kind', 'resale')->where('history.items.0.direction', 'out')->where('history.items.0.amount.amount', $paid)
                ->where('history.items.0.counterparty', $title)->where('history.items.0.units', '2')->etc();
        });
    $this->actingAs($buyer)->get(route('investor.wallet', ['receipt' => $bought]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('receipt.receipt.code', 'SECONDARY_SETTLED')->where('receipt.receipt.amount.amount', $paid)
            ->where('receipt.receipt.units', '2')->where('receipt.kind', 'resale')->etc());
    $this->actingAs($seller)->get(route('investor.wallet', ['movement' => 'external']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('history.items.0.kind', 'resale')->where('history.items.0.direction', 'in')
            ->where('history.items.0.amount.amount', $transfer->seller_receives)->where('history.items.0.counterparty', $title)->etc());

    // Reading what is held needs no simulation; off UAT, published audit seals verify against the test database.
    app()->forgetInstance(EnvironmentIsolation::class);
    config(['app.uat_money_simulation' => false]);
    // Both sides hear that the trade settled (QA #656).
    $this->actingAs($buyer)->getJson(route('investor.notices'))->assertOk()
        ->assertJson(fn (AssertableJson $json): AssertableJson => $json->where('data.notices.0.kind', 'market.units_bought')
            ->where('data.notices.0.facts.units', '2')->where('data.notices.0.facts.amount', $paid)->where('data.notices.0.facts.title', $title)
            ->where('data.notices.0.link.url', route('investor.portfolio', [], false))->etc());
    $this->actingAs($seller)->getJson(route('investor.notices'))->assertOk()
        ->assertJson(fn (AssertableJson $json): AssertableJson => $json->where('data.notices.0.kind', 'market.units_sold')
            ->where('data.notices.0.facts.amount', $transfer->seller_receives)->where('data.notices.0.facts.fee', $transfer->seller_fee)
            ->where('data.notices.0.link.url', route('investor.wallet', [], false))->etc());

    // Staff open the note and see who holds its units now, the buyer's bought lot included (QA #646).
    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Notes review.');
    $this->actingAs($analyst)->get(route('staff.notes.index', ['note' => $note['campaign']]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('drawer.id', fn (string $id): bool => strtolower($id) === strtolower($note['campaign']))
            ->where('drawer.holders', fn (Collection $holders): bool => $holders->contains(fn (array $row): bool => $row['bought'] && $row['units'] === '2' && $row['name'] === $buyer->name)
                && $holders->contains(fn (array $row): bool => ! $row['bought'] && $row['name'] === $seller->name && $row['units'] === (string) ($holding->units - 2)))
            ->where('drawer.investors', 3)->where('drawer.close.url', route('staff.notes.index', [], false))
            // The list row counts the same holders as its drawer (#663).
            ->where('notes', fn (Collection $rows): bool => $rows->contains(fn (array $row): bool => strtolower($row['id']) === strtolower($note['campaign']) && $row['investors'] === 3))
            ->where('notes.0.link.url', fn (string $url): bool => str_contains($url, 'note='))->etc());
    $this->actingAs($analyst)->get(route('staff.notes.index'))->assertInertia(fn (Assert $page): Assert => $page->where('drawer', null)->etc());

    $this->actingAs($buyer)->get(route('investor.portfolio', ['tab' => 'secondary']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('bought.0.lot_id', $transfer->id)->where('bought.0.units', '2')
            ->where('bought.0.price.amount', $price)->where('bought.0.paid.amount', $paid)->where('bought.0.link.url', route('investor.lots.show', $transfer->id, false))
            ->where('totals.businesses', 1)->where('totals.invested.amount', $paid)->etc());
    $this->actingAs($buyer)->get(route('investor.portfolio'))->assertInertia(fn (Assert $page): Assert => $page->where('bought.0.lot_id', $transfer->id)
        ->where('payouts', fn ($months): bool => count($months) > 0)->where('totals.next_payout', fn ($next): bool => $next !== null)->etc());
    $this->actingAs($buyer)->get(route('investor.portfolio', ['tab' => 'matured']))->assertInertia(fn (Assert $page): Assert => $page->where('bought', [])->etc());

    // The raise page counts who holds the note now, the buyer included (QA #642).
    $this->actingAs($note['payer'])->get(route('business.campaigns.show', ['business' => $note['business'], 'campaign' => $note['campaign']]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('investors.holders', 3)
            ->where('investors.list', fn (Collection $rows): bool => $rows->contains('units', '2') && (int) $rows->sum('units') === array_sum(array_map(fn ($issued): int => $issued->units,
                app(RetainedNoteSchedules::class)->find($note['campaign'])->holdings)))->etc());
    // The bought lot opens its own page: what it cost, its schedule for the units held, and what it is paid (QA #663 item 2).
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $this->actingAs($buyer)->get(route('investor.lots.show', $transfer->id))
        ->assertInertia(fn (Assert $page): Assert => $page->component('investor/lot')->where('contract_version', 'investor-lot-v1')
            ->where('lot.lot_id', $transfer->id)->where('lot.units', '2')->where('lot.paid.amount', $paid)->where('lot.price.amount', $price)
            ->where('lot.received.amount', '0')->where('lot.payments_made', 0)->has('lot.schedule', count($schedule->instalments))
            ->where('lot.schedule.0.status', 'upcoming')->where('lot.next_payment', fn ($next): bool => $next !== null)
            ->where('links.back.url', route('investor.portfolio', ['tab' => 'secondary'], false))->etc());
    $this->actingAs($seller)->get(route('investor.lots.show', $transfer->id))->assertNotFound();
    $this->actingAs($buyer)->get(route('investor.lots.show', strtolower((string) Str::ulid())))->assertNotFound();
    asUatProfile(...config('app.uat_money_simulation_businesses'));
    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $schedule->instalments[0]->gross]])->assertOk();
    app()->forgetInstance(EnvironmentIsolation::class);
    config(['app.uat_money_simulation' => false]);
    $lotPayout = DB::table('servicing_payouts')->where('lot_type', 'secondary_transfer')->where('lot_id', $transfer->id)->sole();
    $this->actingAs($buyer)->get(route('investor.lots.show', $transfer->id))
        ->assertInertia(fn (Assert $page): Assert => $page->where('lot.schedule.0.status', 'paid')->where('lot.schedule.0.net.amount', (string) $lotPayout->net)
            ->where('lot.received.amount', (string) $lotPayout->net)->where('lot.payments_made', 1)->etc());

    $this->actingAs($seller)->get(route('investor.holdings.show', $holding->holdingId))
        ->assertInertia(fn (Assert $page): Assert => $page->where('holding.resold_units', '2')->where('holding.issue.units', (string) $holding->units)
            ->where('holding.invested.amount', (string) intdiv((int) $holding->principal * ($holding->units - 2), $holding->units))
            ->where('holding.issue.principal.amount', $holding->principal)->etc());
    $this->actingAs($seller)->get(route('investor.portfolio'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('holdings.0.invested.amount', (string) intdiv((int) $holding->principal * ($holding->units - 2), $holding->units))->etc());
});

it('reads the Market closed, offering nothing, while resale is off', function (): void {
    $investor = InvestorWalletFixture::ready()['user'];

    $this->actingAs($investor)->get(route('investor.market'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('market', ['open' => false, 'listings' => [], 'sellable' => [], 'trades' => [], 'closed' => []])
            ->where('actions.list', null)->where('links.wallet.url', route('investor.wallet', [], false))->etc());
    $this->actingAs($investor)->postJson(route('investor.market_list'), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
        'lot_id' => strtolower((string) Str::ulid()), 'units' => '1', 'price' => '100'])->assertStatus(503)->assertJsonPath('code', 'SECONDARY_TRADING_DISABLED');
});

it('reopens resale and drops a paid instalment from the cap once its payouts post', function (): void {
    $note = simulatedIssuedNote();
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $postedThrough = fn (): ?string => DB::transaction(fn (): ?string => app(SecondaryNotes::class)->find($note['campaign'])['posted_through']);
    expect($postedThrough())->toBeNull();
    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $schedule->instalments[0]->gross]])->assertOk();

    expect($postedThrough())->toBe($schedule->instalments[0]->dueDate);
});

it('moves the raise on once its payout settles, and Home past a paid instalment', function (): void {
    $note = simulatedIssuedNote();
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $closing = DB::table('disbursement_closings')->where('kind', 'issued')->sole();
    $disbursement = DB::table('disbursements')->where('id', $closing->disbursement_id)->sole();
    $net = (string) ((int) $disbursement->amount - (int) $disbursement->platform_fee);

    $this->actingAs($note['payer'])->get(route('business.campaigns.show', ['business' => $note['business'], 'campaign' => $note['campaign']]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('campaign.lifecycle', 'issued')->where('note.progress.phase', 'disbursed')
            ->where('note.progress.amount.amount', $net)->where('note.progress.platform_fee.amount', (string) $disbursement->platform_fee)
            ->where('note.progress.receipt.code', 'DISBURSEMENT_ISSUED')->where('note.progress.receipt.reference', 'DSB-'.strtoupper(substr($disbursement->id, -8)))
            ->where('note.progress.receipt.link.url', route('business.campaigns.show', ['business' => $note['business'], 'campaign' => $note['campaign']], false))
            ->where('note.progress.effective_date', $schedule->closing->effectiveDate)->etc());
    $holder = User::query()->where('party_id', $schedule->holdings[0]->partyId)->sole();
    app()->forgetInstance(EnvironmentIsolation::class);
    $this->actingAs($holder)->get(route('investor.deals.show', $note['campaign']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('deal.lifecycle', 'issued')->etc());
    asUatProfile($note['business']);
    $this->actingAs($note['payer'])->get(route('business.show', $note['business']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('today', fn (Collection $today): bool => $today->contains(fn (array $item): bool => $item['kind'] === 'repayment_due'
            && $item['due_on'] === $schedule->instalments[0]->dueDate))->etc());

    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $schedule->instalments[0]->gross]])->assertOk();
    $this->actingAs($note['payer'])->get(route('business.show', $note['business']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('today', fn (Collection $today): bool => $today->contains(fn (array $item): bool => $item['kind'] === 'repayment_due'
            && $item['due_on'] === $schedule->instalments[1]->dueDate))->etc());
});

it('lists a repayment in the Business wallet with its receipt, opens it from Repayments, and notifies the Business (QA #643)', function (): void {
    $note = simulatedIssuedNote();
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $gross = $schedule->instalments[0]->gross;
    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $gross]])->assertOk();
    $repayment = DB::table('business_repayments')->where('note_id', strtolower($note['campaign']))->sole();
    $receipt = route('business.wallet.show', ['business' => $note['business'], 'receipt' => $repayment->id], false);

    $this->actingAs($note['payer'])->get(route('business.wallet.show', ['business' => $note['business'], 'movement' => 'internal']))
        ->assertInertia(fn (Assert $page): Assert => $page->has('history.items', 1)->where('history.items.0.id', $repayment->id)
            ->where('history.items.0.kind', 'repayment')->where('history.items.0.direction', 'out')->where('history.items.0.amount.amount', $gross)
            ->where('history.items.0.instalment_indexes', [1])->where('history.items.0.link.url', $receipt)->etc());
    $all = $this->actingAs($note['payer'])->get(route('business.wallet.show', $note['business']))->viewData('page')['props']['history'];
    $ids = array_column($all['items'], 'id');
    $newestFirst = $ids;
    rsort($newestFirst);
    expect($all['movement'])->toBe('all')->and(array_column($all['filters'], 'key'))->toBe(['all', 'external', 'internal'])
        ->and($ids)->toContain($repayment->id)->and($ids)->toBe($newestFirst);
    $this->actingAs($note['payer'])->get($receipt)
        ->assertInertia(fn (Assert $page): Assert => $page->where('receipt.kind', 'repayment')->where('receipt.receipt.code', 'REPAYMENT_RECEIVED')
            ->where('receipt.receipt.reference', 'RZR-'.strtoupper(substr($repayment->id, -10)))
            ->where('receipt.receipt.policy_version', SimulatedServicingPayouts::POLICY_VERSION)->etc());
    $this->actingAs($note['payer'])->get(route('business.repayments.show', $note['business']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('recent.0.link.url', $receipt)->where('schedule.0.receipt.url', $receipt)
            ->where('schedule.1.receipt', null)->missing('schedule.0.repayment_id')->etc());
    $this->actingAs($note['payer'])->getJson(route('business.notices.index', $note['business']))->assertOk()
        ->assertJson(fn (AssertableJson $json): AssertableJson => $json->where('data.notices.0.kind', 'business.repayment_received')
            ->where('data.notices.0.facts.amount', $gross)->where('data.notices.0.facts.instalments', '1')->where('data.notices.0.link.url', $receipt)->etc());
    $payout = DB::table('servicing_payouts')->where('repayment_id', $repayment->id)->where('party_id', $schedule->holdings[0]->partyId)->sole();
    // An unsold Holding is paid exactly its own scheduled row, the split its schedule shows (QA #645).
    expect([$payout->principal, $payout->contractual_return])
        ->toBe([$schedule->holdings[0]->instalments[0]->principal, $schedule->holdings[0]->instalments[0]->contractualReturn]);
    $holder = User::query()->where('party_id', $schedule->holdings[0]->partyId)->sole();
    app()->forgetInstance(EnvironmentIsolation::class);
    $this->actingAs($holder)->getJson(route('investor.notices'))->assertOk()
        ->assertJson(fn (AssertableJson $json): AssertableJson => $json->where('data.notices.0.kind', 'wallet.payout_received')
            ->where('data.notices.0.facts.amount', $payout->net)->where('data.notices.0.facts.fee', $payout->fee)
            ->where('data.notices.0.facts.gross', (string) ((int) $payout->principal + (int) $payout->contractual_return))
            ->where('data.notices.0.link.url', route('investor.wallet', [], false))->etc());
});

it('refuses a repayment whose payout cannot post, so nothing is collected without paying its holders', function (): void {
    $note = simulatedIssuedNote();
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $holders = $this->createStub(NoteUnitHolders::class);
    $holders->method('lots')->willReturn([['lot_type' => 'primary_holding', 'lot_id' => $schedule->holdings[0]->holdingId,
        'party_id' => $schedule->holdings[0]->partyId, 'units' => '1', 'ordinals' => [['first' => '999999999', 'last' => '999999999']]]]);
    app()->instance(NoteUnitHolders::class, $holders);

    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $schedule->instalments[0]->gross]])->assertServerError();
    expect(DB::table('business_repayments')->count())->toBe(0)->and(DB::table('servicing_payouts')->count())->toBe(0);
});

it('shows instalments past their date as due now and overdue with their days past due', function (): void {
    $note = simulatedIssuedNote();
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $this->travelTo(CarbonImmutable::parse(substr($schedule->instalments[1]->dueDate, 0, 10).' 12:00', 'Africa/Kigali'));
    $dueNow = (int) $schedule->instalments[0]->gross + (int) $schedule->instalments[1]->gross;

    $this->actingAs($note['payer'])->get(route('business.repayments.show', $note['business']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('servicing.state', 'overdue')->where('servicing.dpd', fn (int $dpd): bool => $dpd > 0)
            ->where('schedule.0.status', 'overdue')->where('schedule.1.status', 'due')->where('schedule.1.dpd', 0)
            ->where('servicing.due_now.total.amount', (string) $dueNow)->where('pay.options.0.key', 'due_now')->where('pay.options.0.instalment_indexes', [1, 2])
            ->where('pay.options.1.key', 'next_instalment')->where('pay.options.1.instalment_indexes', [3]));
});

it('reads repaid once every instalment is paid, and offers nothing more', function (): void {
    $note = simulatedIssuedNote('20000000');
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $this->travelTo(CarbonImmutable::parse(substr($schedule->instalments[count($schedule->instalments) - 1]->dueDate, 0, 10).' 12:00', 'Africa/Kigali'));
    $total = array_sum(array_map(fn ($row): int => (int) $row->gross, $schedule->instalments));

    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'due_now',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => (string) $total]])->assertOk();

    $this->actingAs($note['payer'])->get(route('business.repayments.show', $note['business']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('servicing.state', 'repaid')->where('servicing.next', null)->where('servicing.progress.repaid_pct', '100.0')
            ->where('allowed_actions', [])->where('actions.pay', null)->where('pay.options', []));
    expect((string) DB::table('servicing_payouts')->sum('principal'))->toBe($schedule->principal);
});

it('files a note repaid in full as repaid on Home and the staff screens, scores it on time, and frees its principal from capacity (QA #672)', function (): void {
    $note = simulatedIssuedNote('20000000');
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $reservation = (string) BusinessCampaign::query()->whereKey($note['campaign'])->value('exposure_reservation_id');
    expect(array_column(app(BusinessExposureStore::class)->current($note['business']), 'id'))->toContain($reservation);

    // Every instalment paid early, one at a time.
    foreach ($schedule->instalments as $offset => $instalment) {
        $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
            ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
                'expected_servicing_revision' => $offset + 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $instalment->gross]])->assertOk();
    }
    expect(app(ServicingRecord::class)->forBusiness($note['business']))->toBe(['repaid' => [['campaign_id' => $note['campaign'],
        'exposure_reservation_id' => $reservation, 'principal' => $schedule->principal]],
        'instalments_paid' => count($schedule->instalments), 'paid_on_time' => count($schedule->instalments)]);

    $this->actingAs($note['payer'])->get(route('business.show', $note['business']))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('notes.0.status', 'completed')->where('capital.active_notes', 0)
            ->where('capital.on_time_pct', 100)->where('today', fn (Collection $today): bool => ! $today->contains('kind', 'repayment_due'))->etc());
    // Its principal is no longer owed, so it no longer counts against capacity, while the note itself stays readable.
    expect(array_column(app(BusinessExposureStore::class)->current($note['business']), 'id'))->not->toContain($reservation)
        ->and(DB::transaction(fn (): array => app(PrimaryCampaignSource::class)->lockRetained($note['campaign']))['id'])->toBe($note['campaign']);

    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Repaid note review.');
    $this->actingAs($analyst)->get(route('staff.businesses.index', ['business' => $note['business']]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.stats.0.value.value', 0)->where('party.list.rows.0.detail.value', 'completed')
            ->where('party.list.rows.0.tone', 'grey')
            // Activity lists the payout and every repayment (QA #642).
            ->where('party.history', fn (Collection $history): bool => $history->where('action.code', 'note.repay')->count() === count($schedule->instalments)
                && $history->contains(fn (array $entry): bool => $entry['action']['code'] === 'campaign.disburse' && str_starts_with($entry['action']['label'], 'Note disbursed · ')))->etc());
    // The raise page reads repaid, and shows the platform fee taken at disbursement (QA #642).
    $this->actingAs($note['payer'])->get(route('business.campaigns.show', ['business' => $note['business'], 'campaign' => $note['campaign']]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('campaign.lifecycle', 'repaid')->where('note.progress.phase', 'disbursed')
            ->where('note.progress.platform_fee.amount', fn (string $fee): bool => (int) $fee > 0)->has('note.progress.gross')->etc());
    // The register reads each deal's sealed reports, which checks this test database is a dedicated one.
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile,assertSafeConfiguration]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat', 'assertSafeConfiguration' => null]);
    app()->instance(EnvironmentIsolation::class, $isolation);
    $this->actingAs($analyst)->get(route('staff.notes.index', ['tab' => 'matured']))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('notes.0.id', $note['campaign'])->where('notes.0.state', 'matured')->etc());
    $this->actingAs($analyst)->get('/admin/dashboard')->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('funnel.3', ['stage' => 'repaying', 'count' => 0, 'width_pct' => 0])
            ->where('funnel.4.stage', 'matured')->where('funnel.4.count', 1)->etc());
    // Repayments still shows the repaid note's schedule beside its repaid statement.
    asUatProfile($note['business']);
    $this->actingAs($note['payer'])->get(route('business.repayments.show', $note['business']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('servicing.state', 'repaid')
            ->where('issued.0.note_id', $note['campaign'])->etc());
});

it('counts an instalment paid after its due date as late', function (): void {
    $note = simulatedIssuedNote('20000000');
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $this->travelTo(CarbonImmutable::parse(substr($schedule->instalments[1]->dueDate, 0, 10).' 12:00', 'Africa/Kigali'));
    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'due_now',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => (string) ((int) $schedule->instalments[0]->gross + (int) $schedule->instalments[1]->gross)]])
        ->assertOk();

    expect(app(ServicingRecord::class)->forBusiness($note['business']))->toBe(['repaid' => [], 'instalments_paid' => 2, 'paid_on_time' => 1]);
    $this->actingAs($note['payer'])->get(route('business.show', $note['business']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('notes.0.status', 'repaying')->where('capital.on_time_pct', 50)->etc());
    // Outside the simulation nothing has been repaid or paid.
    app()->forgetInstance(EnvironmentIsolation::class);
    expect(app(ServicingRecord::class)->forBusiness($note['business']))->toBe(['repaid' => [], 'instalments_paid' => 0, 'paid_on_time' => 0]);
});

it('pays a sold unit\'s share to whoever holds it at collection, and a later resale moves nothing already paid', function (): void {
    $note = simulatedIssuedNote();
    $buyer = InvestorWalletFixture::ready()['user']->party_id;
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $holders = $this->createStub(NoteUnitHolders::class);
    $seller = $schedule->holdings[0];
    $holders->method('lots')->willReturn([
        ['lot_type' => 'primary_holding', 'lot_id' => $seller->holdingId, 'party_id' => $seller->partyId, 'units' => '1000',
            'ordinals' => [['first' => (string) ($seller->ordinals[0]['first'] + 80), 'last' => (string) $seller->ordinals[0]['last']]]],
        ['lot_type' => 'secondary_transfer', 'lot_id' => strtolower((string) Str::ulid()), 'party_id' => $buyer, 'units' => '80',
            'ordinals' => [['first' => (string) $seller->ordinals[0]['first'], 'last' => (string) ($seller->ordinals[0]['first'] + 79)]]],
        ['lot_type' => 'primary_holding', 'lot_id' => $schedule->holdings[1]->holdingId, 'party_id' => $schedule->holdings[1]->partyId, 'units' => '1080',
            'ordinals' => [['first' => (string) $schedule->holdings[1]->ordinals[0]['first'], 'last' => (string) $schedule->holdings[1]->ordinals[0]['last']]]],
    ]);
    app()->instance(NoteUnitHolders::class, $holders);

    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $schedule->instalments[0]->gross]])->assertOk();
    $bought = DB::table('servicing_payouts')->where('party_id', $buyer)->sole();
    expect(DB::table('servicing_payouts')->count())->toBe(3)->and($bought->lot_type)->toBe('secondary_transfer')->and((int) $bought->units)->toBe(80)
        ->and($bought->rate_bps)->toBe($seller->terms['earnings_fee']['rate_bps'])
        ->and((string) DB::table('servicing_payouts')->sum('principal'))->toBe($schedule->instalments[0]->principal)
        ->and(investorAvailable($buyer))->toBe((string) $bought->net);
    // The resold Holding's own row is shared 1000:80 between seller and buyer, the unsold one is paid its row exactly,
    // and together they reconcile to the repayment (QA #645).
    $kept = DB::table('servicing_payouts')->where('lot_id', $seller->holdingId)->sole();
    $other = DB::table('servicing_payouts')->where('lot_id', $schedule->holdings[1]->holdingId)->sole();
    $row = $seller->instalments[0];
    expect((int) $kept->principal + (int) $bought->principal)->toBe((int) $row->principal)
        ->and((int) $kept->contractual_return + (int) $bought->contractual_return)->toBe((int) $row->contractualReturn)
        ->and((int) $bought->principal)->toBe(intdiv((int) $row->principal * 80, 1080) + ((int) $row->principal * 80 % 1080 * 2 > 1080 ? 1 : 0))
        ->and([$other->principal, $other->contractual_return])->toBe([$schedule->holdings[1]->instalments[0]->principal, $schedule->holdings[1]->instalments[0]->contractualReturn])
        ->and((int) DB::table('servicing_payouts')->sum(DB::raw('principal + contractual_return')))->toBe((int) DB::table('business_repayments')->value('amount'));

    // The buyer resells all 80 units after collection: the repayment already paid stays with the holder at collection.
    $later = $this->createStub(NoteUnitHolders::class);
    $later->method('lots')->willReturn([['lot_type' => 'primary_holding', 'lot_id' => $seller->holdingId, 'party_id' => $seller->partyId, 'units' => '1080',
        'ordinals' => [['first' => (string) $seller->ordinals[0]['first'], 'last' => (string) $seller->ordinals[0]['last']]]]]);
    app()->instance(NoteUnitHolders::class, $later);
    $repaymentId = (string) DB::table('business_repayments')->value('id');
    expect(app(SimulatedServicingPayouts::class)->payOut($repaymentId))->toBe(0)
        ->and(DB::table('servicing_payouts')->count())->toBe(3)->and(investorAvailable($buyer))->toBe((string) $bought->net);
});

it('refuses simulated servicing and payouts outside the simulation, and finds nothing to pay on where none runs', function (): void {
    expect(fn () => app(SimulatedNoteServicing::class)->current('x'))->toThrow(LogicException::class, 'UAT_SIMULATION_ONLY')
        ->and(fn () => app(SimulatedServicingPayouts::class)->payOut('x'))->toThrow(LogicException::class, 'UAT_SIMULATION_ONLY')
        ->and(app(ServicingStatements::class)->current('x'))->toBeNull()
        ->and(app(ServicingPayouts::class)->payOut('x'))->toBe(0);

    $namedBusiness = strtolower((string) Str::ulid());
    asUatProfile($namedBusiness);
    expect(app(SimulatedServicingPayouts::class)->payOut(strtolower((string) Str::ulid())))->toBe(0)
        ->and(app(SimulatedNoteServicing::class)->lockForPayment($namedBusiness, strtolower((string) Str::ulid())))->toBeNull()
        ->and(app(SimulatedNoteServicing::class)->current($namedBusiness))->toBeNull();
});

it('collects a repayment under the same note lock resale settles under, so neither sees the holders change mid-way', function (): void {
    $note = simulatedIssuedNote();
    config(['database.connections.resale_watcher' => config('database.connections.pgsql')]);
    $resale = DB::connection('resale_watcher');
    $noteLocked = fn (): bool => ! $resale->transaction(fn (): bool => (bool) $resale->scalar('SELECT pg_try_advisory_xact_lock(hashtextextended(?, 0))',
        ['secondary-note:'.$note['campaign']]));
    try {
        // Collection holds the note: resale settlement on the same note must wait.
        expect($noteLocked())->toBeFalse();
        DB::beginTransaction();
        expect(app(SimulatedNoteServicing::class)->lockForPayment($note['business'], strtolower($note['campaign'])))->not->toBeNull()
            ->and($noteLocked())->toBeTrue();
        DB::rollBack();
        expect($noteLocked())->toBeFalse();

        // Resale settlement holds the note: collection waits for it rather than reading the holders mid-transfer.
        $resale->beginTransaction();
        $resale->select('SELECT pg_advisory_xact_lock_shared(hashtextextended(?, 0))', ['secondary-market']);
        $resale->select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['secondary-note:'.$note['campaign']]);
        DB::beginTransaction();
        DB::statement("SET LOCAL lock_timeout = '200ms'");
        expect(fn () => app(SimulatedNoteServicing::class)->lockForPayment($note['business'], strtolower($note['campaign'])))
            ->toThrow(QueryException::class, 'lock timeout');
    } finally {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($resale->transactionLevel() > 0) {
            $resale->rollBack();
        }
        DB::purge('resale_watcher');
    }
});

it('acts only on the synthetic test Businesses staging names', function (): void {
    asUatProfile(' 01ABC ', '01def');
    $simulation = app(UatMoneySimulation::class);
    expect($simulation->covers('01abc'))->toBeTrue()->and($simulation->covers('01DEF'))->toBeTrue()->and($simulation->covers('01xyz'))->toBeFalse();
    config(['app.uat_money_simulation_businesses' => null]);
    expect($simulation->covers('01abc'))->toBeFalse();
    config(['app.uat_money_simulation' => false, 'app.uat_money_simulation_businesses' => ['01abc']]);
    expect($simulation->covers('01abc'))->toBeFalse();
});

it('leaves an unnamed Business\'s funded raise, repayments and resale untouched', function (): void {
    ['campaign' => $publication] = PrimaryHoldingFixture::committed(units: ['1080', '1080'], requoted: [0]);
    $businessId = (string) BusinessCampaign::query()->whereKey($publication->id)->value('business_id');
    asUatProfile(strtolower((string) Str::ulid()));

    expect(Artisan::call('disbursements:reconcile'))->toBe(0)
        ->and(Disbursement::query()->where('business_campaign_id', $publication->id)->exists())->toBeFalse()
        ->and(app(ServicingStatements::class)->current($businessId))->toBeNull()
        ->and(app(SimulatedNoteServicing::class)->lockForPayment($businessId, strtolower($publication->id)))->toBeNull();
});

it('lists for resale only notes of a named Business, and passes every other market command through', function (): void {
    $note = simulatedIssuedNote();
    $holdingId = (string) DB::table('primary_holdings')->where('business_campaign_id', $note['campaign'])->value('id');
    $market = $this->createMock(SecondaryMarket::class);
    $market->expects($this->exactly(2))->method('list')->willReturnCallback(fn (int $userId, int $revision, string $holding): array => ['code' => 'SECONDARY_LISTED', 'holding' => $holding]);
    $market->expects($this->once())->method('reserve')->with(1, 1, 'l', '4', 'r2')->willReturn(['code' => 'reserve']);
    $market->expects($this->once())->method('settle')->with(1, 1, 'l', 'v', 'r3')->willReturn(['code' => 'settle']);
    $market->expects($this->once())->method('amend')->with(1, 1, 'l', '2800', 'r4')->willReturn(['code' => 'amend']);
    $market->expects($this->once())->method('halt')->with(2, null, 'why', 'r5')->willReturn(['code' => 'halt']);
    $market->expects($this->once())->method('lift')->with(2, 'n', 'why', 'r6')->willReturn(['code' => 'lift']);
    $market->expects($this->once())->method('cancelListing')->with(2, 'l', 'why', 'r7')->willReturn(['code' => 'cancel']);
    $simulated = new SimulatedSecondaryMarket($market, app(UatMoneySimulation::class));

    // A lot the market does not know is left to the market itself to refuse.
    expect($simulated->list(1, 1, $holdingId, '4', '2700', 'r1'))->toBe(['code' => 'SECONDARY_LISTED', 'holding' => $holdingId])
        ->and($simulated->list(1, 1, 'unknown-lot', '4', '2700', 'r0'))->toBe(['code' => 'SECONDARY_LISTED', 'holding' => 'unknown-lot'])
        ->and($simulated->reserve(1, 1, 'l', '4', 'r2'))->toBe(['code' => 'reserve'])
        ->and($simulated->settle(1, 1, 'l', 'v', 'r3'))->toBe(['code' => 'settle'])
        ->and($simulated->amend(1, 1, 'l', '2800', 'r4'))->toBe(['code' => 'amend'])
        ->and($simulated->halt(2, null, 'why', 'r5'))->toBe(['code' => 'halt'])
        ->and($simulated->lift(2, 'n', 'why', 'r6'))->toBe(['code' => 'lift'])
        ->and($simulated->cancelListing(2, 'l', 'why', 'r7'))->toBe(['code' => 'cancel']);

    config(['app.uat_money_simulation_businesses' => []]);
    expect(fn () => $simulated->list(1, 1, $holdingId, '4', '2700', 'r8'))->toThrow(CommandRejection::class, 'SECONDARY_TRADING_DISABLED');
});

it('names its Businesses before limiting, so unnamed funded raises cannot crowd out named ones', function (): void {
    asUatProfile('01NAMED1', '01named2');
    $ref = fn (string $campaign, string $business): FundedCampaignRef => new FundedCampaignRef($campaign, $business, '2026-11-15T00:00:00Z');
    $pages = [
        '' => [$ref('09', '01other'), $ref('08', '01other')],
        '08' => [$ref('07', '01other'), $ref('06', '01named1')],
        '06' => [$ref('05', '01named2'), $ref('04', '01named1')],
    ];
    $retained = $this->createMock(FundedCampaigns::class);
    $retained->expects($this->exactly(3))->method('funded')
        ->willReturnCallback(fn (?string $before, int $limit): array => $limit === 2 ? $pages[$before ?? ''] : []);
    $simulated = new SimulatedFundedCampaigns($retained, app(UatMoneySimulation::class));

    expect(array_map(fn (FundedCampaignRef $campaign): string => $campaign->campaignId, $simulated->funded(null, 2)))->toBe(['06', '05']);

    $short = $this->createMock(FundedCampaigns::class);
    $short->expects($this->once())->method('funded')->willReturn([$ref('03', '01other')]);
    expect((new SimulatedFundedCampaigns($short, app(UatMoneySimulation::class)))->funded(null, 2))->toBe([])
        ->and($simulated->funded(null, 0))->toBe([]);
});

it('stops a disbursement already under way once its Business is taken off the list, and finishes it once named again', function (): void {
    ['campaign' => $publication] = PrimaryHoldingFixture::committed(units: ['1080', '1080'], requoted: [0]);
    $businessId = (string) BusinessCampaign::query()->whereKey($publication->id)->value('business_id');
    asUatProfile($businessId);
    expect(Artisan::call('disbursements:reconcile'))->toBe(0)
        ->and(Disbursement::query()->where('business_campaign_id', $publication->id)->exists())->toBeTrue();

    // Taken off the list while its disbursement waits: it can no longer be locked to advance, so linking an account sends nothing.
    config(['app.uat_money_simulation_businesses' => []]);
    expect(fn () => DB::transaction(fn () => app(FundedCampaigns::class)->lockFunded($publication->id)))
        ->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE');
    BusinessFundingMethod::factory()->create(['business_id' => $businessId, 'kind' => 'bank', 'label' => 'Bank of Kigali',
        'masked' => 'Account ····6789', 'reference' => 'bk:000123456789', 'verified_at' => now('UTC')->subMinute()]);
    Artisan::call('disbursements:reconcile');
    expect(DisbursementIntent::query()->exists())->toBeFalse()->and(DisbursementClosing::query()->exists())->toBeFalse();

    config(['app.uat_money_simulation_businesses' => [$businessId]]);
    Artisan::call('disbursements:reconcile');
    Artisan::call('disbursements:reconcile');
    expect(DisbursementClosing::query()->where('intent_id', DisbursementIntent::query()->sole()->id)->sole()->kind)->toBe('issued');
});

it('refuses to reserve, settle or reprice an existing listing once its Business is taken off the list, but lets it be withdrawn', function (): void {
    $note = simulatedIssuedNote();
    $holding = DB::table('primary_holdings')->where('business_campaign_id', $note['campaign'])->orderBy('id')->first();
    $seller = User::query()->where('party_id', $holding->party_id)->sole();
    $listingId = app(EloquentSecondaryMarket::class)->list($seller->id, 1, (string) $holding->id, '4', '1000', (string) Str::uuid())['data']['listing']['listing_id'];
    $market = $this->createMock(SecondaryMarket::class);
    $market->expects($this->once())->method('reserve')->willReturn(['code' => 'reserve']);
    $market->expects($this->once())->method('amend')->with(1, 1, $listingId, null, 'withdraw')->willReturn(['code' => 'withdrawn']);
    $market->expects($this->never())->method('settle');
    $simulated = new SimulatedSecondaryMarket($market, app(UatMoneySimulation::class));
    expect($simulated->reserve(1, 1, $listingId, '4', 'named'))->toBe(['code' => 'reserve']);

    config(['app.uat_money_simulation_businesses' => []]);
    expect(fn () => $simulated->reserve(1, 1, $listingId, '4', 'r1'))->toThrow(CommandRejection::class, 'SECONDARY_TRADING_DISABLED')
        ->and(fn () => $simulated->settle(1, 1, $listingId, 'v', 'r2'))->toThrow(CommandRejection::class, 'SECONDARY_TRADING_DISABLED')
        ->and(fn () => $simulated->amend(1, 1, $listingId, '1200', 'r3'))->toThrow(CommandRejection::class, 'SECONDARY_TRADING_DISABLED')
        ->and($simulated->amend(1, 1, $listingId, null, 'withdraw'))->toBe(['code' => 'withdrawn']);
});

it('records a named Business\'s fully committed raise as funded on UAT, so its disbursement opens on the same run, once', function (): void {
    ['campaign' => $publication] = PrimaryHoldingFixture::committed(units: ['1080', '1080'], fund: false);
    $businessId = (string) BusinessCampaign::query()->whereKey($publication->id)->value('business_id');
    asUatProfile(strtoupper($businessId));
    expect(app(CampaignFundingEvidence::class)->find($publication->id))->toBeNull();

    expect(Artisan::call('disbursements:reconcile'))->toBe(0)
        ->and(Artisan::output())->toContain('recorded 1 fully committed raises as funded');
    $funding = app(CampaignFundingEvidence::class)->find($publication->id);
    expect($funding)->not->toBeNull()
        ->and($funding['admission']['policy']['evidence']['policy_version'])->toBe(SimulatedCommittedRaiseFunding::POLICY_VERSION)
        ->and(count($funding['commitments']))->toBe(2)
        ->and(Disbursement::query()->where('business_campaign_id', $publication->id)->count())->toBe(1);

    expect(Artisan::call('disbursements:reconcile'))->toBe(0)
        ->and(DB::table('primary_campaign_fundings')->where('business_campaign_id', $publication->id)->count())->toBe(1)
        ->and(Disbursement::query()->where('business_campaign_id', $publication->id)->count())->toBe(1);
});

it('leaves a raise that is not fully committed, or belongs to an unnamed Business, unfunded', function (): void {
    ['campaign' => $partial] = PrimaryHoldingFixture::committed(units: ['1080'], fund: false);
    $businessId = (string) BusinessCampaign::query()->whereKey($partial->id)->value('business_id');
    asUatProfile($businessId);

    expect(app(SimulatedRaiseFunding::class)->fundCommitted(25))->toBe(['funded' => 0, 'waiting' => 1])
        ->and(app(CampaignFundingEvidence::class)->find($partial->id))->toBeNull();

    asUatProfile(strtolower((string) Str::ulid()));
    expect(app(SimulatedRaiseFunding::class)->fundCommitted(25))->toBe(['funded' => 0, 'waiting' => 0]);
});

it('records no funding outside the simulation or with no room to', function (): void {
    ['campaign' => $publication] = PrimaryHoldingFixture::committed(units: ['1080', '1080'], fund: false);
    $businessId = (string) BusinessCampaign::query()->whereKey($publication->id)->value('business_id');
    config(['app.uat_money_simulation' => false, 'app.uat_money_simulation_businesses' => [$businessId]]);

    expect(app(SimulatedRaiseFunding::class)->fundCommitted(25))->toBe(['funded' => 0, 'waiting' => 0]);
    asUatProfile($businessId);
    expect(app(SimulatedRaiseFunding::class)->fundCommitted(0))->toBe(['funded' => 0, 'waiting' => 0])
        ->and(app(CampaignFundingEvidence::class)->find($publication->id))->toBeNull();
});
