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
                'frozen' => false, 'you' => true, 'link' => ['url' => '/admin/staff?operator='.$analyst->id, 'method' => 'get']]]));
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

                return $rows[(string) $viewer->id]['role'] === 'compliance' && $rows[(string) $viewer->id]['you'] === true
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
            ->where('party.links.close.url', '/admin/staff')->where('party.actions', []));

    $this->actingAs($viewer)->get(route('staff.staff.index', ['operator' => $viewer->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.health', 'active')->where('party.freeze', null)
            ->where('party.stats.0.value.value', 'Super-admin')->has('party.restrictions', 1));
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
        ->assertJsonPath('data.party.links.close.url', '/api/v1/staff/staff');
    Sanctum::actingAs($viewer, ['staff:investors:read']);
    $this->getJson('/api/v1/staff/staff')->assertForbidden();
});
