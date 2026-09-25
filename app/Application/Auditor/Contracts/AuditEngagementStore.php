<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

use Closure;

/**
 * @phpstan-import-type Documents from \App\Domain\Auditor\AuditEngagementDocuments
 * @phpstan-import-type Acceptance from \App\Domain\Auditor\AuditEngagementDocuments
 *
 * @phpstan-type Release array{id: string, revision: int, version: string, procedure_version: string, documents: Documents, sha256: string, synthetic: bool}
 * @phpstan-type Page array{release: Release|null, acceptance: Acceptance|null}
 */
interface AuditEngagementStore
{
    /**
     * @param  array<string, mixed>  $documents
     * @return array<string, mixed>
     */
    public function record(int $actorId, int $expectedRevision, string $status, ?string $version, array $documents, bool $synthetic, string $approvalReference, string $reason, string $requestId): array;

    /** @return Page */
    public function get(int $userId, int $contextRevision): array;

    /** @return array<string, mixed> */
    public function accept(int $userId, int $contextRevision, string $releaseId, int $expectedRevision, string $sha256, bool $accepted, string $requestId): array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId): array;

    /**
     * The assignment caller holds current Business and ordered Party/profile locks.
     * This supplies retained terms facts, not permission to access a Party or assignment.
     * Catalog withdrawal cannot commit until the protected callback finishes.
     *
     * @template TResult
     *
     * @param  list<string>  $partyIds
     * @param  Closure(array<string, Acceptance>): TResult  $operation
     * @return TResult
     */
    public function withCurrentAcceptances(array $partyIds, Closure $operation): mixed;
}
