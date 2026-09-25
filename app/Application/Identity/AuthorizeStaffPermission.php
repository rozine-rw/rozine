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
