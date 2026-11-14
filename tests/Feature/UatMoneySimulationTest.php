<?php

declare(strict_types=1);

use App\Application\Business\Contracts\ServicingPayouts;
use App\Application\Business\Contracts\ServicingStatements;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\SyntheticDisbursementGuard;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\UatMoneySimulation;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Secondary\Contracts\NoteUnitHolders;
use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Infrastructure\Disbursement\SimulatedFundedCampaigns;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Infrastructure\Disbursement\SyntheticPayoutProvider;
use App\Infrastructure\Disbursement\UnavailableFundedCampaigns;
use App\Infrastructure\Disbursement\UnavailablePayoutProvider;
use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use App\Infrastructure\Secondary\UnavailableSecondaryMarket;
use App\Infrastructure\Wallet\SimulatedNoteServicing;
use App\Infrastructure\Wallet\SimulatedServicingPayouts;
use App\Infrastructure\Wallet\UnavailableServicingPayouts;
use App\Models\BusinessCampaign;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessMandate;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryHoldingFixture;

/*
 * Staging's simulated money after funding (Erastus, 14 Nov 2026): on UAT with live money off, a raise
 * that reaches 100% issues its notes and pays the Business's own linked account, and resale opens.
 * Nothing of it reaches demo or production.
 */

function asUatProfile(): void
{
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);
}

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
    ['uat', true, SimulatedFundedCampaigns::class, SyntheticPayoutProvider::class, EloquentSecondaryMarket::class],
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
    asUatProfile();

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
    asUatProfile();
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

    // Paid on once: the sweep finds nothing more, and a stale quote is refused.
    expect(Artisan::call('servicing:payouts'))->toBe(0)->and(Artisan::output())->toContain('Paid on 0 repayments')
        ->and(DB::table('servicing_payouts')->count())->toBe(2);
    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $first->gross]])
        ->assertStatus(409);
    $repaymentId = (string) DB::table('servicing_payouts')->value('repayment_id');
    expect(app(SimulatedServicingPayouts::class)->payOut($repaymentId))->toBe(0)
        ->and(fn () => DB::transaction(fn (): int => app(SimulatedNoteServicing::class)->applyRepayment(strtolower($note['campaign']), $repaymentId,
            'next_instalment', WalletMoney::of($first->gross), 2)))->toThrow(CommandRejection::class, 'VERSION_CONFLICT');
});

it('keeps a repayment whose payout fails, and the sweep reports it as failed until it can be paid', function (): void {
    $note = simulatedIssuedNote();
    $schedule = app(RetainedNoteSchedules::class)->find($note['campaign']);
    $holders = $this->createStub(NoteUnitHolders::class);
    $holders->method('lots')->willReturn([['lot_type' => 'primary_holding', 'lot_id' => $schedule->holdings[0]->holdingId,
        'party_id' => $schedule->holdings[0]->partyId, 'units' => '1', 'ordinals' => [['first' => '999999999', 'last' => '999999999']]]]);
    app()->instance(NoteUnitHolders::class, $holders);

    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $schedule->instalments[0]->gross]])->assertOk();
    expect(DB::table('business_repayments')->count())->toBe(1)->and(DB::table('servicing_payouts')->count())->toBe(0)
        ->and(Artisan::call('servicing:payouts'))->toBe(0)->and(Artisan::output())->toContain('Paid on 0 repayments to 0 lots; 1 failed.');
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

it('pays a sold unit\'s share to its buyer, and the sweep pays on a repayment whose payout did not run', function (): void {
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
    app()->instance(ServicingPayouts::class, new UnavailableServicingPayouts);

    $this->actingAs($note['payer'])->postJson(route('business.repayments.pay', $note['business']),
        ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note['campaign'], 'option' => 'next_instalment',
            'expected_servicing_revision' => 1, 'quoted_total' => ['currency' => 'RWF', 'amount' => $schedule->instalments[0]->gross]])->assertOk();
    expect(DB::table('servicing_payouts')->count())->toBe(0);

    app()->forgetInstance(ServicingPayouts::class);
    expect(Artisan::call('servicing:payouts'))->toBe(0)->and(Artisan::output())->toContain('Paid on 1 repayments to 3 lots; 0 failed.');
    $bought = DB::table('servicing_payouts')->where('party_id', $buyer)->sole();
    expect($bought->lot_type)->toBe('secondary_transfer')->and((int) $bought->units)->toBe(80)
        ->and($bought->rate_bps)->toBe($seller->terms['earnings_fee']['rate_bps'])
        ->and((string) DB::table('servicing_payouts')->sum('principal'))->toBe($schedule->instalments[0]->principal);
});

it('refuses simulated servicing and payouts outside the simulation, and finds nothing to pay on where none runs', function (): void {
    expect(fn () => app(SimulatedNoteServicing::class)->current('x'))->toThrow(LogicException::class, 'UAT_SIMULATION_ONLY')
        ->and(fn () => app(SimulatedServicingPayouts::class)->payOut('x'))->toThrow(LogicException::class, 'UAT_SIMULATION_ONLY')
        ->and(fn () => app(SimulatedServicingPayouts::class)->unpaid(5))->toThrow(LogicException::class, 'UAT_SIMULATION_ONLY')
        ->and(app(ServicingStatements::class)->current('x'))->toBeNull()
        ->and(app(ServicingPayouts::class)->payOut('x'))->toBe(0);
    expect(Artisan::call('servicing:payouts'))->toBe(0)->and(Artisan::output())->toContain('Paid on 0 repayments to 0 lots; 0 failed.')
        ->and(Artisan::call('servicing:payouts', ['--limit' => '0']))->toBe(2);

    asUatProfile();
    expect(app(SimulatedServicingPayouts::class)->payOut(strtolower((string) Str::ulid())))->toBe(0)
        ->and(app(SimulatedNoteServicing::class)->lockForPayment(strtolower((string) Str::ulid()), strtolower((string) Str::ulid())))->toBeNull();
});
