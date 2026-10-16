<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\EnterSoleApp;
use App\Application\Identity\GetIdentityContext;
use App\Domain\Identity\IdentityViolation;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\Expectation;
use Tests\Support\InvestorWalletFixture;

beforeEach(function (): void {
    $this->withoutVite();
});

/** A verified person holding active memberships in `$roles`, with no app selected yet. */
function soleAppPerson(string ...$roles): User
{
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    foreach ($roles as $role) {
        RoleMembership::factory()->for($party)->active()->create(['role' => $role]);
    }

    return $user;
}

/** What identity verification does to a signed-in Investor: the active role is cleared and the context moves on. */
function soleAppResetRole(User $user): void
{
    $user->forceFill(['active_membership_id' => null, 'active_membership_revision' => null, 'context_revision' => $user->context_revision + 1])->save();
}

function soleAppActiveRole(User $user): ?string
{
    return app(GetIdentityContext::class)->handle($user->id)['active_role'];
}

it('lands sign-in on the entry step, which opens a single app where the launcher would', function (): void {
    $user = soleAppPerson('investor');
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('enter', absolute: false));
    $this->get(route('enter'))->assertRedirect(route('identity.roles.resume', ['role' => 'investor']));
    expect(soleAppActiveRole($user))->toBe('investor');
    $this->get(route('enter'))->assertRedirect(route('identity.roles.resume', ['role' => 'investor']));
    expect($user->fresh()?->context_revision)->toBe(1);
});

it('keeps the launcher for two apps, for none yet, and whenever it is opened on purpose', function (): void {
    $two = soleAppPerson('investor', 'business');
    $this->actingAs($two)->get(route('enter', ['verified' => 1]))->assertRedirect(route('dashboard', ['verified' => 1]));
    expect(soleAppActiveRole($two))->toBeNull();
    $none = User::factory()->for(Party::factory()->verified())->create();
    $this->actingAs($none)->get(route('enter'))->assertRedirect(route('dashboard'));
    $one = soleAppPerson('investor');
    $this->actingAs($one)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('dashboard'));
    expect(soleAppActiveRole($one))->toBeNull();
});

it('leaves an Auditor without confirmed two-factor sign-in to the launcher, which explains why', function (): void {
    $auditor = soleAppPerson('auditor');
    $this->actingAs($auditor)->get(route('enter'))->assertRedirect(route('dashboard'));
    expect(soleAppActiveRole($auditor))->toBeNull();
});

it('opens Admin for an enabled staff account and the launcher once access is withdrawn', function (): void {
    $staff = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Compliance review.', (string) Str::uuid(), ['compliance']);
    $this->actingAs($staff)->get(route('enter'))->assertRedirect(route('staff.dashboard'));
    app(ConfigureStaffAccess::class)->handle($staff->id, false, 'Rotation ended.', (string) Str::uuid(), ['compliance']);
    $this->actingAs($staff->fresh())->get(route('enter'))->assertRedirect(route('dashboard'));
});

it('reopens the only app on the page asked for after verification resets the active role', function (): void {
    $fixture = InvestorWalletFixture::investor();
    soleAppResetRole($fixture['user']);
    $url = route('investor.profile', ['section' => 'personal']);
    $this->actingAs($fixture['user']->fresh())->get($url)->assertRedirect($url);
    expect(soleAppActiveRole($fixture['user']))->toBe('investor');
    $this->get($url)->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('investor/profile')
        ->where('personal.country', null)->where('personal.address', null));
});

it('still refuses when the person has two apps, and keeps JSON refusals unchanged', function (): void {
    $two = soleAppPerson('investor', 'business');
    $this->actingAs($two)->get(route('investor.profile'))->assertForbidden()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied', false)->where('code', 'ACTIVE_ROLE_REQUIRED'));
    $fixture = InvestorWalletFixture::investor();
    soleAppResetRole($fixture['user']);
    $this->actingAs($fixture['user']->fresh())->getJson(route('investor.profile'))->assertForbidden()->assertJsonPath('code', 'ACTIVE_ROLE_REQUIRED');
    expect(soleAppActiveRole($two))->toBeNull()->and(soleAppActiveRole($fixture['user']))->toBeNull();
});

it('selects only the account\'s own sole app and never twice in a row', function (): void {
    $one = soleAppPerson('investor');
    $enter = app(EnterSoleApp::class);
    expect($enter->selectSole($one->id, 'business'))->toBeFalse()
        ->and($enter->selectSole($one->id, 'investor'))->toBeTrue()
        ->and($enter->selectSole($one->id, 'investor'))->toBeFalse()
        ->and(soleAppActiveRole($one))->toBe('investor');
});

it('leaves the launcher to explain a selection refused mid-flight, as when another tab moved the context on', function (): void {
    $one = soleAppPerson('investor');
    $real = app(IdentityAccessStore::class);
    $store = Mockery::mock(IdentityAccessStore::class);
    foreach ((new ReflectionClass(IdentityAccessStore::class))->getMethods() as $method) {
        $store->shouldReceive($method->name);
        $expectation = $store->mockery_findExpectation($method->name, []);
        if (! $expectation instanceof Expectation) {
            throw new RuntimeException('The identity access test boundary was not registered.');
        }
        $method->name === 'selectRole'
            ? $expectation->twice()->andThrow(new IdentityViolation('IDENTITY_CONTEXT_STALE', 409))
            : $expectation->andReturnUsing(fn (mixed ...$arguments): mixed => $real->{$method->name}(...$arguments));
    }
    app()->instance(IdentityAccessStore::class, $store);

    $this->actingAs($one)->get(route('enter'))->assertRedirect(route('dashboard'));
    expect(app(EnterSoleApp::class)->selectSole($one->id, 'investor'))->toBeFalse()->and(soleAppActiveRole($one))->toBeNull();
});
