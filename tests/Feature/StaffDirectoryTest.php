<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\Party;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

/**
 * @param  list<string>  $roles
 * @param  array<string, mixed>  $attributes
 */
function rosterStaff(array $roles, array $attributes = []): User
{
    $user = User::factory()->withTwoFactor()->create($attributes);
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Staff roster assignment.', (string) Str::uuid(), $roles);

    return $user;
}

test('every staff member sees the designed Staff & Roles directory; participants and guests do not', function (): void {
    $analyst = rosterStaff(['analyst'], ['name' => 'Grace Kalisa']);

    $this->actingAs($analyst)->get(route('staff.staff.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/parties')->where('contract_version', 'staff-directory-v1')->where('kind', 'staff')
            ->where('viewer.role', 'analyst')->where('viewer.initials', 'GK')->where('nav.staff.url', '/admin/staff')->where('nav.today.url', '/admin/dashboard')
            ->where('nav.events', null)->where('policy', [])->where('filters', [])->where('party', null)
            ->where('stats', [['key' => 'operators', 'value' => ['kind' => 'count', 'value' => 1]], ['key' => 'approvers', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'frozen_accounts', 'value' => ['kind' => 'count', 'value' => 0]]])
            ->where('directory.rows', [['id' => (string) $analyst->id, 'name' => 'Grace Kalisa', 'email' => $analyst->email, 'initials' => 'GK', 'role' => 'analyst',
                'roles' => ['analyst'], 'frozen' => false, 'you' => true, 'link' => ['url' => '/admin/staff?operator='.$analyst->id, 'method' => 'get']]]));
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.staff.index'))->assertForbidden();
    auth()->logout();
    $this->get(route('staff.staff.index'))->assertRedirect(route('login'));
});

test('rows carry real roles and account states, and the chips and search count them', function (): void {
    $viewer = rosterStaff(['compliance', 'approver'], ['name' => 'A. Diane']);
    $eric = rosterStaff(['approver'], ['name' => 'Eric Ndoli']);
    $frozen = rosterStaff(['approver'], ['name' => 'Jean-Paul M.']);
    app(ConfigureStaffAccess::class)->handle($frozen->id, false, 'Left the operations team.', (string) Str::uuid(), ['approver']);
    $treasury = rosterStaff(['treasury'], ['name' => 'Tresor Mugabo']);
    StaffAccount::factory()->create(['enabled' => true, 'roles' => []]);

    $this->actingAs($viewer)->get(route('staff.staff.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'compliance')->where('viewer.initials', 'AD')
            ->where('stats.0.value.value', 5)->where('stats.1.value.value', 3)->where('stats.2.value.value', 1)
            ->where('chips', [['key' => 'all', 'count' => 5, 'link' => ['url' => '/admin/staff', 'method' => 'get'], 'active' => true],
                ['key' => 'active', 'count' => 4, 'link' => ['url' => '/admin/staff?chip=active', 'method' => 'get'], 'active' => false],
                ['key' => 'frozen', 'count' => 1, 'link' => ['url' => '/admin/staff?chip=frozen', 'method' => 'get'], 'active' => false]])
            ->where('shown', 5)->where('total', 5)->where('nav.events.url', '/admin/activity')
            ->where('directory.rows', function (Collection $rows) use ($viewer, $eric, $frozen, $treasury): bool {
                $rows = $rows->keyBy('id');

                return $rows[(string) $viewer->id]['role'] === 'compliance' && $rows[(string) $viewer->id]['roles'] === ['compliance', 'approver']
                    && $rows[(string) $viewer->id]['you'] === true
                    && $rows[(string) $eric->id]['role'] === 'approver' && $rows[(string) $eric->id]['you'] === false
                    && $rows[(string) $frozen->id]['frozen'] === true && $rows[(string) $frozen->id]['initials'] === 'JP'
                    && $rows[(string) $treasury->id]['role'] === 'treasury'
                    && $rows->where('role', null)->count() === 1;
            }));

    $this->actingAs($viewer)->get(route('staff.staff.index', ['chip' => 'frozen', 'q' => 'jean']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('search', 'jean')->where('shown', 1)->where('total', 1)
            ->where('chips.2', ['key' => 'frozen', 'count' => 1, 'link' => ['url' => '/admin/staff?q=jean&chip=frozen', 'method' => 'get'], 'active' => true])
            ->where('directory.rows.0.link.url', '/admin/staff?q=jean&chip=frozen&operator='.$frozen->id));
    $this->actingAs($viewer)->get(route('staff.staff.index', ['chip' => 'active']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total', 4));
    $this->actingAs($viewer)->get(route('staff.staff.index', ['q' => 'nobody']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total', 0)->where('directory.rows', [])->where('stats.0.value.value', 0));
    $this->actingAs($viewer)->get(route('staff.staff.index', ['chip' => 'everyone']))->assertSessionHasErrors('chip');
});

test("an operator's 360 shows their roles, access history and the freeze that disabled them", function (): void {
    $viewer = rosterStaff(['superadmin']);
    $frozen = rosterStaff(['approver', 'treasury'], ['name' => 'Jean-Paul M.']);
    app(ConfigureStaffAccess::class)->handle($frozen->id, false, 'Left the operations team.', (string) Str::uuid(), ['approver', 'treasury']);

    $this->actingAs($viewer)->get(route('staff.staff.index', ['operator' => $frozen->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.id', (string) $frozen->id)->where('party.kind', 'staff')
            ->where('party.name', 'Jean-Paul M.')->where('party.subtitle', $frozen->email)->where('party.health', 'frozen')
            ->where('party.stats', [['key' => 'role', 'value' => ['kind' => 'text', 'value' => 'Approver, Treasury']]])
            ->where('party.list', null)->where('party.kyc', null)->where('party.licence', null)->where('party.release_blocked', null)
            ->where('party.history.0.action', ['code' => 'staff.configure', 'label' => 'Changed staff access', 'tone' => 'purple'])
            ->where('party.history.0.actor', 'Server console')->where('party.history.0.reason', 'Left the operations team.')
            ->has('party.history', 2)
            ->where('party.freeze', fn ($freeze): bool => $freeze['actor'] === 'Server console' && $freeze['reason'] === 'Left the operations team.' && is_string($freeze['at']))
            ->where('party.restrictions.0.kind', 'freeze')->where('party.restrictions.0.action.label', 'Disabled staff access')
            ->where('party.restrictions.1.kind', 'release')->where('party.restrictions.1.action', ['code' => 'staff.configure', 'label' => 'Enabled staff access', 'tone' => 'green'])
            ->where('party.links.close.url', '/admin/staff')
            ->where('party.actions', ['release' => ['url' => '/admin/staff/'.$frozen->id.'/reactivate', 'method' => 'post']]));

    $this->actingAs($viewer)->get(route('staff.staff.index', ['operator' => $viewer->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.health', 'active')->where('party.freeze', null)
            ->where('party.stats.0.value.value', 'Super-admin')->has('party.restrictions', 1)->where('party.actions', []));
    $bare = StaffAccount::factory()->create(['enabled' => false, 'roles' => []]);
    $this->actingAs($viewer)->get(route('staff.staff.index', ['operator' => $bare->user_id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.health', 'frozen')->where('party.freeze', null)
            ->where('party.stats.0.value.value', '—')->where('party.history', []));
    $this->actingAs($viewer)->get(route('staff.staff.index', ['operator' => 999999]))->assertNotFound();
});

test('the directory answers over the API with the read ability and links API routes', function (): void {
    $viewer = rosterStaff(['compliance']);

    Sanctum::actingAs($viewer, ['staff:staff:read']);
    $this->getJson('/api/v1/staff/staff?operator='.$viewer->id)->assertOk()
        ->assertJsonPath('data.contract_version', 'staff-directory-v1')
        ->assertJsonPath('data.nav.staff.url', '/api/v1/staff/staff')
        ->assertJsonPath('data.nav.today.url', '/api/v1/staff/dashboard')
        ->assertJsonPath('data.nav.events.url', '/api/v1/staff/activity')
        ->assertJsonPath('data.directory.rows.0.link.url', '/api/v1/staff/staff?operator='.$viewer->id)
        ->assertJsonPath('data.party.links.close.url', '/api/v1/staff/staff')
        ->assertJsonPath('data.permission_matrix.0', ['key' => 'admin.open', 'roles' => ['superadmin', 'compliance', 'approver', 'treasury', 'analyst']]);
    Sanctum::actingAs($viewer, ['staff:investors:read']);
    $this->getJson('/api/v1/staff/staff')->assertForbidden();
});

test('the permission matrix lists what each role may do from the role definitions', function (): void {
    $this->actingAs(rosterStaff(['analyst']))->get(route('staff.staff.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('permission_matrix', function (Collection $matrix): bool {
            $roles = $matrix->pluck('roles', 'key');

            return $matrix->count() === 28 && $matrix->pluck('key')->unique()->count() === 28
                && $matrix->whereNotNull('withheld')->pluck('withheld', 'key')->all() === ['accounts.freeze' => 'not_built',
                    'capacity.adjust' => 'not_built', 'records.edit' => 'not_built', 'ledger.correct' => 'not_built', 'policies.change' => 'not_built',
                    'records.delete' => 'by_rule']
                && $matrix->whereNotNull('withheld')->every(fn (array $row): bool => $row['roles'] === [])
                && $roles['admin.open'] === ['superadmin', 'compliance', 'approver', 'treasury', 'analyst']
                && $roles['primary.view'] === ['superadmin', 'compliance', 'approver', 'treasury', 'analyst']
                && $roles['investors.verify'] === ['superadmin', 'compliance']
                && $roles['disbursements.hold'] === ['compliance', 'approver', 'treasury']
                && $roles['disbursements.requery'] === ['treasury']
                && $roles['staging.mail.testers.manage'] === ['superadmin'] && $roles['staff.manage'] === ['superadmin']
                && $matrix->filter(fn (array $row): bool => in_array('analyst', $row['roles'], true))->pluck('key')->all()
                    === ['admin.open', 'businesses.view', 'audit.reports.view', 'primary.view'];
        }));
});

test('a superadmin suspends and reactivates another operator with a reason, and the action log shows it', function (): void {
    $superadmin = rosterStaff(['superadmin'], ['name' => 'A. Diane']);
    $eric = rosterStaff(['approver', 'treasury'], ['name' => 'Eric Ndoli']);

    $this->actingAs($superadmin)->get(route('staff.staff.index', ['operator' => $eric->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.actions', ['freeze' => ['url' => '/admin/staff/'.$eric->id.'/suspend', 'method' => 'post']]));
    $this->actingAs($superadmin)->post(route('staff.staff.suspend', $eric->id), ['request_id' => (string) Str::uuid(), 'reason' => ''])
        ->assertSessionHasErrors('reason');
    $request = (string) Str::uuid();
    $this->actingAs($superadmin)->post(route('staff.staff.suspend', $eric->id), ['request_id' => $request, 'reason' => 'On leave until November.'])
        ->assertRedirect(route('staff.staff.index', ['operator' => $eric->id]));
    $this->actingAs($superadmin)->post(route('staff.staff.suspend', $eric->id), ['request_id' => $request, 'reason' => 'On leave until November.'])
        ->assertRedirect(route('staff.staff.index', ['operator' => $eric->id]));
    expect(StaffAccount::query()->find($eric->id))->enabled->toBeFalse()->roles->toBe(['approver', 'treasury']);
    $this->actingAs($eric)->get(route('staff.staff.index'))->assertForbidden();
    $this->actingAs($superadmin)->post(route('staff.staff.suspend', $eric->id), ['request_id' => (string) Str::uuid(), 'reason' => 'Again.'])
        ->assertSessionHasErrors(['reason' => "This operator's access already changed. Review it again."]);
    $this->actingAs($superadmin)->post(route('staff.staff.suspend', $eric->id), ['request_id' => $request, 'reason' => 'Different reason.'])
        ->assertSessionHasErrors(['reason' => 'That request was already used for different details. Try again.']);

    $this->actingAs($superadmin)->get(route('staff.staff.index', ['operator' => $eric->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.health', 'frozen')->where('party.freeze.actor', 'A. Diane')
            ->where('party.freeze.reason', 'On leave until November.')
            ->where('party.actions', ['release' => ['url' => '/admin/staff/'.$eric->id.'/reactivate', 'method' => 'post']])
            ->where('action_log.0', fn ($entry): bool => $entry['actor'] === 'A. Diane' && $entry['target'] === 'Eric Ndoli'
                && $entry['action'] === 'staff_suspended' && $entry['reason'] === 'On leave until November.' && is_string($entry['at']))
            ->where('action_log.1.action', 'staff_enabled')->where('action_log.1.actor', 'Server console'));

    $this->actingAs($superadmin)->post(route('staff.staff.reactivate', $eric->id), ['request_id' => (string) Str::uuid(), 'reason' => 'Back from leave.'])
        ->assertRedirect(route('staff.staff.index', ['operator' => $eric->id]));
    expect(StaffAccount::query()->find($eric->id))->enabled->toBeTrue()->roles->toBe(['approver', 'treasury']);
    $this->actingAs($eric)->get(route('staff.staff.index'))->assertOk();
});

test('nobody changes their own access, other roles cannot, and reactivation still needs MFA', function (): void {
    $superadmin = rosterStaff(['superadmin']);
    $compliance = rosterStaff(['compliance']);
    $frozen = rosterStaff(['analyst']);
    app(ConfigureStaffAccess::class)->handle($frozen->id, false, 'Left.', (string) Str::uuid(), ['analyst']);
    $frozen->forceFill(['two_factor_confirmed_at' => null])->save();

    $this->actingAs($superadmin)->post(route('staff.staff.suspend', $superadmin->id), ['request_id' => (string) Str::uuid(), 'reason' => 'Test.'])
        ->assertSessionHasErrors(['reason' => 'You cannot change your own access. Ask another super-admin.']);
    $this->actingAs($compliance)->post(route('staff.staff.suspend', $superadmin->id), ['request_id' => (string) Str::uuid(), 'reason' => 'Test.'])
        ->assertForbidden();
    $this->actingAs($superadmin)->post(route('staff.staff.reactivate', $frozen->id), ['request_id' => (string) Str::uuid(), 'reason' => 'Back.'])
        ->assertSessionHasErrors(['reason' => 'This operator needs a verified email and two-factor sign-in before access can return.']);
    $this->actingAs($superadmin)->post(route('staff.staff.reactivate', 999999), ['request_id' => (string) Str::uuid(), 'reason' => 'Back.'])
        ->assertNotFound();
    $this->actingAs($compliance)->get(route('staff.staff.index', ['operator' => $superadmin->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.actions', []));
    expect(StaffAccount::query()->find($superadmin->id))->enabled->toBeTrue();
});

test('a superadmin adds an operator by email with one role and a reason, and the action log shows it', function (): void {
    $superadmin = rosterStaff(['superadmin'], ['name' => 'A. Diane']);
    $analyst = rosterStaff(['analyst']);
    $candidate = User::factory()->withTwoFactor()->create(['name' => 'Eric Kamanzi', 'email' => 'eric.kamanzi@example.test']);

    $this->actingAs($superadmin)->get(route('staff.staff.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('grant', ['url' => '/admin/staff', 'method' => 'post']));
    $this->actingAs($analyst)->get(route('staff.staff.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('grant', null)->where('access', ['granted' => 4, 'total' => 22]));
    $this->actingAs($superadmin)->get(route('staff.staff.index'))->assertInertia(fn (Assert $page) => $page->where('access', ['granted' => 20, 'total' => 22]));
    $this->actingAs($superadmin)->getJson(route('api.v1.staff.staff.index'))->assertOk()->assertJsonPath('data.grant', null);

    $this->actingAs($superadmin)->post(route('staff.staff.grant'), ['request_id' => (string) Str::uuid(), 'email' => 'nope', 'role' => 'owner', 'reason' => ''])
        ->assertSessionHasErrors(['email' => 'Enter a valid email address.', 'role' => 'Choose a role.', 'reason' => 'Give the reason for this change.']);
    $request = (string) Str::uuid();
    $payload = ['request_id' => $request, 'email' => 'Eric.Kamanzi@example.test', 'role' => 'treasury', 'reason' => 'Joins the treasury desk.'];
    $this->actingAs($superadmin)->post(route('staff.staff.grant'), $payload)->assertRedirect(route('staff.staff.index', ['operator' => $candidate->id]));
    $this->actingAs($superadmin)->post(route('staff.staff.grant'), $payload)->assertRedirect(route('staff.staff.index', ['operator' => $candidate->id]));
    $this->actingAs($superadmin)->post(route('staff.staff.grant'), [...$payload, 'role' => 'analyst'])
        ->assertSessionHasErrors(['email' => 'That request was already used for different details. Try again.']);
    expect(StaffAccount::query()->find($candidate->id))->enabled->toBeTrue()->roles->toBe(['treasury'])
        ->and($candidate->refresh()->party_id)->toBeNull();
    $this->actingAs($candidate)->get(route('staff.staff.index'))->assertOk();

    $this->actingAs($superadmin)->get(route('staff.staff.index', ['operator' => $candidate->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('action_log.0', fn ($entry): bool => $entry['actor'] === 'A. Diane'
            && $entry['target'] === 'Eric Kamanzi' && $entry['action'] === 'staff_enabled' && $entry['reason'] === 'Joins the treasury desk.'));
    $this->actingAs($superadmin)->post(route('staff.staff.grant'), [...$payload, 'request_id' => (string) Str::uuid()])
        ->assertSessionHasErrors(['email' => 'That account is already an operator. Open it to suspend or reactivate it.']);
});

test('adding an operator refuses unknown, own, unsecured and participant accounts, and other roles', function (): void {
    $superadmin = rosterStaff(['superadmin']);
    $compliance = rosterStaff(['compliance']);
    $unsecured = User::factory()->create();
    $investor = User::factory()->withTwoFactor()->for(Party::factory()->verified())->create();
    $grant = fn (User $actor, string $email) => $this->actingAs($actor)->post(route('staff.staff.grant'),
        ['request_id' => (string) Str::uuid(), 'email' => $email, 'role' => 'analyst', 'reason' => 'New operator.']);

    $grant($superadmin, 'nobody@example.test')->assertSessionHasErrors(['email' => 'No account uses that email. The operator signs up first, then you add them.']);
    $grant($superadmin, $superadmin->email)->assertSessionHasErrors(['email' => 'You cannot change your own access. Ask another super-admin.']);
    $grant($superadmin, $unsecured->email)
        ->assertSessionHasErrors(['email' => 'This operator needs a verified email and two-factor sign-in before access can return.']);
    $grant($superadmin, $investor->email)
        ->assertSessionHasErrors(['email' => 'That account invests or runs a business. Operators need a separate, dedicated account.']);
    $grant($compliance, $unsecured->email)->assertForbidden();
    $this->assertDatabaseCount('staff_accounts', 2);
});
