<?php

declare(strict_types=1);

use App\Application\Identity\SelectActiveRole;
use App\Domain\Identity\InvestorAccessTerms;
use App\Models\IdentityAuditEvent;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->withoutVite();
});

/**
 * A verified person who is a member of a Business, and nothing else unless asked.
 *
 * @param  array<string, string>  $memberships
 */
function selfGrantMember(array $memberships = ['business' => 'active']): User
{
    $party = Party::factory()->verified()->create();
    foreach ($memberships as $role => $status) {
        RoleMembership::factory()->for($party)->create(['role' => $role, 'status' => $status]);
    }

    return User::factory()->for($party)->create();
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function selfGrantCommand(array $overrides = []): array
{
    return ['expected_revision' => 0, 'terms_version' => InvestorAccessTerms::VERSION, 'terms_sha256' => InvestorAccessTerms::sha256(),
        'accepted' => true, 'request_id' => (string) Str::uuid(), ...$overrides];
}

it('lets a verified Business member add Investor access on the same Party, once, and open the Investor app', function (): void {
    $user = selfGrantMember();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->where('identity.allowed_actions', ['identity.select_role', 'identity.add_investor'])
        ->where('investor_access', ['terms' => InvestorAccessTerms::toArray(), 'link' => ['url' => '/identity/investor-access', 'method' => 'post']]));

    $command = selfGrantCommand();
    $first = $this->postJson(route('identity.investor-access.store'), $command)->assertOk()
        ->assertJsonPath('data.available_roles', ['investor', 'business'])
        ->assertJsonPath('data.allowed_actions', ['identity.select_role'])->json();
    $this->postJson(route('identity.investor-access.store'), $command)->assertOk()->assertExactJson($first);

    $membership = RoleMembership::query()->where('role', 'investor')->sole();
    $event = IdentityAuditEvent::query()->where('action', 'investor.self_grant')->sole();
    expect($membership->party_id)->toBe($user->party_id)->and($membership->status)->toBe('active')
        ->and($event->actor_user_id)->toBe($user->id)->and($event->target_id)->toBe($membership->id)
        ->and($event->after)->toBe(['status' => 'active', 'revision' => $membership->revision,
            'terms_version' => InvestorAccessTerms::VERSION, 'terms_sha256' => InvestorAccessTerms::sha256()])
        ->and($event->result['code'])->toBe('INVESTOR_ACCESS_ADDED');

    $this->get(route('dashboard'))->assertInertia(fn (Assert $page): Assert => $page->where('investor_access', null));
    $this->postJson(route('identity.investor-access.store'), selfGrantCommand())
        ->assertStatus(409)->assertJsonPath('code', 'INVESTOR_ACCESS_UNAVAILABLE');

    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    $this->get(route('investor.home'))->assertRedirect();
});

it('refuses changed terms, a stale launcher, a reused request and a grant that was not accepted', function (): void {
    $user = selfGrantMember();
    $this->actingAs($user);
    $route = route('identity.investor-access.store');

    $this->postJson($route, selfGrantCommand(['terms_sha256' => str_repeat('0', 64)]))->assertStatus(409)->assertJsonPath('code', 'INVESTOR_TERMS_CHANGED');
    $this->postJson($route, selfGrantCommand(['terms_version' => 'investor-access-old']))->assertStatus(409)->assertJsonPath('code', 'INVESTOR_TERMS_CHANGED');
    $this->postJson($route, selfGrantCommand(['expected_revision' => 3]))->assertStatus(409)->assertJsonPath('code', 'ACTIVE_ROLE_REVISION_CONFLICT');
    $this->postJson($route, selfGrantCommand(['accepted' => false]))->assertUnprocessable()->assertJsonValidationErrors('accepted');

    $command = selfGrantCommand();
    $this->postJson($route, $command)->assertOk();
    $this->postJson($route, [...$command, 'expected_revision' => 1])->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');
    expect(RoleMembership::query()->where('role', 'investor')->count())->toBe(1);
});

it('leaves Investor access to staff for anyone who is not a verified Business member with no Investor history', function (?array $memberships, bool $verified): void {
    $user = $memberships === null ? User::factory()->create() : selfGrantMember($memberships);
    if (! $verified) {
        $user->party?->forceFill(['verified_at' => null])->save();
    }

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->where('investor_access', null));
    $this->postJson(route('identity.investor-access.store'), selfGrantCommand())
        ->assertStatus(409)->assertJsonPath('code', 'INVESTOR_ACCESS_UNAVAILABLE');
    expect(IdentityAuditEvent::query()->where('action', 'investor.self_grant')->exists())->toBeFalse();
})->with([
    'no Party' => [null, true],
    'an unverified identity' => [['business' => 'active'], false],
    'a pending Business membership' => [['business' => 'pending'], true],
    'a suspended Investor membership' => [['business' => 'active', 'investor' => 'suspended'], true],
    'a revoked Investor membership' => [['business' => 'active', 'investor' => 'revoked'], true],
    'an Auditor membership' => [['business' => 'active', 'auditor' => 'pending'], true],
]);

it('still offers it to a Business member whose Auditor membership was revoked', function (): void {
    $this->actingAs(selfGrantMember(['business' => 'active', 'auditor' => 'revoked']))
        ->postJson(route('identity.investor-access.store'), selfGrantCommand())->assertOk()
        ->assertJsonPath('data.available_roles', ['investor', 'business']);
});
