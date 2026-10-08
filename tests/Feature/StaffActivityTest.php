<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\CommandOperation;
use App\Models\IdentityAuditEvent;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

/**
 * @param  list<string>  $roles
 * @param  array<string, mixed>  $attributes
 */
function trailStaff(array $roles, array $attributes = []): User
{
    $user = User::factory()->withTwoFactor()->create($attributes);
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Activity trail assignment.', (string) Str::uuid(), $roles);

    return $user;
}

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 8)->setTime(10, 0));
});

test('Compliance and superadmin read the designed trail; other staff, participants and guests do not', function (): void {
    $officer = trailStaff(['compliance']);

    $this->actingAs($officer)->get(route('staff.events.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/events')->where('contract_version', 'staff-activity-v1')
            ->where('nav.events.url', '/admin/activity')->where('export', ['state' => 'idle'])->where('actions', [])
            ->where('filters', ['q' => '', 'preset' => null, 'from' => null, 'to' => null])->where('total', 1)->where('more', null)
            ->where('events.0.action', ['code' => 'staff.configure', 'label' => 'Changed staff access', 'tone' => 'purple'])
            ->where('events.0.actor', 'Server console')->where('events.0.source', 'Server console')->where('events.0.target', $officer->name)
            ->where('events.0.reason', 'Activity trail assignment.')
            ->where('events.0.changes', [['field' => 'enabled', 'before' => 'false', 'after' => 'true'], ['field' => 'roles', 'before' => '', 'after' => 'compliance']]));
    $this->actingAs(trailStaff(['superadmin']))->get(route('staff.events.index'))->assertOk();
    foreach (['analyst', 'approver', 'treasury'] as $role) {
        $this->actingAs(trailStaff([$role]))->get(route('staff.events.index'))->assertForbidden();
    }
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.events.index'))->assertForbidden();
    auth()->logout();
    $this->get(route('staff.events.index'))->assertRedirect(route('login'));
});

test('rows come from the identity log and the command journal, newest first, with their outcome and changes', function (): void {
    $officer = trailStaff(['compliance'], ['name' => 'Diane Uwase']);
    $approver = trailStaff(['approver'], ['name' => 'Eric Ndoli']);
    $investor = User::factory()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]);
    $membership = RoleMembership::factory()->create(['party_id' => $investor->party_id, 'role' => 'investor']);
    IdentityAuditEvent::factory()->create(['actor_key' => 'user:'.$officer->id, 'actor_user_id' => $officer->id, 'target_type' => 'membership',
        'target_id' => $membership->id, 'action' => 'membership.change', 'reason' => 'Identity documents matched.',
        'before' => ['status' => 'pending', 'revision' => 1], 'after' => ['status' => 'active', 'revision' => 2, 'evidence_reference' => 'case:1'],
        'created_at' => now()->subMinutes(30)]);
    IdentityAuditEvent::factory()->create(['actor_key' => 'user:'.$investor->id, 'actor_user_id' => $investor->id, 'target_type' => 'bookmark',
        'target_id' => (string) Str::ulid(), 'action' => 'bookmark.save', 'created_at' => now()->subMinutes(20)]);
    CommandOperation::factory()->create(['actor_key' => 'party:'.$investor->party_id, 'actor_user_id' => $investor->id, 'command' => 'primary.confirm',
        'target_type' => 'primary_reservation', 'target_id' => 'res-1', 'result' => ['status' => 'completed', 'code' => 'PURCHASE_CONFIRMED'],
        'created_at' => now()->subMinutes(10)]);
    CommandOperation::factory()->create(['actor_key' => 'staff:'.$approver->id, 'actor_user_id' => $approver->id, 'command' => 'disbursement.approve',
        'target_type' => 'disbursement', 'target_id' => 'disb-1', 'result' => ['status' => 'rejected', 'code' => 'SELF_APPROVAL_FORBIDDEN'],
        'created_at' => now()->subMinutes(5)]);
    CommandOperation::factory()->create(['actor_key' => 'staff:'.$approver->id, 'actor_user_id' => $approver->id, 'command' => 'audit.location.verify',
        'target_type' => 'audit.location', 'target_id' => 'loc-1', 'result' => [], 'created_at' => now()->subMinute()]);

    $this->actingAs($officer)->get(route('staff.events.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total', 6)->has('events', 6)
            ->where('events.2.action', ['code' => 'audit.location.verify', 'label' => 'Audit location verify', 'tone' => 'grey'])
            ->where('events.2.target', 'Audit location loc-1')->where('events.2.changes', [])->where('events.2.reason', null)
            ->where('events.3.action', ['code' => 'disbursement.approve', 'label' => 'Approved a disbursement (refused)', 'tone' => 'red'])
            ->where('events.3.actor', 'Eric Ndoli')->where('events.3.source', 'Admin console')->where('events.3.target', 'Disbursement disb-1')
            ->where('events.3.changes', [['field' => 'outcome', 'before' => null, 'after' => 'SELF_APPROVAL_FORBIDDEN']])
            ->where('events.4.action', ['code' => 'primary.confirm', 'label' => 'Confirmed a purchase', 'tone' => 'green'])
            ->where('events.4.actor', 'Aline Uwase')->where('events.4.source', 'Participant app')
            ->where('events.5.action.label', 'Changed a membership')->where('events.5.target', 'Aline Uwase')->where('events.5.source', 'Admin console')
            ->where('events.5.reason', 'Identity documents matched.')
            ->where('events.5.changes', [['field' => 'status', 'before' => 'pending', 'after' => 'active']])
            ->where('events', fn (Collection $events): bool => $events->pluck('action.code')->doesntContain('bookmark.save')));

    $this->actingAs($officer)->get(route('staff.events.index', ['q' => 'eric']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total', 3)->where('search', 'eric')->where('filters.q', 'eric'));
    $this->actingAs($officer)->get(route('staff.events.index', ['q' => 'membership']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total', 1));
    $this->actingAs($officer)->get(route('staff.events.index', ['q' => 'aline uwase']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total', 2));
    $this->actingAs($officer)->get(route('staff.events.index', ['q' => 'reversal', 'preset' => '7d']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total', 0)->where('events', [])
            ->where('filters', ['q' => 'reversal', 'preset' => '7d', 'from' => null, 'to' => null]));
    $this->actingAs($officer)->get(route('staff.events.index', ['limit' => 2]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('events', 2)->where('total', 6)
            ->where('more', ['url' => '/admin/activity?limit=500', 'method' => 'get']));
    $this->actingAs($officer)->get(route('staff.events.index', ['limit' => 2, 'q' => 'a', 'preset' => '30d']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('more.url', '/admin/activity?q=a&preset=30d&limit=500'));
    $this->actingAs($officer)->get(route('staff.events.index', ['preset' => 'year']))->assertSessionHasErrors('preset');
});

test('presets count back from now and a date range covers whole Kigali days', function (): void {
    $officer = trailStaff(['compliance']);
    foreach (['old' => now()->subDays(40), 'month' => now()->subDays(20), 'week' => now()->subDays(3), 'yesterday' => now()->subDay()] as $target => $at) {
        CommandOperation::factory()->create(['command' => 'wallet.deposit', 'target_type' => 'investor_wallet', 'target_id' => $target, 'created_at' => $at]);
    }

    $count = fn (array $query): int => $this->actingAs($officer)->get(route('staff.events.index', $query))->assertOk()->inertiaPage()['props']['total'];
    expect($count([]))->toBe(5)
        ->and($count(['preset' => 'today']))->toBe(1)
        ->and($count(['preset' => '7d']))->toBe(3)
        ->and($count(['preset' => '30d']))->toBe(4)
        ->and($count(['from' => now()->subDays(25)->toDateString(), 'to' => now()->subDays(2)->toDateString()]))->toBe(2)
        ->and($count(['to' => now()->subDays(30)->toDateString()]))->toBe(1)
        ->and($count(['from' => now()->toDateString()]))->toBe(1)
        ->and($count(['preset' => 'today', 'from' => '2020-01-01']))->toBe(1);
    $this->actingAs($officer)->get(route('staff.events.index', ['preset' => 'today', 'from' => '2020-01-01']))
        ->assertInertia(fn (Assert $page) => $page->where('filters', ['q' => '', 'preset' => 'today', 'from' => null, 'to' => null]));
    $this->actingAs($officer)->get(route('staff.events.index', ['from' => '2026-09-01', 'to' => '2026-10-01']))
        ->assertInertia(fn (Assert $page) => $page->where('filters', ['q' => '', 'preset' => null, 'from' => '2026-09-01', 'to' => '2026-10-01']));
});

test('the trail answers over the API with the read ability and links API routes', function (): void {
    $officer = trailStaff(['compliance']);
    CommandOperation::factory()->count(2)->create();

    Sanctum::actingAs($officer, ['staff:events:read']);
    $this->getJson('/api/v1/staff/activity?limit=1')->assertOk()
        ->assertJsonPath('data.contract_version', 'staff-activity-v1')
        ->assertJsonPath('data.nav.events.url', '/api/v1/staff/activity')
        ->assertJsonPath('data.more.url', '/api/v1/staff/activity?limit=500')
        ->assertJsonPath('data.total', 3);
    Sanctum::actingAs($officer, ['staff:investors:read']);
    $this->getJson('/api/v1/staff/activity')->assertForbidden();
});
