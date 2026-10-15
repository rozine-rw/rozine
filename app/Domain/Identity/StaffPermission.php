<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final class StaffPermission
{
    /** @var array<string, list<string>> */
    private const ROLES = [
        'analyst' => ['businesses.view', 'audit.reports.view', 'primary.view'],
        'approver' => ['businesses.view', 'businesses.verify', 'applications.review', 'audit.partners.verify', 'audit.assignments.manage', 'audit.reports.view',
            'disbursements.view', 'disbursements.approve', 'disbursements.hold', 'primary.view'],
        'treasury' => ['businesses.view', 'audit.reports.view', 'disbursements.view', 'disbursements.authorize', 'disbursements.hold', 'disbursements.requery', 'reconciliation.view', 'exceptions.view', 'primary.view', 'payments.view', 'health.view'],
        'compliance' => ['businesses.view', 'businesses.verify', 'investors.verify', 'audit.partners.verify', 'audit.reports.view', 'consent.documents.record',
            'disbursements.view', 'disbursements.hold', 'reconciliation.view', 'exceptions.view', 'messaging.view', 'primary.view', 'payments.view', 'complaints.answer', 'health.view'],
        // No superadmin bypass (§10.6, §11.1): disbursements are view-only here, never authorize or approve.
        'superadmin' => ['businesses.view', 'businesses.verify', 'investors.verify', 'applications.review', 'audit.partners.verify', 'audit.assignments.manage', 'audit.reports.view', 'consent.documents.record',
            'disbursements.view', 'staging.mail.testers.manage', 'reconciliation.view', 'exceptions.view', 'messaging.view', 'primary.view', 'payments.view', 'complaints.answer', 'health.view'],
    ];

    /**
     * Proposed `ledger.view` allocation (#222), held until Robert's affirmative #99 P2 signoff:
     * granted only once that signoff is recorded in configuration.
     *
     * @var list<string>
     */
    private const LEDGER_VIEW_ROLES = ['treasury', 'compliance', 'superadmin'];

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
    public static function forRoles(array $roles, bool $ledgerViewSignedOff = false): array
    {
        $permissions = ['admin.open'];
        foreach (self::normalizeRoles($roles) as $role) {
            $permissions = array_merge($permissions, self::ROLES[$role]);
            if ($ledgerViewSignedOff && in_array($role, self::LEDGER_VIEW_ROLES, true)) {
                $permissions[] = 'ledger.view';
            }
        }

        return array_values(array_unique($permissions));
    }
}
