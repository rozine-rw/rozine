<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\RoleAccessRequests;
use App\Domain\Identity\IdentityViolation;

/**
 * The console's access requests (#616): Business requests for staff who onboard businesses
 * (`businesses.verify`) and Auditor requests for staff who verify Audit Partners
 * (`audit.partners.verify`), newest first. Staff holding neither are refused.
 *
 * @phpstan-import-type StaffRow from RoleAccessRequests
 */
final readonly class BrowseRoleAccessRequests
{
    /** Which permission shows which role's requests. */
    public const PERMISSIONS = ['business' => 'businesses.verify', 'auditor' => 'audit.partners.verify'];

    public function __construct(private RoleAccessRequests $requests, private AuthorizeStaffPermission $staff) {}

    /** @return array{roles: list<'business'|'auditor'>, requests: list<StaffRow>} */
    public function handle(int $staffId): array
    {
        $permissions = $this->staff->permissions($staffId);
        $roles = array_keys(array_filter(self::PERMISSIONS, fn (string $permission): bool => in_array($permission, $permissions, true)));
        if ($roles === []) {
            throw new IdentityViolation('STAFF_PERMISSION_REQUIRED');
        }

        return ['roles' => $roles, 'requests' => $this->requests->forStaff($roles)];
    }
}
