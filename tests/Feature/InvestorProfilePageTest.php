<?php

declare(strict_types=1);

use App\Domain\Identity\InvestorVerificationCase;
use App\Models\InvestorFundingMethod;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\StaffAccount;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/** @return array<string, mixed> */
function profilePageFixture(string $name): array
{
    /** @var array{component: string, props: array<string, mixed>} $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path("fixtures/ui/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);

    return $fixture['props'];
}

/** A person whose identity is not yet verified, with their submission at `$status` when there is one. */
function profilePagePerson(?string $status): User
{
    $person = User::factory()->create(['party_id' => Party::factory()]);
    if ($status !== null) {
        (new InvestorVerification)->forceFill(['party_id' => $person->party_id, 'revision' => 1, 'status' => $status,
            'state' => app(InvestorVerificationCase::class)->empty(), 'submitted_at' => $status === 'draft' ? null : now()])->save();
    }

    return $person;
}

it('shows the account, its verified state and its verified funding methods, offering no command that does not exist', function (): void {
    $fixture = InvestorWalletFixture::ready();
    InvestorFundingMethod::factory()->unverified()->create(['party_id' => $fixture['party']->id]);
    $user = $fixture['user'];
    $response = $this->actingAs($user)->get(route('investor.profile'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/profile'));
    $props = array_intersect_key($response->viewData('page')['props'], profilePageFixture('investor-profile'));
    $profile = fn (string $query = ''): array => ['url' => '/investor/profile'.$query, 'method' => 'get'];

    expect(array_keys($props))->toEqualCanonicalizing(array_keys(profilePageFixture('investor-profile')))
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private')
        ->and($props['section'])->toBe('overview')
        ->and($props['identity'])->toBe(['name' => $user->name, 'email' => $user->email, 'investor_type' => 'individual', 'kyc' => 'verified',
            'member_since' => $user->created_at->toIso8601String()])
        ->and($props['linked'])->toBe(['accounts' => [['id' => $fixture['method']->id, 'kind' => 'mtn', 'label' => 'MTN MoMo', 'masked' => '+250 788 ···· 456',
            'verified' => true, 'unlink' => null]], 'banks' => []])
        ->and($props['statements'])->toBe(['annual' => null, 'monthly' => []])
        ->and($props['links'])->toBe(['deals' => ['url' => '/investor/deals', 'method' => 'get'], 'portfolio' => ['url' => '/investor/portfolio', 'method' => 'get'],
            'profile' => $profile(), 'wallet' => ['url' => '/investor/wallet', 'method' => 'get'], 'notifications' => null,
            'launcher' => ['url' => '/dashboard', 'method' => 'get'], 'overview' => $profile(), 'linked' => $profile('?section=linked'),
            'statements' => $profile('?section=statements'), 'automation' => $profile('?section=automation'),
            'verification' => ['url' => '/investor/verified', 'method' => 'get'], 'terms' => null, 'privacy' => null])
        ->and($props['actions'])->toBe(['link_account' => null, 'logout' => ['url' => '/logout', 'method' => 'post']])
        ->and(array_keys($props['links']))->toEqualCanonicalizing(array_keys(profilePageFixture('investor-profile-automation')['links']));

    $this->get(route('investor.profile', ['section' => 'statements', 'identity_context_revision' => 1]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('section', 'statements'));
    $this->get(route('investor.profile', ['section' => 'security']))->assertSessionHasErrors('section');
});

it('shows a person still being verified where their verification stands, with no wallet', function (?string $status, string $kyc): void {
    $person = profilePagePerson($status);

    $this->actingAs($person)->get(route('investor.profile', ['section' => 'linked']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/profile')->where('section', 'linked')->where('identity.kyc', $kyc)
            ->where('identity.name', $person->name)->where('linked', ['accounts' => [], 'banks' => []])->where('links.wallet', null)
            ->where('links.verification', ['url' => '/investor/verification', 'method' => 'get']));
})->with([
    'not started' => [null, 'unverified'],
    'rejected' => ['rejected', 'unverified'],
    'with Compliance' => ['submitted', 'pending'],
]);

it('keeps the profile from anyone without current Investor authority', function (): void {
    $this->actingAs(User::factory()->create())->get(route('investor.profile'))->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', 'IDENTITY_NOT_LINKED'));

    $staff = User::factory()->withTwoFactor()->create();
    StaffAccount::factory()->create(['user_id' => $staff->id]);
    $this->actingAs($staff)->getJson(route('investor.profile'))->assertForbidden()->assertJsonPath('code', 'IDENTITY_NOT_LINKED');

    auth()->logout();
    $this->get(route('investor.profile'))->assertRedirect(route('login'));
});

it('tells a verified Investor so, with their available cash and the open deals', function (): void {
    $investor = InvestorWalletFixture::investor();
    $this->actingAs($investor['user'])->get(route('investor.verified'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/verified')->where('wallet', ['available' => ['currency' => 'RWF', 'amount' => '0']])
            ->where('open_deals', 0)->where('links', ['deals' => ['url' => '/investor/deals', 'method' => 'get']]));

    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $this->get(route('investor.verified'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('open_deals', 1));

    $this->travelTo($campaign->expires_at);
    $this->get(route('investor.verified'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('open_deals', 0));
});

it('sends a person still being verified back to their verification, and a guest to sign in', function (): void {
    $this->actingAs(profilePagePerson('draft'))->get(route('investor.verified'))->assertRedirect(route('investor.verification'));

    auth()->logout();
    $this->get(route('investor.verified'))->assertRedirect(route('login'));
});
