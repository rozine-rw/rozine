<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\IdentityViolation;
use Closure;

final class AuthorizeStaffPermission
{
    public function __construct(private IdentityAccessStore $access) {}

    /** Read-only preflight. Protected effects must still use handle to retain authorization. */
    public function check(int $userId, string $permission): void
    {
        $access = $this->access->staffAccess($userId, true, false);
        if (! in_array($permission, $access['allowed_actions'], true)) {
            throw new IdentityViolation('STAFF_PERMISSION_REQUIRED');
        }
    }

    /**
     * The staff member's current permissions without locking, for read-only projections such as
     * `allowed_actions`; empty when they may not open the console at all. Never authority for an
     * effect: commands use `handle` or `currentlyHolds`.
     *
     * @return list<string>
     */
    public function permissions(int $userId): array
    {
        /** @var list<string> $allowed */
        $allowed = $this->access->staffAccess($userId, false, false)['allowed_actions'];

        return $allowed;
    }

    /**
     * Locks another recorded staff member's account row (a maker or checker, in the caller's lock
     * order) and reports whether they still hold this permission, with current MFA and an enabled
     * staff account. It refuses nothing itself: the caller decides what a lapsed authority means.
     */
    public function currentlyHolds(int $userId, string $permission): bool
    {
        return in_array($permission, $this->access->staffAccess($userId, false, true)['allowed_actions'], true);
    }

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $operation
     * @return TResult
     */
    public function handle(int $userId, string $permission, Closure $operation): mixed
    {
        return $this->access->withStaffPermission($userId, $permission, $operation);
    }
}
