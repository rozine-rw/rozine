<?php

declare(strict_types=1);

use App\Models\Party;
use App\Models\RoleAccessRequest;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\StaffFixture;

/*
 * Business and Auditor access are asked for on the launcher's own cards (#616), not by email: a
 * verified person sends what staff need (KYC-R03 for a Business representative, AUD-R01 for an
 * ICPAR-licensed Audit Partner) once per role, and staff who grant that role read the request.
 */

beforeEach(function (): void {
    $this->withoutVite();
    /** What the launcher offers this person to ask for. */
    $this->launcherAccess = function (User $user): array {
        $props = [];
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertInertia(function (Assert $page) use (&$props): void {
            $props = $page->toArray()['props']['access_requests'];
        });

        return $props;
    };
});

/**
 * A verified person holding these memberships.
 *
 * @param  array<string, string>  $memberships
 */
function accessRequester(array $memberships = ['investor' => 'active'], bool $verified = true): User
{
    $party = ($verified ? Party::factory()->verified() : Party::factory())->create();
    foreach ($memberships as $role => $status) {
        RoleMembership::factory()->for($party)->create(['role' => $role, 'status' => $status]);
    }

    return User::factory()->for($party)->create(['name' => 'Jean Mugisha']);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function accessRequest(array $overrides = []): array
{
    return ['request_id' => (string) Str::uuid(), 'role' => 'business', 'organization' => '  Kivu Harvest Ltd ', 'reference' => '',
        'phone' => '+250 788 000 000', 'note' => 'Coffee washing station', ...$overrides];
}

it('sends Business access from the launcher once, shows it as sent, and replays the same request', function (): void {
    $user = accessRequester();
    $link = ['url' => '/identity/access-requests', 'method' => 'post'];

    expect(($this->launcherAccess)($user))->toBe(['requestable' => ['business'], 'sent' => [], 'link' => $link])
        ->and(($this->launcherAccess)(accessRequester([]))['requestable'])->toBe(['business', 'auditor']);
    $this->actingAs($user);

    $command = accessRequest();
    $this->post(route('identity.access-requests.store'), $command)->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
    $this->post(route('identity.access-requests.store'), $command)->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

    $row = RoleAccessRequest::query()->sole();
    expect($row->only(['party_id', 'role', 'organization', 'reference', 'phone', 'note']))->toBe(['party_id' => $user->party_id, 'role' => 'business',
        'organization' => 'Kivu Harvest Ltd', 'reference' => null, 'phone' => '+250 788 000 000', 'note' => 'Coffee washing station'])
        ->and(($this->launcherAccess)($user))->toBe(['requestable' => [], 'sent' => ['business' => $row->created_at->toIso8601String()], 'link' => $link]);

    $this->post(route('identity.access-requests.store'), accessRequest())
        ->assertSessionHasErrors(['role' => 'You already asked for this access. Rozine will contact you.']);
    $this->post(route('identity.access-requests.store'), [...$command, 'note' => 'Something else'])
        ->assertSessionHasErrors(['role' => 'That request was already sent with different details. Try again.']);
    expect(RoleAccessRequest::query()->count())->toBe(1);
});

it('asks an Auditor for the ICPAR licence and a Business for its name, and checks the phone', function (): void {
    $this->actingAs(accessRequester([]));
    $route = route('identity.access-requests.store');

    $this->post($route, accessRequest(['organization' => '']))->assertSessionHasErrors(['organization' => 'Enter the business name.']);
    $this->post($route, accessRequest(['role' => 'auditor', 'organization' => '', 'reference' => '']))
        ->assertSessionHasErrors(['reference' => 'Enter your ICPAR licence number.']);
    $this->post($route, accessRequest(['phone' => 'call me']))->assertSessionHasErrors(['phone' => 'Enter a phone number with digits only, for example +250 788 000 000.']);
    $this->post($route, accessRequest(['role' => 'investor']))->assertSessionHasErrors('role');

    $this->post($route, accessRequest(['role' => 'auditor', 'organization' => '', 'reference' => 'ICPAR-2041', 'phone' => null, 'note' => null]))
        ->assertSessionHasNoErrors();
    expect(RoleAccessRequest::query()->sole()->only(['role', 'organization', 'reference', 'phone', 'note']))
        ->toBe(['role' => 'auditor', 'organization' => '', 'reference' => 'ICPAR-2041', 'phone' => null, 'note' => null]);
});

it('offers nothing to ask for and refuses a request outside the roles a person may still ask for', function (array $memberships, bool $verified, string $role, array $requestable): void {
    $user = accessRequester($memberships, $verified);

    expect(($this->launcherAccess)($user)['requestable'])->toBe($requestable);
    $this->post(route('identity.access-requests.store'), accessRequest(['role' => $role, 'reference' => 'ICPAR-1']))
        ->assertSessionHasErrors(['role' => "This access can't be requested from your account. Refresh the page to see your apps."]);
    expect(RoleAccessRequest::query()->exists())->toBeFalse();
})->with([
    'an Auditor asking for Business' => [['auditor' => 'active'], true, 'business', []],
    'a Business member asking for Auditor' => [['business' => 'active'], true, 'auditor', []],
    'a pending Business member asking again' => [['business' => 'pending'], true, 'business', []],
    'a person still being verified' => [['investor' => 'active'], false, 'business', []],
    'a revoked Business member asking for Business again' => [['business' => 'revoked'], true, 'business', ['auditor']],
]);

it('refuses a signed-in account with no Party, which has nothing to ask for', function (): void {
    $user = User::factory()->create();

    expect(($this->launcherAccess)($user))->toMatchArray(['requestable' => [], 'sent' => []]);
    $this->post(route('identity.access-requests.store'), accessRequest())->assertSessionHasErrors('role');
});

it('shows staff who grant each role the requests for it, newest first, and refuses everyone else', function (): void {
    $business = accessRequester();
    $this->actingAs($business)->post(route('identity.access-requests.store'), accessRequest())->assertSessionHasNoErrors();
    $this->travel(1)->minutes();
    $auditor = accessRequester([]);
    $this->actingAs($auditor)->post(route('identity.access-requests.store'),
        accessRequest(['role' => 'auditor', 'organization' => 'Ledger & Co', 'reference' => 'ICPAR-2041']))->assertSessionHasNoErrors();

    $staff = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($staff, ['compliance'], 'Access request review.');
    $props = [];
    $this->actingAs($staff)->get(route('staff.access-requests.index'))->assertOk()->assertInertia(function (Assert $page) use (&$props): void {
        $page->component('admin/access-requests');
        $props = $page->toArray()['props'];
    });

    $rows = RoleAccessRequest::query()->orderByDesc('created_at')->get();
    expect($props['roles'])->toBe(['business', 'auditor'])
        ->and($props['links'])->toBe(['business' => ['url' => '/admin/businesses', 'method' => 'get'], 'auditor' => ['url' => '/admin/auditors', 'method' => 'get']])
        ->and($props['nav']['access_requests'])->toBe(['url' => '/admin/access-requests', 'method' => 'get'])
        ->and($props['viewer']['role'])->toBe('compliance')
        ->and($props['requests'])->toBe([
            ['id' => $rows[0]->id, 'role' => 'auditor', 'name' => 'Jean Mugisha', 'email' => $auditor->email, 'organization' => 'Ledger & Co',
                'reference' => 'ICPAR-2041', 'phone' => '+250 788 000 000', 'note' => 'Coffee washing station', 'sent_at' => $rows[0]->created_at->toIso8601String(), 'outcome' => null],
            ['id' => $rows[1]->id, 'role' => 'business', 'name' => 'Jean Mugisha', 'email' => $business->email, 'organization' => 'Kivu Harvest Ltd',
                'reference' => null, 'phone' => '+250 788 000 000', 'note' => 'Coffee washing station', 'sent_at' => $rows[1]->created_at->toIso8601String(), 'outcome' => null],
        ]);

    $treasury = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($treasury, ['treasury'], 'Access request refusal.');
    $this->actingAs($treasury)->get(route('staff.access-requests.index'))->assertForbidden();
    $this->actingAs($treasury)->get(route('staff.dashboard'))->assertInertia(fn (Assert $page): Assert => $page->where('nav.access_requests', null));
});

it('moves a request out of the open queue once staff create the membership it asked for, keeping what was sent', function (): void {
    $business = accessRequester();
    $this->actingAs($business)->post(route('identity.access-requests.store'), accessRequest())->assertSessionHasNoErrors();
    $this->travel(1)->minutes();
    $auditor = accessRequester([]);
    $this->actingAs($auditor)->post(route('identity.access-requests.store'),
        accessRequest(['role' => 'auditor', 'organization' => '', 'reference' => 'ICPAR-2041']))->assertSessionHasNoErrors();
    $this->travel(1)->minutes();
    $later = accessRequester();
    $this->actingAs($later)->post(route('identity.access-requests.store'), accessRequest())->assertSessionHasNoErrors();

    // Staff onboard the first Business (still pending) and verify the Auditor; the latest Business stays open.
    RoleMembership::factory()->create(['party_id' => $business->party_id, 'role' => 'business', 'status' => 'pending']);
    RoleMembership::factory()->create(['party_id' => $auditor->party_id, 'role' => 'auditor', 'status' => 'active']);

    $staff = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($staff, ['compliance'], 'Access request outcomes.');
    $rows = [];
    $this->actingAs($staff)->get(route('staff.access-requests.index'))->assertOk()->assertInertia(function (Assert $page) use (&$rows): void {
        $rows = $page->toArray()['props']['requests'];
    });

    expect(array_map(fn (array $row): array => [$row['email'], $row['outcome']], $rows))->toBe([
        [$later->email, null],
        [$auditor->email, 'active'],
        [$business->email, 'pending'],
    ])->and($rows[2]['organization'])->toBe('Kivu Harvest Ltd')
        ->and(RoleAccessRequest::query()->count())->toBe(3);
});

it('keeps a sent request as it was sent', function (): void {
    $this->actingAs(accessRequester())->post(route('identity.access-requests.store'), accessRequest())->assertSessionHasNoErrors();

    expect(fn () => DB::table('role_access_requests')->update(['note' => 'Changed']))->toThrow(QueryException::class)
        ->and(fn () => DB::table('role_access_requests')->delete())->toThrow(QueryException::class)
        ->and(fn () => DB::table('role_access_requests')->insert(['id' => (string) Str::ulid(), 'party_id' => RoleAccessRequest::query()->value('party_id'),
            'role' => 'investor', 'organization' => '', 'operation_id' => (string) Str::ulid(), 'created_at' => now()]))->toThrow(QueryException::class);
});
