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

/** The approved submission staff decided on, holding the identity document it was decided on. */
function profilePageApproved(Party $party, string $type, string $number, string $status = 'approved'): void
{
    (new InvestorVerification)->forceFill(['party_id' => $party->id, 'revision' => 3, 'status' => $status, 'submitted_at' => $status === 'draft' ? null : now(),
        'state' => [...app(InvestorVerificationCase::class)->empty(), 'step' => 'liveness', 'date_of_birth' => '1990-08-01', 'id_type' => $type, 'id_number' => $number]])->save();
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
        ->and($props['personal'])->toBe(['name' => $user->name, 'email' => $user->email, 'phone' => null, 'id_type' => null, 'id_number' => null, 'address' => null])
        ->and($props['security'])->toBe(['two_factor' => false])
        ->and($props['linked'])->toBe(['accounts' => [['id' => $fixture['method']->id, 'kind' => 'mtn', 'label' => 'MTN MoMo', 'masked' => '+250 788 ···· 456',
            'verified' => true, 'unlink' => null]], 'banks' => []])
        ->and($props['statements'])->toBe(['annual' => null, 'monthly' => []])
        ->and($props['links'])->toBe(['deals' => ['url' => '/investor/deals', 'method' => 'get'], 'portfolio' => ['url' => '/investor/portfolio', 'method' => 'get'],
            'market' => ['url' => '/investor/market', 'method' => 'get'], 'cart' => ['url' => '/investor/cart', 'method' => 'get'], 'profile' => $profile(), 'wallet' => ['url' => '/investor/wallet', 'method' => 'get'], 'notifications' => null,
            'launcher' => ['url' => '/dashboard', 'method' => 'get'], 'overview' => $profile(), 'personal' => $profile('?section=personal'),
            'plan' => $profile('?section=plan'), 'security' => $profile('?section=security'), 'linked' => $profile('?section=linked'),
            'statements' => $profile('?section=statements'), 'help' => $profile('?section=help'), 'terms' => $profile('?section=terms'),
            'privacy' => $profile('?section=privacy'), 'automation' => $profile('?section=automation'),
            'verification' => ['url' => '/investor/verified', 'method' => 'get'], 'security_settings' => ['url' => '/settings/security', 'method' => 'get']])
        ->and($props['actions'])->toBe(['link_account' => null, 'logout' => ['url' => '/logout', 'method' => 'post']])
        ->and(array_keys($props['links']))->toEqualCanonicalizing(array_keys(profilePageFixture('investor-profile-automation')['links']));

    foreach (['personal', 'plan', 'security', 'statements', 'help', 'terms', 'privacy'] as $section) {
        $this->get(route('investor.profile', ['section' => $section, 'identity_context_revision' => 1]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('section', $section));
    }
    $this->get(route('investor.profile', ['section' => 'refer']))->assertSessionHasErrors('section');
});

it('shows the identity document staff approved with all but its last four characters hidden', function (string $type, string $number, string $masked): void {
    $fixture = InvestorWalletFixture::investor();
    profilePageApproved($fixture['party'], $type, $number);

    $response = $this->actingAs($fixture['user'])->get(route('investor.profile', ['section' => 'personal']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('personal.id_type', $type)->where('personal.id_number', $masked));

    expect(json_encode($response->viewData('page'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))->not->toContain($number)
        ->not->toContain(substr($number, 0, 8));
})->with([
    'national ID' => ['national_id', '1199080012345678', '•••• •••• •••• 5678'],
    'driving licence' => ['drivers_license', '1199070098765432', '•••• •••• •••• 5432'],
    'passport' => ['passport', 'PC1234567', '•••••4567'],
]);

it('shows no identity document until staff approve one', function (string $status): void {
    $person = profilePagePerson(null);
    profilePageApproved($person->party, 'national_id', '1199080012345678', $status);

    $this->actingAs($person)->get(route('investor.profile', ['section' => 'personal']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('personal.id_type', null)->where('personal.id_number', null));
})->with(['draft', 'submitted', 'rejected']);

it('reports whether two-factor sign-in is on', function (): void {
    $fixture = InvestorWalletFixture::investor();
    $fixture['user']->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();

    $this->actingAs($fixture['user']->refresh())->get(route('investor.profile', ['section' => 'security']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('security', ['two_factor' => true])
            ->where('links.security_settings', ['url' => '/settings/security', 'method' => 'get']));

    $fixture['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    $this->get(route('investor.profile', ['section' => 'security']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('security', ['two_factor' => false]));
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
