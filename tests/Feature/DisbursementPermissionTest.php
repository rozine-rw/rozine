<?php

declare(strict_types=1);

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Identity\StaffPermission;
use App\Models\User;
use Illuminate\Support\Str;

/*
 * The explicit G-OPS-01 disbursement mapping (#96 answer 3), pinned before any route exists.
 * Superadmin views only: there is no bypass of maker/checker separation.
 */

const DISBURSEMENT_PERMISSIONS = ['disbursements.view', 'disbursements.authorize', 'disbursements.approve', 'disbursements.hold', 'disbursements.requery'];

it('maps each staff role to exactly its disbursement permissions', function (string $role, array $expected): void {
    $granted = array_values(array_intersect(StaffPermission::forRoles([$role]), DISBURSEMENT_PERMISSIONS));
    sort($granted);
    sort($expected);
    expect($granted)->toBe($expected);
})->with([
    'treasury authorizes, holds and requeries' => ['treasury', ['disbursements.view', 'disbursements.authorize', 'disbursements.hold', 'disbursements.requery']],
    'approver approves and holds' => ['approver', ['disbursements.view', 'disbursements.approve', 'disbursements.hold']],
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

it('unions roles for one user, which can then hold both maker and checker permissions', function (): void {
    expect(array_values(array_intersect(StaffPermission::forRoles(['treasury', 'approver']), DISBURSEMENT_PERMISSIONS)))
        ->toEqualCanonicalizing(DISBURSEMENT_PERMISSIONS)
        ->and(StaffPermission::forRoles(['superadmin', 'compliance']))->not->toContain('disbursements.authorize', 'disbursements.approve');
});

it('reads permissions without a lock and reports a lapsed authority under one', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    $configure = app(ConfigureStaffAccess::class);
    $staff = app(AuthorizeStaffPermission::class);
    $configure->handle($user->id, true, 'Treasury maker.', (string) Str::uuid(), ['treasury']);
    expect($staff->permissions($user->id))->toContain('disbursements.authorize')
        ->and($staff->currentlyHolds($user->id, 'disbursements.authorize'))->toBeTrue()
        ->and($staff->currentlyHolds($user->id, 'disbursements.approve'))->toBeFalse();
    $configure->handle($user->id, true, 'Moved to compliance.', (string) Str::uuid(), ['compliance']);
    expect($staff->currentlyHolds($user->id, 'disbursements.authorize'))->toBeFalse();
    $user->forceFill(['two_factor_confirmed_at' => null])->save();
    expect($staff->permissions($user->id))->toBe([])
        ->and($staff->currentlyHolds($user->id, 'disbursements.hold'))->toBeFalse();
});
