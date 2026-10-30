<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final class StaffPermission
{
    /**
     * The design's capabilities no role holds, and why: `not_built` until the console has the
     * command (a participant freeze, capacity override, record edit, ledger correction as a linked
     * entry under ADM-R02, and a policy change, which ships as a release under ADM-R05 until policy
     * is versioned data), and `by_rule` where BRS v2.0 bars every role, Superadmin included
     * (ledger and audit entries are never deleted).
     */
    private const WITHHELD = ['accounts.freeze' => 'not_built', 'capacity.adjust' => 'not_built', 'records.edit' => 'not_built',
        'ledger.correct' => 'not_built', 'policies.change' => 'not_built', 'records.delete' => 'by_rule'];

    /** The console's roles from most to least privileged, as the matrix lists them. */
    private const ORDER = ['superadmin', 'compliance', 'approver', 'treasury', 'analyst'];

    /** @return list<string> */
    public static function roles(): array
    {
        return self::ORDER;
    }

    /** @return list<array{key: string, roles: list<string>, withheld: string}> */
    public static function withheld(): array
    {
        return array_map(fn (string $key, string $why): array => ['key' => $key, 'roles' => [], 'withheld' => $why],
            array_keys(self::WITHHELD), array_values(self::WITHHELD));
    }

    /**
     * @param  list<string>  $roles
     * @return list<string>
     */
    public static function normalizeRoles(array $roles): array
    {
        foreach ($roles as $role) {
            if (! in_array($role, self::ORDER, true)) {
                throw new IdentityViolation('STAFF_ROLE_INVALID', 422);
            }
        }
        $roles = array_values(array_unique($roles));
        sort($roles);

        return $roles;
    }
}
