<?php

declare(strict_types=1);

use App\Models\IdentityAuditEvent;
use App\Models\InvestorAccountRestriction;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\StaffFixture;

beforeEach(function (): void {
    $this->officer = User::factory()->withTwoFactor()->create(['name' => 'Odette Officer', 'created_at' => now()->subMonths(2)]);
    StaffFixture::provision($this->officer, ['compliance'], 'Users page assignment.');
});

/** A person with a Party, signed up `$daysAgo` days ago. */
function usersPerson(string $name, int $daysAgo, array $party = []): User
{
    return User::factory()->create(['name' => $name, 'party_id' => Party::factory()->create($party)->id, 'created_at' => now()->subDays($daysAgo)]);
}

function usersCase(User $user, string $status): string
{
    $id = strtolower((string) Str::ulid());
    DB::table('investor_verifications')->insert(['id' => $id, 'party_id' => $user->party_id, 'revision' => 1, 'status' => $status, 'state' => '{}',
        'submitted_at' => $status === 'draft' ? null : now()->subDay(), 'created_at' => now()->subDays(2), 'updated_at' => now()->subDay()]);

    return $id;
}

function usersRequest(User $user, string $role, string $organization, ?string $reference = null): void
{
    DB::table('role_access_requests')->insert(['id' => strtolower((string) Str::ulid()), 'party_id' => $user->party_id, 'role' => $role,
        'organization' => $organization, 'reference' => $reference, 'operation_id' => strtolower((string) Str::ulid()), 'created_at' => now()->subHours(3)]);
}

test('Compliance and superadmin see Users in the Accounts group; other staff and guests do not', function (): void {
    $this->actingAs($this->officer)->get(route('staff.users.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/users')->where('viewer.role', 'compliance')
            ->where('nav.users.url', '/admin/users')->where('user', null)->where('contract_version', 'staff-user-directory-v1'));
    $superadmin = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($superadmin, ['superadmin'], 'Users page assignment.');
    $this->actingAs($superadmin)->get(route('staff.users.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'superadmin'));
    $approver = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($approver, ['approver'], 'Users page assignment.');
    $this->actingAs($approver)->get(route('staff.users.index'))->assertForbidden();
    $this->actingAs($approver)->get(route('staff.dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('nav.users', null));
    auth()->logout();
    $this->get(route('staff.users.index'))->assertRedirect(route('login'));
});

test('every account is listed with its roles, KYC state and basics, and the chips count them', function (): void {
    $investor = usersPerson('Aline Investor', 1, ['verified_at' => now()]);
    RoleMembership::factory()->active()->create(['party_id' => $investor->party_id, 'role' => 'investor']);
    usersCase($investor, 'approved');
    DB::table('sessions')->insert(['id' => Str::random(40), 'user_id' => $investor->id, 'payload' => '', 'last_activity' => now()->subHours(2)->timestamp]);

    // Signed up as an Auditor but recorded as an Investor: the Investor role shows beside the Auditor request.
    $mixed = usersPerson('Bosco Mixed', 3);
    RoleMembership::factory()->active()->create(['party_id' => $mixed->party_id, 'role' => 'investor']);
    usersRequest($mixed, 'auditor', 'Bosco & Co', 'ICPAR-77');
    usersCase($mixed, 'submitted');

    $auditor = usersPerson('Chantal Auditor', 10);
    RoleMembership::factory()->create(['party_id' => $auditor->party_id, 'role' => 'auditor', 'status' => 'pending']);
    RoleMembership::factory()->create(['party_id' => $auditor->party_id, 'role' => 'business', 'status' => 'suspended']);
    usersRequest($auditor, 'auditor', 'Chantal Audit');
    usersCase($auditor, 'draft');

    $business = usersPerson('Didier Business', 20);
    RoleMembership::factory()->active()->create(['party_id' => $business->party_id, 'role' => 'business']);
    RoleMembership::factory()->create(['party_id' => $business->party_id, 'role' => 'investor', 'status' => 'revoked']);
    usersCase($business, 'rejected');
    InvestorAccountRestriction::factory()->create(['party_id' => $business->party_id, 'effective_at' => now()->subDay(), 'expires_at' => null]);

    $newcomer = User::factory()->unverified()->create(['name' => 'Eric Newcomer', 'created_at' => now()->subDays(40)]);

    $this->actingAs($this->officer)->get(route('staff.users.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats', [['key' => 'total_users', 'value' => ['kind' => 'count', 'value' => 6]],
                ['key' => 'new_this_week', 'value' => ['kind' => 'count', 'value' => 2]],
                ['key' => 'kyc_verified', 'value' => ['kind' => 'percent', 'value' => '16']],
                ['key' => 'awaiting_kyc', 'value' => ['kind' => 'count', 'value' => 1]]])
            ->where('chips', fn (Collection $chips): bool => $chips->pluck('count', 'key')->all()
                === ['all' => 6, 'investor' => 2, 'business' => 2, 'auditor' => 1, 'staff' => 1, 'no_role' => 1])
            ->where('chips.0', ['key' => 'all', 'count' => 6, 'link' => ['url' => '/admin/users', 'method' => 'get'], 'active' => true])
            ->where('filters', [['key' => 'kyc', 'value' => 'any', 'options' => ['any', 'verified', 'in_review', 'in_progress', 'rejected', 'not_started']],
                ['key' => 'sort', 'value' => 'newest', 'options' => ['newest', 'oldest', 'name']]])
            ->where('shown', 6)->where('total', 6)
            ->where('rows', function (Collection $rows) use ($investor, $mixed): bool {
                $byName = $rows->keyBy('name');

                return $rows->pluck('name')->all() === ['Aline Investor', 'Bosco Mixed', 'Chantal Auditor', 'Didier Business', 'Eric Newcomer', 'Odette Officer']
                    && $byName->map(fn (array $row): string => $row['kyc'])->all() === ['Aline Investor' => 'verified', 'Bosco Mixed' => 'in_review',
                        'Chantal Auditor' => 'in_progress', 'Didier Business' => 'rejected', 'Eric Newcomer' => 'not_started', 'Odette Officer' => 'not_started']
                    && $byName['Aline Investor']['memberships'][0]['role'] === 'investor' && $byName['Aline Investor']['memberships'][0]['status'] === 'active'
                    && is_string($byName['Aline Investor']['last_seen_at']) && $byName['Bosco Mixed']['last_seen_at'] === null
                    && $byName['Aline Investor']['link'] === ['url' => '/admin/users?user='.$investor->id, 'method' => 'get']
                    && $byName['Bosco Mixed']['requested'] === ['auditor'] && $byName['Bosco Mixed']['id'] === (string) $mixed->id
                    && array_column($byName['Chantal Auditor']['memberships'], 'status', 'role') === ['auditor' => 'pending', 'business' => 'suspended']
                    && $byName['Chantal Auditor']['requested'] === []
                    && array_column($byName['Didier Business']['memberships'], 'status', 'role') === ['business' => 'active', 'investor' => 'revoked']
                    && $byName['Didier Business']['restricted'] === true && $byName['Aline Investor']['restricted'] === false
                    && $byName['Eric Newcomer']['email_verified'] === false && $byName['Eric Newcomer']['memberships'] === []
                    && $byName['Eric Newcomer']['staff'] === null
                    && $byName['Odette Officer']['staff'] === ['roles' => ['compliance'], 'enabled' => true];
            }));
});

test('the role chips, KYC filter, sort and search are server queries that keep each other in their links', function (): void {
    $investor = usersPerson('Aline Investor', 1, ['verified_at' => now()]);
    RoleMembership::factory()->active()->create(['party_id' => $investor->party_id, 'role' => 'investor']);
    $second = usersPerson('Zed Investor', 5);
    RoleMembership::factory()->active()->create(['party_id' => $second->party_id, 'role' => 'investor']);
    usersCase($second, 'submitted');
    $auditor = usersPerson('Mona Auditor', 9);
    RoleMembership::factory()->active()->create(['party_id' => $auditor->party_id, 'role' => 'auditor']);
    User::factory()->create(['name' => 'Nobody Yet']);

    $names = fn (array $query): array => collect($this->actingAs($this->officer)->get(route('staff.users.index', $query))->assertOk()
        ->viewData('page')['props']['rows'])->pluck('name')->all();

    expect($names(['role' => 'investor']))->toBe(['Aline Investor', 'Zed Investor'])
        ->and($names(['role' => 'investor', 'sort' => 'oldest']))->toBe(['Zed Investor', 'Aline Investor'])
        ->and($names(['sort' => 'name']))->toBe(['Aline Investor', 'Mona Auditor', 'Nobody Yet', 'Odette Officer', 'Zed Investor'])
        ->and($names(['role' => 'auditor']))->toBe(['Mona Auditor'])
        ->and($names(['role' => 'staff']))->toBe(['Odette Officer'])
        ->and($names(['role' => 'business']))->toBe([])
        ->and($names(['role' => 'no_role']))->toBe(['Nobody Yet'])
        ->and($names(['kyc' => 'in_review']))->toBe(['Zed Investor'])
        ->and($names(['kyc' => 'verified', 'role' => 'auditor']))->toBe([])
        ->and($names(['q' => 'mona']))->toBe(['Mona Auditor'])
        ->and($names(['q' => $second->email]))->toBe(['Zed Investor']);

    $this->actingAs($this->officer)->get(route('staff.users.index', ['role' => 'investor', 'kyc' => 'in_review', 'sort' => 'name', 'q' => 'zed']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('search', 'zed')->where('filters.0.value', 'in_review')->where('filters.1.value', 'name')
            ->where('shown', 1)->where('total', 1)->where('chips.0.count', 1)->where('chips.0.active', false)
            ->where('chips.1', ['key' => 'investor', 'count' => 1, 'link' => ['url' => '/admin/users?q=zed&kyc=in_review&sort=name&role=investor', 'method' => 'get'], 'active' => true])
            ->where('rows.0.link.url', '/admin/users?q=zed&kyc=in_review&sort=name&role=investor&user='.$second->id)
            // The stats count the whole platform, whatever the filters.
            ->where('stats.0.value.value', 5)->where('stats.2.value.value', '20')->where('stats.3.value.value', 1));

    $this->actingAs($this->officer)->get(route('staff.users.index', ['role' => 'everyone']))->assertSessionHasErrors('role');
    $this->actingAs($this->officer)->get(route('staff.users.index', ['kyc' => 'pending']))->assertSessionHasErrors('kyc');
    $this->actingAs($this->officer)->get(route('staff.users.index', ['sort' => 'wallet']))->assertSessionHasErrors('sort');
});

test("an account's drawer shows its roles, identity, sign-in security, access requests, history and the directories that act on it", function (): void {
    $mixed = usersPerson('Bosco Mixed', 3);
    $membership = RoleMembership::factory()->active()->create(['party_id' => $mixed->party_id, 'role' => 'investor']);
    $caseId = usersCase($mixed, 'submitted');
    usersRequest($mixed, 'auditor', 'Bosco & Co', 'ICPAR-77');
    DB::table('passkeys')->insert(['user_id' => $mixed->id, 'name' => 'Phone', 'credential_id' => Str::random(20), 'credential' => '{}', 'created_at' => now(), 'updated_at' => now()]);
    IdentityAuditEvent::factory()->create(['actor_key' => 'user:'.$mixed->id, 'actor_user_id' => $mixed->id, 'target_type' => 'membership',
        'target_id' => $membership->id, 'action' => 'investor.self_grant', 'reason' => 'Chose Investor in the launcher.', 'created_at' => now()->subHours(2)]);
    IdentityAuditEvent::factory()->create(['actor_key' => 'console', 'actor_user_id' => null, 'target_type' => 'party',
        'target_id' => $mixed->party_id, 'action' => 'organization.resolve', 'reason' => 'Registry match.', 'created_at' => now()->subHour()]);

    $this->actingAs($this->officer)->get(route('staff.users.index', ['role' => 'investor', 'user' => $mixed->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('user.id', (string) $mixed->id)->where('user.name', 'Bosco Mixed')
            ->where('user.kyc', 'in_review')->where('user.requested', ['auditor'])->where('user.two_factor', false)->where('user.passkeys', 1)
            ->where('user.email_verified_at', fn (?string $at): bool => is_string($at))->where('user.submitted_at', fn (?string $at): bool => is_string($at))
            ->where('user.requests', [['role' => 'auditor', 'organization' => 'Bosco & Co', 'reference' => 'ICPAR-77',
                'sent_at' => now()->subHours(3)->startOfSecond()->toIso8601String()]])
            ->where('user.history', fn (Collection $history): bool => $history->pluck('action')->all() === ['organization.resolve', 'investor.self_grant']
                && $history[0]['actor'] === '' && $history[1]['actor'] === 'Bosco Mixed' && $history[1]['reason'] === 'Chose Investor in the launcher.')
            ->where('user.links.close', ['url' => '/admin/users?role=investor', 'method' => 'get'])
            ->where('user.links.investor', ['url' => '/admin/investors?verification='.$caseId, 'method' => 'get'])
            ->where('user.links.business', null)->where('user.links.auditor', null)->where('user.links.staff', null)
            ->where('user.links.access_requests', ['url' => '/admin/access-requests', 'method' => 'get']));

    // Staff accounts link to Staff & Roles and show their own access history; an Investor without a case opens by Party.
    $investor = usersPerson('Aline Investor', 1, ['verified_at' => now()]);
    RoleMembership::factory()->active()->create(['party_id' => $investor->party_id, 'role' => 'investor']);
    $this->actingAs($this->officer)->get(route('staff.users.index', ['user' => $investor->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('user.links.investor', ['url' => '/admin/investors?investor='.$investor->party_id, 'method' => 'get'])
            ->where('user.submitted_at', null)->where('user.requests', [])->where('user.history', [])->where('user.links.access_requests', null));
    $this->actingAs($this->officer)->get(route('staff.users.index', ['user' => $this->officer->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('user.staff', ['roles' => ['compliance'], 'enabled' => true])->where('user.two_factor', true)
            ->where('user.links.staff', ['url' => '/admin/staff', 'method' => 'get'])->where('user.links.investor', null)
            ->where('user.history.0.action', 'staff.configure')->where('user.history.0.reason', 'Users page assignment.'));

    $this->actingAs($this->officer)->get(route('staff.users.index', ['user' => 999999]))->assertNotFound();
});

test('a superadmin reaches the Business, Auditor and Access requests tools from an account that holds those roles', function (): void {
    $superadmin = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($superadmin, ['superadmin'], 'Users page assignment.');
    $person = usersPerson('Chantal Both', 4);
    RoleMembership::factory()->active()->create(['party_id' => $person->party_id, 'role' => 'business']);
    RoleMembership::factory()->active()->create(['party_id' => $person->party_id, 'role' => 'auditor']);
    usersRequest($person, 'business', 'Chantal Ltd');

    $this->actingAs($superadmin)->get(route('staff.users.index', ['user' => $person->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('user.links.business', ['url' => '/admin/businesses', 'method' => 'get'])
            ->where('user.links.auditor', ['url' => '/admin/auditors', 'method' => 'get'])
            ->where('user.links.access_requests', ['url' => '/admin/access-requests', 'method' => 'get'])
            ->where('user.requested', [])->where('user.requests.0.role', 'business'));
});
