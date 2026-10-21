<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final class StaffPermission
{
    /** @var array<string, list<string>> */
    private const ROLES = [
        'analyst' => ['businesses.view', 'audit.reports.view', 'primary.view'],
        'approver' => ['businesses.view', 'businesses.verify', 'applications.review', 'audit.partners.verify', 'audit.assignments.manage', 'audit.reports.view',
            'disbursements.view', 'disbursements.hold', 'primary.view'],
        'treasury' => ['businesses.view', 'audit.reports.view', 'ledger.view', 'disbursements.view', 'disbursements.hold', 'disbursements.requery', 'reconciliation.view', 'exceptions.view', 'primary.view', 'payments.view', 'health.view'],
        'compliance' => ['businesses.view', 'businesses.verify', 'investors.verify', 'audit.partners.verify', 'audit.reports.view', 'consent.documents.record', 'ledger.view',
            'disbursements.view', 'disbursements.hold', 'reconciliation.view', 'exceptions.view', 'messaging.view', 'primary.view', 'payments.view', 'complaints.answer', 'health.view'],
        // No superadmin bypass (§10.6, §11.1): disbursements are view-only here, never held or released.
        'superadmin' => ['businesses.view', 'businesses.verify', 'investors.verify', 'applications.review', 'audit.partners.verify', 'audit.assignments.manage', 'audit.reports.view', 'consent.documents.record', 'ledger.view',
            'disbursements.view', 'staging.mail.testers.manage', 'staff.manage', 'reconciliation.view', 'exceptions.view', 'messaging.view', 'primary.view', 'payments.view', 'complaints.answer', 'health.view'],
    ];

    /** The console's roles from most to least privileged, as the matrix lists them. */
    private const ORDER = ['superadmin', 'compliance', 'approver', 'treasury', 'analyst'];

    /**
     * Every capability a staff role can grant, in the order the roles first grant them (the most
     * privileged first), with the roles that grant it. Every enabled operator may open the console.
     *
     * @return list<array{key: string, roles: list<string>}>
     */
    public static function matrix(): array
    {
        $matrix = ['admin.open' => self::ORDER];
        foreach (self::ORDER as $role) {
            foreach (self::ROLES[$role] as $permission) {
                $matrix[$permission][] = $role;
            }
        }

        return array_map(fn (string $key, array $roles): array => ['key' => $key, 'roles' => array_values(array_unique($roles))], array_keys($matrix), array_values($matrix));
    }

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
