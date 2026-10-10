<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Http\Controllers\StaffSectionController;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/** @param  list<string>  $roles */
function sectionStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Console section assignment.', (string) Str::uuid(), $roles);

    return $user;
}

test('every design section opens the console frame with its empty state for any staff member', function (string $slug, string $section): void {
    $this->actingAs(sectionStaff(['analyst']))->get(route('staff.sections.show', ['section' => $slug]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/section')->where('section', $section)->where('search', '')
            ->where('viewer.role', 'analyst')->where('badges', ['applications' => null, 'disbursements' => null])
            ->where("nav.{$section}.url", '/admin/'.$slug)->where('nav.investors', null)->where('nav.payments.url', '/admin/payments')
            ->where('nav.compliance.url', '/admin/compliance')
            ->where('nav.businesses.url', '/admin/businesses')->where('nav.auditors', null));
})->with(fn (): array => array_map(fn (string $slug): array => [$slug, StaffSectionController::SECTIONS[$slug]],
    array_combine(array_keys(StaffSectionController::SECTIONS), array_keys(StaffSectionController::SECTIONS))));

test('the frame names the most privileged role, and Payments and Compliance open their live queues for whoever may see them', function (): void {
    $this->actingAs(sectionStaff(['approver', 'superadmin']))->get(route('staff.sections.show', ['section' => 'notes']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'superadmin')
            ->where('nav.payments.url', '/admin/disbursements')->where('nav.compliance.url', '/admin/investor-verifications')
            ->where('nav.investors.url', '/admin/investors')
            ->where('nav.businesses.url', '/admin/businesses')->where('nav.auditors.url', '/admin/auditors'));
});

test('Compliance opens the investor identity review queue for compliance officers, and the design page otherwise', function (): void {
    $this->actingAs(sectionStaff(['compliance']))->get(route('staff.sections.show', ['section' => 'notes']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('nav.compliance.url', '/admin/investor-verifications'));
    $this->actingAs(sectionStaff(['compliance']))->getJson(route('api.v1.staff.investor-verifications.index'))->assertOk()
        ->assertJsonPath('data.nav.compliance.url', '/api/v1/staff/investor-verifications');
    $this->actingAs(sectionStaff(['approver']))->get(route('staff.sections.show', ['section' => 'notes']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('nav.compliance.url', '/admin/compliance'));
});

test('the Business directory and Audit Partner network are live screens, not pending sections', function (): void {
    expect(StaffSectionController::SECTIONS)->not->toHaveKeys(['businesses', 'auditors']);
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/businesses')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/parties')->where('kind', 'business'));
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/auditors')->assertForbidden();
});

test('participants and guests cannot open a console section, and unknown sections do not exist', function (): void {
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.sections.show', ['section' => 'notes']))
        ->assertForbidden()->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', 'STAFF_ACCESS_REQUIRED'));
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/everything')->assertNotFound();
    auth()->logout();
    $this->get(route('staff.sections.show', ['section' => 'notes']))->assertRedirect(route('login'));
});

test('Oversight lists the design\'s Reports, which opens its section page from the sidebar and the Audit desk command', function (): void {
    expect(StaffSectionController::SECTIONS)->toHaveKey('reports')->and(array_slice(array_keys(StaffSectionController::SECTIONS), 3, 3))
        ->toBe(['reports', 'risk', 'compliance']);
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/reports')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/section')->where('section', 'reports')
            ->where('nav.reports', ['url' => '/admin/reports', 'method' => 'get'])
            ->where('nav.policies', ['url' => '/admin/policies', 'method' => 'get']));
    $this->actingAs(sectionStaff(['analyst']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/today')
            ->where('nav.reports', ['url' => '/admin/reports', 'method' => 'get'])
            ->where('nav.policies', ['url' => '/admin/policies', 'method' => 'get']));
});
