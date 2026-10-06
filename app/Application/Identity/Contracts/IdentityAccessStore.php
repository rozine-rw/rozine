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
    public function staffAccess(int $userId, bool $required = false, bool $lock = true): array;

    /**
     * Whether a staff member holds this permission now and has held it without a gap since the
     * given instant: no recorded staff-access change since then removed it, and MFA was not
     * re-confirmed since then. Optionally locks the account row in the caller's order.
     */
    public function staffPermissionContinuousSince(int $userId, string $permission, string $since, bool $lock = true): bool;

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

    /**
     * Compliance's approval of a person's own Investor identity submission (`investors.verify`). It runs
     * the verified-person writer that resolvePerson uses, refusing a reference already verified for
     * another Party, activates the Investor membership through the membership-transition core, and
     * then runs `$approve` with the verified Party id, all in one transaction. Lock order: users,
     * identity advisory lock, parties, then whatever `$approve` locks (the submission).
     *
     * @param  Closure(string): array<string, mixed>  $approve
     * @return array<string, mixed>
     */
    public function verifyInvestor(int $actorId, int $userId, string $identityReference, string $evidenceReference, string $reason, string $requestId, Closure $approve): array;

    /**
     * Records, as an identity operator, the verified person behind a staff account; a null reference
     * revokes the current link. A staff account never holds a Party, so this is the only staff-person
     * resolution. Each change is an append-only revision recorded in the identity audit log.
     *
     * @return array<string, mixed>
     */
    public function recordStaffPerson(int $actorId, int $staffUserId, ?string $identityReference, string $evidenceReference, string $reason, string $requestId): array;

    /**
     * The verified person behind a staff account, read without row locks: null when the account is
     * unresolved or its current link is revoked; otherwise the current link revision's id and that
     * person's marketplace Party, which is null when the person holds none.
     *
     * @return array{resolution_id: string, party_id: string|null}|null
     */
    public function staffPerson(int $staffUserId): ?array;

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
     * @param  list<string>  $additionalPartyIds  Locked with the required parties without granting entity authority.
     * @param  Closure(AccessSnapshot): TResult  $operation
     * @return TResult
     */
    public function withEntityRole(int $userId, string $role, int $expectedContext, string $entityKind, string $entityPartyId, array $personPartyIds, Closure $operation, ?string $registryReference = null, array $additionalPartyIds = []): mixed;

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
