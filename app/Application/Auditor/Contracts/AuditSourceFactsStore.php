<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * Assignment-scoped isolated source facts for C2 procedure acceptance only. Records are synthetic,
 * never a native/D-04 capture proof or a real Business assertion, and are unavailable outside an
 * environment that allows isolated fixtures.
 *
 * @phpstan-import-type AcceptedAssignment from AuditAssignmentStore
 * @phpstan-import-type Position from \App\Domain\Auditor\AuditSourceFacts
 * @phpstan-import-type Photo from \App\Domain\Auditor\AuditSourceFacts
 *
 * @phpstan-type Source array{id: string, revision: int, sha256: string, kind: 'isolated_synthetic', reference: string, procedure_version: string, assignment_id: string, assignment_revision: int}
 * @phpstan-type SourceFacts array{source: Source, declared_stock_rwf: string|null, declared_stock_units: string|null, declared_unit_label: string|null, declared_sector_label: string|null, declared_account_label: string|null, check_in: array{at: string|null, position: Position|null, review_required: bool|null}, photos: array{required: list<Photo>|null, extra: list<Photo>|null}, proof_ids: array{financial: list<string>|null, inventory: list<string>|null}}
 */
interface AuditSourceFactsStore
{
    /**
     * Trusted isolated-fixture publisher for the exact current accepted assignment revision and
     * Auditor, never an Auditor command, capture route or production override.
     *
     * @param  array<string, mixed>|null  $facts
     * @return array<string, mixed>
     */
    public function recordFixture(int $actorId, string $assignmentId, int $assignmentRevision, int $expectedRevision, ?array $facts, string $sourceReference, string $reason, string $requestId): array;

    /**
     * Holds current accepted-assignment authority through the read.
     *
     * @return SourceFacts|null
     */
    public function forAssignment(int $userId, int $contextRevision, string $assignmentId): ?array;

    /**
     * Source read only; grants no access. The caller already holds current accepted-assignment
     * authority for exactly this assignment snapshot.
     *
     * @param  AcceptedAssignment  $assignment
     * @return SourceFacts|null
     */
    public function forAccepted(array $assignment): ?array;
}
