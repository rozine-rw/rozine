<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletStore;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\InvestorVerificationCase;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\StaffAccount;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\InvestorWalletFixture;

/**
 * The live page against a C3 portfolio fixture: the same keys at every level of every object.
 *
 * @param  array<string, mixed>  $live
 * @param  array<string, mixed>  $fixture
 */
function portfolioPageSameShape(array $live, array $fixture, string $path): void
{
    expect(array_keys($live))->toEqualCanonicalizing(array_keys($fixture), "Keys differ at {$path}");
    foreach ($live as $key => $value) {
        $other = $fixture[$key];
        if (is_array($value) && is_array($other) && ! array_is_list($value) && ! array_is_list($other)) {
            portfolioPageSameShape($value, $other, "{$path}.{$key}");
        }
    }
}

/** @return array<string, mixed> */
function portfolioPageFixture(string $name): array
{
    /** @var array{component: string, props: array<string, mixed>} $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path("fixtures/ui/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);
    expect($fixture['component'])->toBe('investor/portfolio');

    return $fixture['props'];
}

beforeEach(function (): void {
    $this->freezeSecond();
});

it('renders the empty portfolio in the live-minimal shape, with real routes and zero totals only', function (): void {
    $investor = InvestorWalletFixture::investor();
    $response = $this->actingAs($investor['user'])->get(route('investor.portfolio'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/portfolio'));
    $props = array_intersect_key($response->viewData('page')['props'], portfolioPageFixture('investor-portfolio-live-minimal'));
    $zero = ['currency' => 'RWF', 'amount' => '0'];

    portfolioPageSameShape($props, portfolioPageFixture('investor-portfolio-live-minimal'), 'props');
    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private')
        ->and($props)->toMatchArray(['contract_version' => 'investor-primary-v1', 'identity_context_revision' => 1, 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'tab' => 'active', 'holdings' => [], 'payouts' => [], 'industries' => [], 'risk' => [], 'concentration' => null,
            'idle' => null, 'commitments' => []])
        ->and($props['totals'])->toBe(['businesses' => 0, 'value' => $zero, 'invested' => $zero, 'gain' => $zero, 'this_month' => $zero,
            'projected_3m' => $zero, 'next_payout' => null, 'avg_monthly' => $zero])
        ->and($props['tabs'])->toBe([
            ['key' => 'active', 'active' => true, 'link' => ['url' => '/investor/portfolio?tab=active', 'method' => 'get']],
            ['key' => 'matured', 'active' => false, 'link' => ['url' => '/investor/portfolio?tab=matured', 'method' => 'get']],
            ['key' => 'secondary', 'active' => false, 'link' => ['url' => '/investor/portfolio?tab=secondary', 'method' => 'get']],
            ['key' => 'saved', 'active' => false, 'link' => ['url' => '/investor/portfolio?tab=saved', 'method' => 'get']]])
        ->and($props['links'])->toBe(['deals' => ['url' => '/investor/deals', 'method' => 'get'], 'portfolio' => ['url' => '/investor/portfolio', 'method' => 'get'],
            'market' => ['url' => '/investor/market', 'method' => 'get'], 'cart' => ['url' => '/investor/cart', 'method' => 'get'],
            'profile' => ['url' => '/investor/profile', 'method' => 'get'], 'wallet' => ['url' => '/investor/wallet', 'method' => 'get'],
            'notifications' => null, 'launcher' => ['url' => '/dashboard', 'method' => 'get']])
        ->and(json_encode($props, JSON_THROW_ON_ERROR))->not->toContain('/preview/');
});

it('shows the wallet’s credited cash as idle and opens the matured tab', function (): void {
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle((string) InvestorWalletFixture::deposit($fixture, '75000')['data']['intent_id']);

    $this->actingAs($fixture['user'])->get(route('investor.portfolio', ['tab' => 'matured', 'identity_context_revision' => 1]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/portfolio')->where('tab', 'matured')
            ->where('idle', ['currency' => 'RWF', 'amount' => '75000'])->where('holdings', [])
            ->where('tabs.0.active', false)->where('tabs.1.active', true));
    $this->get(route('investor.portfolio', ['tab' => 'resale']))->assertSessionHasErrors('tab');
});

it('opens the design’s Secondary and Saved tabs empty, since neither has a read yet', function (string $tab, int $index): void {
    $fixture = InvestorWalletFixture::ready();

    $this->actingAs($fixture['user'])->get(route('investor.portfolio', ['tab' => $tab]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/portfolio')->where('tab', $tab)
            ->where('holdings', [])->where('commitments', [])->where('tabs.0.active', false)->where("tabs.{$index}.active", true)
            ->where("tabs.{$index}.key", $tab));
})->with([['secondary', 2], ['saved', 3]]);

it('shows a person still being verified an empty portfolio with no wallet', function (): void {
    $person = User::factory()->create(['party_id' => Party::factory()]);
    (new InvestorVerification)->forceFill(['party_id' => $person->party_id, 'revision' => 1, 'status' => 'submitted',
        'state' => app(InvestorVerificationCase::class)->empty(), 'submitted_at' => now()])->save();

    $this->actingAs($person)->get(route('investor.portfolio'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/portfolio')->where('idle', null)->where('holdings', [])
            ->where('identity_context_revision', $person->context_revision)->where('links.wallet', null)
            ->where('links.profile', ['url' => '/investor/profile', 'method' => 'get']));
});

it('keeps the portfolio from anyone without current Investor authority', function (): void {
    $this->actingAs(User::factory()->create())->get(route('investor.portfolio'))->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', 'IDENTITY_NOT_LINKED'));

    $staff = User::factory()->withTwoFactor()->create();
    StaffAccount::factory()->create(['user_id' => $staff->id]);
    $this->actingAs($staff)->getJson(route('investor.portfolio'))->assertForbidden()->assertJsonPath('code', 'IDENTITY_NOT_LINKED');

    $verified = User::factory()->create(['party_id' => Party::factory()->verified()]);
    $this->actingAs($verified)->getJson(route('investor.portfolio'))->assertForbidden()
        ->assertJsonPath('code', fn (string $code): bool => $code !== 'IDENTITY_VERIFICATION_REQUIRED');

    $investor = InvestorWalletFixture::investor();
    $this->actingAs($investor['user'])->getJson(route('investor.portfolio', ['identity_context_revision' => 0]))->assertStatus(409)
        ->assertJsonPath('code', 'ACTIVE_ROLE_REVISION_CONFLICT');

    auth()->logout();
    $this->get(route('investor.portfolio'))->assertRedirect(route('login'));
});

it('lets a verification refusal stand once the identity it names is already verified', function (): void {
    $investor = InvestorWalletFixture::investor();
    $this->mock(WalletStore::class, function ($mock): void {
        $mock->shouldReceive('page')->andThrow(new IdentityViolation('IDENTITY_VERIFICATION_REQUIRED'));
    });

    $this->actingAs($investor['user'])->getJson(route('investor.portfolio'))->assertForbidden()->assertJsonPath('code', 'IDENTITY_VERIFICATION_REQUIRED');
});
