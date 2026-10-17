<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\PayoutAccounts;
use App\Domain\Wallet\PayoutAccount;
use App\Infrastructure\Wallet\UnavailablePayoutAccountVerifier;
use App\Models\InvestorFundingMethod;
use App\Models\Party;
use App\Models\PartyNotice;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\InvestorWalletFixture;

/**
 * @param  array<string, string|null>  $fields
 * @return array<string, string|null>
 */
function payoutLink(array $fields = []): array
{
    return ['request_id' => (string) Str::uuid(), 'type' => 'mobile', 'network' => 'airtel', 'number' => '0731234567', ...$fields];
}

it('links a mobile money number and a bank account in the Investor\'s name, verified at once, with a security notice', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $this->actingAs($fixture['user'])->post(route('investor.profile.link'), payoutLink())
        ->assertRedirect(route('investor.profile', ['section' => 'linked']))->assertSessionHasNoErrors();
    $this->post(route('investor.profile.link'), payoutLink(['type' => 'bank', 'network' => null, 'bank' => 'equity', 'number' => '4001234567891234']))
        ->assertRedirect(route('investor.profile', ['section' => 'linked']))->assertSessionHasNoErrors();

    $linked = InvestorFundingMethod::query()->where('party_id', $fixture['party']->id)->whereKeyNot($fixture['method']->id)->orderBy('id')->get();

    expect($linked->map(fn (InvestorFundingMethod $method): array => [$method->kind, $method->label, $method->masked, $method->reference,
        $method->verification_source, $method->verified_at?->equalTo($method->created_at), $method->revoked_at])->all())->toBe([
            ['airtel', 'Airtel Money', '+250 731 ···· 567', '0731234567', 'synthetic', true, null],
            ['bank', 'Equity Bank Rwanda', 'Account ····1234', 'equity:4001234567891234', 'synthetic', true, null],
        ])
        ->and(PartyNotice::query()->where('party_id', $fixture['party']->id)->orderBy('id')->get(['kind', 'facts'])->toArray())->toBe([
            ['kind' => 'security.payout_account_linked', 'facts' => ['account' => 'Airtel Money +250 731 ···· 567']],
            ['kind' => 'security.payout_account_linked', 'facts' => ['account' => 'Equity Bank Rwanda Account ····1234']],
        ]);

    $this->get(route('investor.profile', ['section' => 'linked']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('linked.accounts', 3)
            ->where('linked.accounts.1.label', 'Airtel Money')
            ->where('linked.accounts.1.unlink', ['url' => '/investor/profile/linked-accounts/'.$linked[0]->id.'/unlink', 'method' => 'post'])
            ->where('linked.banks.0', ['code' => 'bk', 'name' => 'Bank of Kigali'])
            ->has('linked.banks', count(PayoutAccount::BANKS))
            ->where('actions.link_account', ['url' => '/investor/profile/linked-accounts', 'method' => 'post']));
});

it('answers a repeated request once, and refuses an account already linked', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $request = payoutLink();
    $this->actingAs($fixture['user'])->post(route('investor.profile.link'), $request)->assertSessionHasNoErrors();
    $this->post(route('investor.profile.link'), $request)->assertSessionHasNoErrors();
    $this->post(route('investor.profile.link'), payoutLink())->assertSessionHasErrors(['number' => 'That account is already linked.']);
    $this->post(route('investor.profile.link'), [...$request, 'number' => '0739999999'])->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

    expect(InvestorFundingMethod::query()->where('party_id', $fixture['party']->id)->count())->toBe(2)
        ->and(PartyNotice::query()->where('party_id', $fixture['party']->id)->count())->toBe(1);
});

it('names what is wrong with each field, and refuses an account not in the Investor\'s verified name', function (array $fields, string $field, string $message): void {
    $fixture = InvestorWalletFixture::ready();
    $this->actingAs($fixture['user'])->post(route('investor.profile.link'), payoutLink($fields))->assertSessionHasErrors([$field => $message]);

    expect(InvestorFundingMethod::query()->where('party_id', $fixture['party']->id)->count())->toBe(1)
        ->and(PartyNotice::query()->where('party_id', $fixture['party']->id)->exists())->toBeFalse();
})->with([
    'no network' => [['network' => 'tigo'], 'network', 'Choose MTN MoMo or Airtel Money.'],
    'unknown type' => [['type' => 'card'], 'type', 'The selected type is invalid.'],
    'short mobile number' => [['number' => '07312345'], 'number', 'Enter a full 10-digit mobile number starting with 07.'],
    'foreign mobile number' => [['number' => '0831234567'], 'number', 'Enter a full 10-digit mobile number starting with 07.'],
    'no number' => [['number' => ''], 'number', 'Enter the account number.'],
    'unknown bank' => [['type' => 'bank', 'bank' => 'xyz', 'number' => '40012345'], 'bank', 'Choose your bank.'],
    'short bank number' => [['type' => 'bank', 'bank' => 'bk', 'number' => '4001'], 'number', 'Enter 8 to 20 digits of the account number.'],
    'not their name' => [['number' => '0788880000'], 'number', 'This account is not in your verified name. Link an account you hold yourself.'],
]);

it('unlinks an account one way, drops it from the profile and wallet, and keeps it on record', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $method = $fixture['method'];
    $unlink = fn (string $id) => $this->post(route('investor.profile.unlink', ['method' => $id]), ['request_id' => (string) Str::uuid()]);
    $this->actingAs($fixture['user']);
    $unlink($method->id)->assertRedirect(route('investor.profile', ['section' => 'linked']))->assertSessionHasNoErrors();

    expect($method->refresh()->revoked_at)->not->toBeNull()
        ->and(PartyNotice::query()->where('party_id', $fixture['party']->id)->get(['kind', 'facts'])->toArray())
        ->toBe([['kind' => 'security.payout_account_unlinked', 'facts' => ['account' => 'MTN MoMo +250 788 ···· 456']]]);
    $this->get(route('investor.profile', ['section' => 'linked']))->assertInertia(fn (Assert $page) => $page->has('linked.accounts', 0));
    $this->get(route('investor.wallet'))->assertInertia(fn (Assert $page) => $page->has('funding.methods', 0));

    $unlink($method->id)->assertSessionHasErrors(['number' => 'That account is no longer linked.']);
    $other = InvestorFundingMethod::factory()->create(['party_id' => Party::factory()->verified()]);
    $unlink($other->id)->assertSessionHasErrors(['number' => 'That account is no longer linked.']);
    expect($other->refresh()->revoked_at)->toBeNull();
});

it('lets only a named wallet tester link an account on the test site', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);

    $this->actingAs($fixture['user'])->post(route('investor.profile.link'), payoutLink())
        ->assertSessionHasErrors(['number' => 'Only approved testers can link accounts on this test site.']);
    expect(InvestorFundingMethod::query()->where('party_id', $fixture['party']->id)->count())->toBe(1);
});

it('offers no linking where nothing can verify an account, and refuses a link sent anyway', function (): void {
    config(['isolation.live_money_enabled' => true]);
    $fixture = InvestorWalletFixture::ready();

    expect(app(PayoutAccounts::class)->linkable())->toBeFalse();
    $this->actingAs($fixture['user'])->get(route('investor.profile', ['section' => 'linked']))
        ->assertInertia(fn (Assert $page) => $page->where('actions.link_account', null)->where('linked.banks', [])
            ->where('linked.accounts.0.unlink', null));
    $this->post(route('investor.profile.link'), payoutLink())->assertSessionHasErrors(['number' => 'Accounts cannot be linked yet. Try again later.']);
    $account = PayoutAccount::fromInput(payoutLink());
    expect($account)->toBeInstanceOf(PayoutAccount::class);
    if ($account instanceof PayoutAccount) {
        expect(fn () => app(UnavailablePayoutAccountVerifier::class)->refusal($account, 'Ada'))->toThrow(LogicException::class);
    }
});

it('offers no linking to a person not yet verified, and refuses a link sent anyway', function (): void {
    $person = User::factory()->create(['party_id' => Party::factory()]);
    $this->actingAs($person)->post(route('investor.profile.link'), payoutLink())->assertForbidden()->assertJsonPath('code', 'IDENTITY_VERIFICATION_REQUIRED');
    $this->get(route('investor.profile', ['section' => 'linked']))->assertInertia(fn (Assert $page) => $page->where('actions.link_account', null)->where('linked.banks', []));
    expect(InvestorFundingMethod::query()->where('party_id', $person->party_id)->exists())->toBeFalse();
});
