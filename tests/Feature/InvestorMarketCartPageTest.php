<?php

declare(strict_types=1);

use App\Domain\Identity\InvestorVerificationCase;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;

/*
 * Market and Cart are on every Investor page's tabs. Neither has a read or command yet, so each
 * page carries the app frame's links only, under current Investor authority.
 */

beforeEach(function (): void {
    $this->withoutVite();
});

dataset('market and cart', [
    'market' => ['investor.market', 'investor/market'],
    'cart' => ['investor.cart', 'investor/cart'],
]);

it('sends a guest to sign in', function (string $route): void {
    $this->get(route($route))->assertRedirect(route('login'));
})->with('market and cart');

it('opens on the app frame links for a verified Investor, wallet included', function (string $route, string $component): void {
    $user = InvestorWalletFixture::ready()['user'];
    $link = fn (string $url): array => ['url' => $url, 'method' => 'get'];

    $response = $this->actingAs($user)->get(route($route))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component($component));
    $props = $response->viewData('page')['props'];

    expect($props['links'])->toBe(['deals' => $link('/investor/deals'), 'portfolio' => $link('/investor/portfolio'), 'market' => $link('/investor/market'),
        'cart' => $link('/investor/cart'), 'profile' => $link('/investor/profile'), 'notifications' => null, 'launcher' => $link('/dashboard'),
        'wallet' => $link('/investor/wallet')])
        ->and($props['identity_context_revision'])->toBe($user->refresh()->context_revision)
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
})->with('market and cart');

it('opens without a wallet while the identity is still being verified', function (string $route, string $component, ?string $status): void {
    $person = User::factory()->create(['party_id' => Party::factory()]);
    if ($status !== null) {
        (new InvestorVerification)->forceFill(['party_id' => $person->party_id, 'revision' => 1, 'status' => $status,
            'state' => app(InvestorVerificationCase::class)->empty(), 'submitted_at' => $status === 'draft' ? null : now()])->save();
    }

    $props = $this->actingAs($person)->get(route($route))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component($component))->viewData('page')['props'];

    expect($props['links']['wallet'])->toBeNull()->and($props['links']['market'])->toBe(['url' => '/investor/market', 'method' => 'get']);
})->with('market and cart')->with(['never submitted' => [null], 'submitted' => ['submitted']]);

it('refuses a stale identity context', function (string $route): void {
    $user = InvestorWalletFixture::ready()['user'];

    $this->actingAs($user)->getJson(route($route, ['identity_context_revision' => -1]))->assertUnprocessable()
        ->assertJsonValidationErrors('identity_context_revision');
    $this->actingAs($user)->get(route($route, ['identity_context_revision' => $user->refresh()->context_revision + 5]))->assertStatus(409);
})->with('market and cart');

it('links Market and Cart from every web Investor page and leaves them off the bearer transport', function (): void {
    $user = InvestorWalletFixture::ready()['user'];
    $market = ['url' => '/investor/market', 'method' => 'get'];
    $cart = ['url' => '/investor/cart', 'method' => 'get'];

    foreach (['investor.deals', 'investor.portfolio', 'investor.profile', 'investor.wallet'] as $route) {
        $links = $this->actingAs($user)->get(route($route))->assertOk()->viewData('page')['props']['links'];

        expect($links['market'])->toBe($market, $route)->and($links['cart'])->toBe($cart, $route);
    }

    Sanctum::actingAs($user, ['investor:read']);
    foreach (['api.v1.investor.deals', 'api.v1.investor.wallet'] as $route) {
        $this->getJson(route($route))->assertOk()->assertJsonPath('data.links.market', null)->assertJsonPath('data.links.cart', null);
    }
});
