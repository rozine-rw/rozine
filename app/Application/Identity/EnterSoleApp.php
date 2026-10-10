<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\IdentityViolation;
use Illuminate\Support\Str;

/**
 * Opens the one app an account can use, so it skips the launcher. A person with exactly one
 * selectable role goes straight into it, the role selected first when it is not active (as the
 * launcher would select it); an enabled staff account with no role opens Admin. Anyone with two
 * or more apps, or with none yet, still chooses in the launcher. The selection is the ordinary
 * audited `identity.select_role` command at the current context revision, so a stale or refused
 * selection leaves the launcher to explain why.
 */
final class EnterSoleApp
{
    public function __construct(private GetIdentityContext $identity, private SelectActiveRole $select, private GetStaffAccess $staff) {}

    /** The app to open without the launcher: `investor`, `business`, `auditor`, `admin`, or null to choose. */
    public function handle(int $userId): ?string
    {
        $context = $this->identity->handle($userId);
        $staff = $this->staff->handle($userId);
        /** @var list<string> $roles */
        $roles = $context['available_roles'];
        /** @var list<string> $staffActions */
        $staffActions = $staff['allowed_actions'];
        $admin = $staff['can_open_admin'] === true && in_array('admin.open', $staffActions, true);

        if ($roles === []) {
            return $admin ? 'admin' : null;
        }
        if ($admin || count($roles) !== 1) {
            return null;
        }

        return $context['active_role'] === $roles[0] || $this->select($userId, $roles[0], $context) ? $roles[0] : null;
    }

    /**
     * Selects `$role` when it is the account's only app and not already active. True only when it
     * selected it now, so a page that still refuses after a selection is never retried in a loop.
     */
    public function selectSole(int $userId, string $role): bool
    {
        $context = $this->identity->handle($userId);
        if ($context['available_roles'] !== [$role] || $context['active_role'] === $role
            || $this->staff->handle($userId)['can_open_admin'] === true) {
            return false;
        }

        return $this->select($userId, $role, $context);
    }

    /** @param array<string, mixed> $context */
    private function select(int $userId, string $role, array $context): bool
    {
        /** @var list<string> $actions */
        $actions = $context['allowed_actions'];
        if (! in_array('identity.select_role', $actions, true)) {
            return false;
        }
        try {
            $this->select->handle($userId, $role, (int) $context['context_revision'], (string) Str::uuid());
        } catch (IdentityViolation) {
            return false;
        }

        return true;
    }
}
