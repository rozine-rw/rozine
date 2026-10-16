<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\BusinessPayoutAccounts;
use App\Domain\Wallet\PayoutAccount;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\PartyNotice;
use Database\Factories\Scenarios\BusinessAuthorityFixture as AuthorityFixture;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Profile > Linked accounts (Business.dc.html L1829–1840): a holder of `business.wallet.withdraw` links
 * and unlinks the Business's own accounts, each verified as held in the business's registered name
 * (BRS v2.0 KYC-R03) and announced to the Business as a security notice (NTF-R01).
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->account = fn (array $fields = []): array => ['request_id' => (string) Str::uuid(), 'type' => 'mobile', 'network' => 'mtn', 'number' => '0781234567', ...$fields];
    $this->business = function (bool $secondOnlyViews = false): array {
        $authority = AuthorityFixture::make('organization', 2, requiredSignatories: 1);
        if ($secondOnlyViews) {
            $viewer = $authority['people'][1]->id;
            $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $viewer
                ? [...$person, 'permissions' => ['business.view']] : $person, $authority['terms']['people']);
        }
        $business = AuthorityFixture::configure($authority)['data']['business']['id'];

        return [$authority, $business, BusinessProfile::query()->whereKey($business)->sole()->entity_party_id];
    };
});

it('links a mobile money number and a bank account in the business\'s name, verified at once, with a security notice', function (): void {
    [$authority, $business, $entity] = ($this->business)();
    $this->actingAs($authority['users'][0])->post(route('business.profile.link', ['business' => $business]), ($this->account)())
        ->assertRedirect(route('business.profile', ['business' => $business, 'section' => 'linked']))->assertSessionHasNoErrors();
    $this->post(route('business.profile.link', ['business' => $business]), ($this->account)(['type' => 'bank', 'network' => null, 'bank' => 'bk', 'number' => '4001234567892231']))
        ->assertSessionHasNoErrors();

    $linked = BusinessFundingMethod::query()->where('business_id', $business)->orderBy('id')->get();
    expect($linked->map(fn (BusinessFundingMethod $method): array => [$method->kind, $method->label, $method->masked, $method->reference,
        $method->verification_source, $method->verified_at !== null, $method->revoked_at])->all())->toBe([
            ['mtn', 'MTN MoMo', '+250 781 ···· 567', '0781234567', 'synthetic', true, null],
            ['bank', 'Bank of Kigali', 'Account ····2231', 'bk:4001234567892231', 'synthetic', true, null],
        ])
        ->and(PartyNotice::query()->where('party_id', $entity)->orderBy('id')->get(['kind', 'facts'])->toArray())->toBe([
            ['kind' => 'security.payout_account_linked', 'facts' => ['account' => 'MTN MoMo +250 781 ···· 567']],
            ['kind' => 'security.payout_account_linked', 'facts' => ['account' => 'Bank of Kigali Account ····2231']],
        ])
        ->and(json_encode(CommandOperation::query()->where('command', 'business.wallet.link_method')->first()?->getAttributes()))->not->toContain('0781234567');

    $this->get(route('business.profile', ['business' => $business, 'section' => 'linked']))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('business/profile')->has('linked.accounts', 2)
            ->where('linked.accounts.0', ['id' => $linked[0]->id, 'kind' => 'mobile_money', 'name' => 'MTN MoMo', 'detail' => '+250 781 ···· 567',
                'unlink' => ['url' => '/business/'.$business.'/profile/linked-accounts/'.$linked[0]->id.'/unlink', 'method' => 'post']])
            ->where('linked.accounts.1.kind', 'bank')
            ->where('linked.add', ['url' => '/business/'.$business.'/profile/linked-accounts', 'method' => 'post'])
            ->has('linked.banks', count(PayoutAccount::BANKS))->where('linked.banks.0', ['code' => 'bk', 'name' => 'Bank of Kigali']));
});

it('answers a repeated request once, and refuses an account already linked or not in the business\'s name', function (): void {
    [$authority, $business, $entity] = ($this->business)();
    $request = ($this->account)();
    $link = fn (array $body) => $this->post(route('business.profile.link', ['business' => $business]), $body);
    $this->actingAs($authority['users'][0]);

    $link($request)->assertSessionHasNoErrors();
    $link($request)->assertSessionHasNoErrors();
    $link(($this->account)())->assertSessionHasErrors(['number' => 'That account is already linked.']);
    $link(($this->account)(['number' => '0788880000']))
        ->assertSessionHasErrors(['number' => 'This account is not in the business\'s registered name. Link an account the business holds.']);
    $link(($this->account)(['network' => 'tigo']))->assertSessionHasErrors(['network' => 'Choose MTN MoMo or Airtel Money.']);
    $link(($this->account)(['number' => '']))->assertSessionHasErrors(['number' => 'Enter the account number.']);
    $link([...$request, 'number' => '0789999999'])->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

    expect(BusinessFundingMethod::query()->where('business_id', $business)->count())->toBe(1)
        ->and(PartyNotice::query()->where('party_id', $entity)->count())->toBe(1);
});

it('unlinks an account one way, drops it from the profile, and keeps it on record', function (): void {
    [$authority, $business, $entity] = ($this->business)();
    $method = BusinessFundingMethod::factory()->create(['business_id' => $business]);
    $elsewhere = AuthorityFixture::make('organization', 1, 'COMPANY-002');
    $other = BusinessFundingMethod::factory()->create(['business_id' => AuthorityFixture::configure($elsewhere)['data']['business']['id']]);
    $unlink = fn (string $id) => $this->post(route('business.profile.unlink', ['business' => $business, 'method' => $id]), ['request_id' => (string) Str::uuid()]);
    $this->actingAs($authority['users'][0]);

    $unlink($method->id)->assertRedirect(route('business.profile', ['business' => $business, 'section' => 'linked']))->assertSessionHasNoErrors();
    expect($method->refresh()->revoked_at)->not->toBeNull()
        ->and(PartyNotice::query()->where('party_id', $entity)->get(['kind', 'facts'])->toArray())
        ->toBe([['kind' => 'security.payout_account_unlinked', 'facts' => ['account' => $method->label.' '.$method->masked]]]);
    $this->get(route('business.profile', ['business' => $business, 'section' => 'linked']))
        ->assertInertia(fn (Assert $page): Assert => $page->has('linked.accounts', 0));

    $unlink($method->id)->assertSessionHasErrors(['number' => 'That account is no longer linked.']);
    $unlink($other->id)->assertSessionHasErrors(['number' => 'That account is no longer linked.']);
    expect($other->refresh()->revoked_at)->toBeNull();
});

it('offers and accepts linking only to a holder of business.wallet.withdraw', function (): void {
    [$authority, $business] = ($this->business)(true);
    $method = BusinessFundingMethod::factory()->create(['business_id' => $business]);

    $this->actingAs($authority['users'][1])->get(route('business.profile', ['business' => $business, 'section' => 'linked']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('linked.add', null)->where('linked.banks', [])->where('linked.accounts.0.unlink', null));
    $this->post(route('business.profile.link', ['business' => $business]), ($this->account)())->assertForbidden()->assertJsonPath('code', 'ACTION_FORBIDDEN');
    $this->post(route('business.profile.unlink', ['business' => $business, 'method' => $method->id]), ['request_id' => (string) Str::uuid()])
        ->assertForbidden()->assertJsonPath('code', 'ACTION_FORBIDDEN');

    expect(BusinessFundingMethod::query()->where('business_id', $business)->count())->toBe(1)
        ->and($method->refresh()->revoked_at)->toBeNull();
});

it('offers no linking where nothing can verify an account or on the test site, and refuses a link sent anyway', function (): void {
    [$authority, $business] = ($this->business)();
    $this->actingAs($authority['users'][0]);
    config(['isolation.live_money_enabled' => true]);

    expect(app(BusinessPayoutAccounts::class)->linkable())->toBeFalse();
    $this->get(route('business.profile', ['business' => $business, 'section' => 'linked']))
        ->assertInertia(fn (Assert $page): Assert => $page->where('linked.add', null)->where('linked.banks', []));
    $this->post(route('business.profile.link', ['business' => $business]), ($this->account)())
        ->assertSessionHasErrors(['number' => 'Accounts cannot be linked yet. Try again later.']);

    config(['isolation.live_money_enabled' => false]);
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);
    $this->post(route('business.profile.link', ['business' => $business]), ($this->account)())
        ->assertSessionHasErrors(['number' => 'Accounts cannot be linked on this test site.']);

    expect(BusinessFundingMethod::query()->where('business_id', $business)->exists())->toBeFalse();
});
