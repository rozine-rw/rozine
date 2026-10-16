<?php

declare(strict_types=1);

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Identity\StaffPermission;
use App\Models\User;
use Illuminate\Support\Str;

/*
 * The explicit G-OPS-01 disbursement mapping (#96 answer 3), as amended by BRS v2.0 SVC-R01 and
 * ADM-R02: funded campaigns pay automatically, so no role authorizes or approves a disbursement any
 * more. Superadmin views only: there is no bypass of the hold.
 */

const DISBURSEMENT_PERMISSIONS = ['disbursements.view', 'disbursements.hold', 'disbursements.requery'];

const RETIRED_DISBURSEMENT_PERMISSIONS = ['disbursements.authorize', 'disbursements.approve'];

it('maps each staff role to exactly its disbursement permissions', function (string $role, array $expected): void {
    $granted = array_values(array_intersect(StaffPermission::forRoles([$role]), DISBURSEMENT_PERMISSIONS));
    sort($granted);
    sort($expected);
    expect($granted)->toBe($expected)
        ->and(StaffPermission::forRoles([$role]))->not->toContain(...RETIRED_DISBURSEMENT_PERMISSIONS);
})->with([
    'treasury holds and requeries' => ['treasury', ['disbursements.view', 'disbursements.hold', 'disbursements.requery']],
    'approver holds' => ['approver', ['disbursements.view', 'disbursements.hold']],
    'compliance holds' => ['compliance', ['disbursements.view', 'disbursements.hold']],
    'superadmin only views' => ['superadmin', ['disbursements.view']],
    'analyst has none' => ['analyst', []],
]);

it('keeps every unrelated permission each role already had', function (): void {
    expect(StaffPermission::forRoles(['superadmin']))->toContain('admin.open', 'businesses.view', 'businesses.verify', 'applications.review',
        'audit.partners.verify', 'audit.assignments.manage', 'audit.reports.view', 'consent.documents.record')
        ->and(StaffPermission::forRoles(['approver']))->toContain('businesses.verify', 'applications.review', 'audit.assignments.manage')
        ->and(StaffPermission::forRoles(['treasury']))->toContain('businesses.view', 'audit.reports.view')
        ->and(StaffPermission::forRoles(['compliance']))->toContain('consent.documents.record');
});

it('unions roles for one user without ever granting a retired maker or checker permission', function (): void {
    expect(array_values(array_intersect(StaffPermission::forRoles(['treasury', 'approver']), DISBURSEMENT_PERMISSIONS)))
        ->toEqualCanonicalizing(DISBURSEMENT_PERMISSIONS)
        ->and(StaffPermission::forRoles(['analyst', 'approver', 'compliance', 'superadmin', 'treasury']))->not->toContain(...RETIRED_DISBURSEMENT_PERMISSIONS)
        ->and(StaffPermission::forRoles(['superadmin', 'compliance']))->toContain('disbursements.hold')->not->toContain('disbursements.requery');
});

it('reads permissions without a lock and reports a lapsed authority under one', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    $configure = app(ConfigureStaffAccess::class);
    $staff = app(AuthorizeStaffPermission::class);
    $configure->handle($user->id, true, 'Treasury operator.', (string) Str::uuid(), ['treasury']);
    expect($staff->permissions($user->id))->toContain('disbursements.requery', 'disbursements.hold')
        ->and($staff->currentlyHolds($user->id, 'disbursements.requery'))->toBeTrue()
        ->and($staff->currentlyHolds($user->id, 'disbursements.approve'))->toBeFalse();
    $configure->handle($user->id, true, 'Moved to compliance.', (string) Str::uuid(), ['compliance']);
    expect($staff->currentlyHolds($user->id, 'disbursements.requery'))->toBeFalse()
        ->and($staff->currentlyHolds($user->id, 'disbursements.hold'))->toBeTrue();
    $user->forceFill(['two_factor_confirmed_at' => null])->save();
    expect($staff->permissions($user->id))->toBe([])
        ->and($staff->currentlyHolds($user->id, 'disbursements.hold'))->toBeFalse();
});
