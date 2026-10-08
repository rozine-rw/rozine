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
            ->where("nav.{$section}.url", '/admin/'.$slug)->where('nav.investors', null)->where('nav.payments.url', '/admin/payments'));
})->with(fn (): array => array_map(fn (string $slug): array => [$slug, StaffSectionController::SECTIONS[$slug]],
    array_combine(array_keys(StaffSectionController::SECTIONS), array_keys(StaffSectionController::SECTIONS))));

test('the frame names the most privileged role, and Payments opens disbursements for whoever may see them', function (): void {
    $this->actingAs(sectionStaff(['approver', 'superadmin']))->get(route('staff.sections.show', ['section' => 'notes']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'superadmin')
            ->where('nav.payments.url', '/admin/disbursements')->where('nav.investors.url', '/admin/investors'));
});

test('participants and guests cannot open a console section, and unknown sections do not exist', function (): void {
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.sections.show', ['section' => 'notes']))
        ->assertForbidden();
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/everything')->assertNotFound();
    auth()->logout();
    $this->get(route('staff.sections.show', ['section' => 'notes']))->assertRedirect(route('login'));
});
