<?php

declare(strict_types=1);

use App\Application\Identity\GetIdentityContext;
use App\Domain\Primary\RiskStatements;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/*
 * BRS v3.0 KYC-R05: a verified Investor acknowledges the five risk statements once, with one tick,
 * when the Investor app opens. The shared `risk_acknowledgement` prop carries them on every Investor
 * page until they are acknowledged in their current wording, and checkout asks nothing extra.
 */

it('carries the five statements and the acknowledge command on every Investor page until they are acknowledged', function (): void {
    $investor = InvestorWalletFixture::investor();
    $this->actingAs($investor['user']);
    $revision = (int) app(GetIdentityContext::class)->handle($investor['user']->id)['context_revision'];

    foreach (['investor.portfolio', 'investor.deals', 'investor.wallet'] as $route) {
        $due = $this->get(route($route))->assertOk()->viewData('page')['props']['risk_acknowledgement'];

        expect($due)->toMatchArray(['version' => 'brs-3.0-kyc-r05', 'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => false,
            'identity_context_revision' => $revision, 'action' => ['url' => '/investor/risk-statements/acknowledgements', 'method' => 'post']])
            ->and(array_column($due['statements'], 'text'))->toBe(array_values(RiskStatements::STATEMENTS));
    }

    $this->postJson(route('investor.risks.acknowledge'), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => $revision,
        'version' => RiskStatements::VERSION, 'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true])
        ->assertOk()->assertJsonPath('code', 'RISKS_ACKNOWLEDGED');

    expect($this->get(route('investor.deals'))->assertOk()->viewData('page')['props']['risk_acknowledgement'])->toBeNull();
});

it('asks nothing off the Investor app or before the identity is verified', function (): void {
    $investor = InvestorWalletFixture::investor();
    expect($this->actingAs($investor['user'])->get(route('dashboard'))->assertOk()->viewData('page')['props']['risk_acknowledgement'])->toBeNull();

    $person = User::factory()->create(['party_id' => Party::factory()]);
    expect($this->actingAs($person)->get(route('investor.verification'))->assertOk()->viewData('page')['props']['risk_acknowledgement'])->toBeNull();
});
