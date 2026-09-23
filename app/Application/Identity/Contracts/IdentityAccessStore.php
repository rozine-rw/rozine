<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use Closure;

/** @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy */
interface IdentityAccessStore
{
    /** @return array<string, mixed> */
    public function configureOperator(int $userId, bool $enabled, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function resolvePerson(int $actorId, int $userId, string $identityReference, string $evidenceReference, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function changeMembership(int $actorId, string $partyId, string $role, string $status, int $expectedRevision, string $evidenceReference, string $reason, string $requestId): array;

    public function selectRole(int $userId, string $role, int $expectedRevision, string $requestId): void;

    /**
     * @template TResult
     *
     * @param  Closure(AccessSnapshot): TResult  $operation
     * @return TResult
     */
    public function withActiveRole(int $userId, string $role, ?string $recordPartyId, ?int $expectedContext, Closure $operation): mixed;
}
