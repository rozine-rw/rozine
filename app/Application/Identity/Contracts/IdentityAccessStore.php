<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use Closure;

/** @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy */
interface IdentityAccessStore
{
    /** @return array<string, mixed> */
    public function configureOperator(int $userId, bool $enabled, string $reason, string $requestId): array;

    /**
     * @param  list<string>  $roles
     * @return array<string, mixed>
     */
    public function configureStaff(int $userId, bool $enabled, string $reason, string $requestId, array $roles = []): array;

    /** @return array<string, mixed> */
    public function staffAccess(int $userId, bool $required = false): array;

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $operation
     * @return TResult
     */
    public function withStaffPermission(int $userId, string $permission, Closure $operation): mixed;

    /** @return array<string, mixed> */
    public function bookmark(int $userId, string $role): array;

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function saveBookmark(int $userId, string $role, string $route, array $parameters, array $query, int $expectedContext, string $requestId): array;

    /** @return array<string, mixed> */
    public function resolvePerson(int $actorId, int $userId, string $identityReference, string $evidenceReference, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function resolveOrganization(int $actorId, string $registryReference, string $evidenceReference, string $reason, string $requestId): array;

    /**
     * @template TResult
     *
     * @param  list<string>  $personPartyIds
     * @param  Closure(): TResult  $operation
     * @return TResult
     */
    public function withVerifiedParties(string $entityKind, string $entityPartyId, array $personPartyIds, Closure $operation, ?string $registryReference = null): mixed;

    /**
     * @template TResult
     *
     * @param  list<string>  $personPartyIds
     * @param  Closure(AccessSnapshot): TResult  $operation
     * @return TResult
     */
    public function withEntityRole(int $userId, string $role, int $expectedContext, string $entityKind, string $entityPartyId, array $personPartyIds, Closure $operation, ?string $registryReference = null): mixed;

    /**
     * @template TResult
     *
     * @param  list<string>  $personPartyIds
     * @param  list<string>  $candidateIds
     * @param  Closure(list<string>, string|null, bool): TResult  $operation
     * @param  int|null  $userId  Null is reserved for the trusted offer-expiry worker; a participant context always requires an actor.
     * @return TResult
     */
    public function withAuditAccess(?int $userId, ?int $contextRevision, string $entityKind, string $entityPartyId, array $personPartyIds, array $candidateIds, bool $requireVerified, Closure $operation, ?string $registryReference = null): mixed;

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
