<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use Closure;

/** @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy */
final class AuthorizeEntityRole
{
    public function __construct(private IdentityAccessStore $access) {}

    /**
     * @template TResult
     *
     * @param  list<string>  $personPartyIds
     * @param  Closure(AccessSnapshot): TResult  $operation
     * @return TResult
     */
    public function handle(int $userId, string $role, int $expectedContext, string $entityKind, string $entityPartyId, array $personPartyIds, Closure $operation, ?string $registryReference = null): mixed
    {
        return $this->access->withEntityRole($userId, $role, $expectedContext, $entityKind, $entityPartyId, $personPartyIds, $operation, $registryReference);
    }
}
