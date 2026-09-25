<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final class StaffPermission
{
    /** @var array<string, list<string>> */
    private const ROLES = [
        'analyst' => ['businesses.view', 'audit.reports.view'],
        'approver' => ['businesses.view', 'businesses.verify', 'applications.review', 'audit.partners.verify', 'audit.assignments.manage', 'audit.reports.view'],
        'treasury' => ['businesses.view', 'audit.reports.view'],
        'compliance' => ['businesses.view', 'businesses.verify', 'audit.partners.verify', 'audit.reports.view', 'consent.documents.record'],
        'superadmin' => ['businesses.view', 'businesses.verify', 'applications.review', 'audit.partners.verify', 'audit.assignments.manage', 'audit.reports.view', 'consent.documents.record'],
    ];

    /**
     * @param  list<string>  $roles
     * @return list<string>
     */
    public static function normalizeRoles(array $roles): array
    {
        foreach ($roles as $role) {
            if (! array_key_exists($role, self::ROLES)) {
                throw new IdentityViolation('STAFF_ROLE_INVALID', 422);
            }
        }
        $roles = array_values(array_unique($roles));
        sort($roles);

        return $roles;
    }

    /**
     * @param  list<string>  $roles
     * @return list<string>
     */
    public static function forRoles(array $roles): array
    {
        $permissions = ['admin.open'];
        foreach (self::normalizeRoles($roles) as $role) {
            $permissions = array_merge($permissions, self::ROLES[$role]);
        }

        return array_values(array_unique($permissions));
    }
}
