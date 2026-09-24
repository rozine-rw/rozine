<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use Closure;

/**
 * @phpstan-import-type Profile from \App\Domain\Business\MandateAuthority
 * @phpstan-import-type Terms from \App\Domain\Business\MandateAuthority
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 *
 * @phpstan-type Business array{id: string, entity_kind: string, entity_party_id: string, profile: Profile, revision: int, mandate_version: int, mandate: Terms}
 */
interface BusinessAuthorityStore
{
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
     * @return TResult
     */
    public function withAuthority(int $userId, int $contextRevision, string $businessId, string $permission, ?int $mandateVersion, Closure $operation): mixed;

    /**
     * @template TResult
     *
     * @param  Closure(Business): TResult  $operation
     * @param  list<string>  $additionalPersonPartyIds
     * @return TResult
     */
    public function withReview(int $actorId, string $businessId, bool $requireVerified, Closure $operation, array $additionalPersonPartyIds = []): mixed;
}
