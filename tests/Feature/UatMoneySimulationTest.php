<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\SyntheticDisbursementGuard;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\UatMoneySimulation;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Infrastructure\Disbursement\SimulatedFundedCampaigns;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Infrastructure\Disbursement\SyntheticPayoutProvider;
use App\Infrastructure\Disbursement\UnavailableFundedCampaigns;
use App\Infrastructure\Disbursement\UnavailablePayoutProvider;
use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use App\Infrastructure\Secondary\UnavailableSecondaryMarket;
use App\Models\BusinessCampaign;
use App\Models\BusinessFundingMethod;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
    $retained = Mockery::mock(FundedCampaigns::class);
    $closing = new FailedClosing('closing', 'disbursement', 'recheck_failed', ['dscr']);
    $retained->expects('failClose')->with($funded, $closing);

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
