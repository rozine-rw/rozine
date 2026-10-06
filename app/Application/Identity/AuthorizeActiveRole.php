<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use Closure;

/** @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy */
final class AuthorizeActiveRole
{
    public function __construct(private IdentityAccessStore $access, private GetIdentityContext $identity) {}

    /** @return array<string, mixed> */
    public function context(int $userId, string $role, ?int $expectedContext = null): array
    {
        return $this->handle($userId, $role, null, $expectedContext,
            fn (array $identity): array => $this->identity->fromSnapshot($identity));
    }

    /**
     * Locks the actor and Party through the operation, including any writes it performs.
     *
     * @template TResult
     *
     * @param  Closure(AccessSnapshot): TResult  $operation
     * @return TResult
     */
    public function handle(int $userId, string $role, ?string $recordPartyId, ?int $expectedContext, Closure $operation): mixed
    {
        return $this->access->withActiveRole($userId, $role, $recordPartyId, $expectedContext, $operation);
    }
}
