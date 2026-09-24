<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use Closure;

final class AuthorizeStaffPermission
{
    public function __construct(private IdentityAccessStore $access) {}

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
