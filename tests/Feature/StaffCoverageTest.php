<?php

declare(strict_types=1);

use App\Application\Auditor\SetAuditorAvailability;
use App\Http\Resources\StaffCoverageResource;
use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\StaffFixture;

/*
 * Partner coverage (MVP-ADMIN-SCR-07) from live records: each district a Business operates in,
 * with the Audit Partners dispatch could reach there (BRS AUD-R01, AUD-R02) and its open audits.
 */

/** @param list<string> $roles */
function coverageStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($user, $roles, 'Coverage assignment.');

    return $user;
}

beforeEach(function (): void {
    $this->freezeSecond();
    $this->page = function (User $staff, array $query = []): array {
        $props = [];
        $this->actingAs($staff)->get(route('staff.coverage.index', $query))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('admin/coverage');
                $props = $page->toArray()['props'];
            });

        return $props;
    };
});

it('counts the partners dispatch could reach in each Business district and its open audits, until none is taking work', function (): void {
    $fixture = AuditAssignmentFixture::make();
    $district = (string) BusinessProfile::query()->whereKey($fixture['business'])->sole()->profile['district'];
    $province = (string) array_key_first(array_filter(config()->array('rwanda.districts'), fn (mixed $names): bool => is_array($names) && in_array($district, $names, true)));
    $staff = coverageStaff(['compliance']);
    $row = fn (int $partners, int $audits, string $capacity): array => ['id' => 'dst_'.Str::lower($district), 'district' => $district, 'province' => $province,
        'active_partners' => $partners, 'audits_open' => $audits, 'capacity' => $capacity,
        'link' => $partners > 0 ? ['url' => '/admin/auditors', 'method' => 'get'] : null];
    $stats = fn (int $uncovered, int $audits): array => [['key' => 'districts', 'value' => ['kind' => 'count', 'value' => 1]],
        ['key' => 'uncovered', 'value' => ['kind' => 'count', 'value' => $uncovered]], ['key' => 'audits_open', 'value' => ['kind' => 'count', 'value' => $audits]]];

    $props = ($this->page)($staff);
    expect($props)->toMatchArray(['contract_version' => 'staff-coverage-v1', 'allowed_actions' => [], 'search' => ''])
        ->and($props['districts'])->toBe([$row(2, 0, 'covered')])->and($props['stats'])->toBe($stats(0, 0))
        ->and($props['nav']['coverage'])->toBe(['url' => '/admin/coverage', 'method' => 'get']);

    AuditAssignmentFixture::request($fixture);
    app(SetAuditorAvailability::class)->handle($fixture['partners'][0]['user']->id, 1, 3, false, (string) Str::uuid());
    expect(($this->page)($staff)['districts'])->toBe([$row(1, 1, 'covered')]);

    app(SetAuditorAvailability::class)->handle($fixture['partners'][1]['user']->id, 1, 3, false, (string) Str::uuid());
    $props = ($this->page)($staff);
    expect($props['districts'])->toBe([$row(0, 1, 'uncovered')])->and($props['stats'])->toBe($stats(1, 1))
        ->and(($this->page)($staff, ['q' => Str::lower($province)])['districts'])->toHaveCount(1)
        ->and(($this->page)($staff, ['q' => 'Atlantis']))->toMatchArray(['districts' => [], 'search' => 'Atlantis']);
});

it('shows no district before any Business is onboarded', function (): void {
    $props = ($this->page)(coverageStaff(['superadmin']));

    expect($props['districts'])->toBe([])->and(array_column(array_column($props['stats'], 'value'), 'value'))->toBe([0, 0, 0])
        ->and((new StaffCoverageResource(['districts' => [], 'search' => '', 'roles' => ['analyst'], 'permissions' => []]))->resolve(request())['nav']['coverage'])->toBeNull();
});

it('is for staff who verify Audit Partners, and over API v1 with its own ability', function (): void {
    $this->actingAs(coverageStaff(['analyst']))->get(route('staff.coverage.index'))->assertForbidden();
    $this->actingAs(coverageStaff(['compliance']))->get(route('staff.coverage.index', ['q' => str_repeat('a', 121)]))->assertSessionHasErrors('q');

    $staff = coverageStaff(['compliance']);
    Sanctum::actingAs($staff, ['staff:coverage:read']);
    $this->getJson('/api/v1/staff/coverage')->assertOk()->assertJsonPath('data.contract_version', 'staff-coverage-v1')
        ->assertJsonPath('data.nav.coverage.url', '/api/v1/staff/coverage');
    Sanctum::actingAs($staff, ['staff:auditors:read']);
    $this->getJson('/api/v1/staff/coverage')->assertForbidden();
});
