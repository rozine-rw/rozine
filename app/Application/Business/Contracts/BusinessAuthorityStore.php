<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use Closure;

/**
 * @phpstan-import-type Profile from \App\Domain\Business\MandateAuthority
 * @phpstan-import-type Terms from \App\Domain\Business\MandateAuthority
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 *
 * @phpstan-type AuditContext array{business: Business, candidate_ids: list<string>, actor_party_id: string|null, current: bool, role_ties: array<string, array{current: bool, ended_at: string|null}>}
 * @phpstan-type Business array{id: string, entity_kind: string, entity_party_id: string, profile: Profile, revision: int, mandate_version: int, mandate: Terms}
 */
interface BusinessAuthorityStore
{
    /** @return array{ids: list<string>, next_cursor: string|null} */
    public function discover(int $userId, int $contextRevision, ?string $before = null, int $limit = 20): array;

    /**
     * @param  Profile  $profile
     * @param  Terms  $terms
     * @return array<string, mixed>
     */
    public function configure(int $actorId, string $entityKind, string $entityPartyId, array $profile, array $terms, int $expectedRevision, string $evidenceReference, string $reason, string $requestId): array;

    /**
     * @template TResult
     *
     * @param  Closure(Business, AccessSnapshot): TResult  $operation
     * @param  list<string>  $additionalPartyIds
     * @return TResult
     */
    public function withAuthority(int $userId, int $contextRevision, string $businessId, string $permission, ?int $mandateVersion, Closure $operation, array $additionalPartyIds = []): mixed;

    /**
     * @template TResult
     *
     * @param  Closure(Business): TResult  $operation
     * @param  list<string>  $additionalPersonPartyIds
     * @return TResult
     */
    public function withReview(int $actorId, string $businessId, bool $requireVerified, Closure $operation, array $additionalPersonPartyIds = []): mixed;

    /**
     * @template TResult
     *
     * @param  list<string>  $candidateIds
     * @param  Closure(AuditContext): TResult  $operation
     * @return TResult
     */
    public function withAudit(?int $userId, ?int $contextRevision, string $businessId, array $candidateIds, bool $requireVerified, Closure $operation): mixed;
}
