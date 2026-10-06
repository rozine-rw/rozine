<?php

declare(strict_types=1);

namespace App\Application\Evidence\Contracts;

use Closure;

/**
 * @phpstan-import-type Rail from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type Statement from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type Observation from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type Review from \App\Domain\Evidence\StatementAuditReview
 * @phpstan-import-type VerifiedObservation from \App\Domain\Evidence\StatementAuditReview
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 *
 * @phpstan-type TranscriptionPayload array{business_id: string, source_revision: int, classification_version: string, rails: list<Rail>, months: list<string>, statements: list<Statement>, source_hashes: array<string, string>, observations: list<Observation>}
 * @phpstan-type Transcription array{id: string, revision: int, amends_id: string|null, sha256: string, current: bool, verified: false, payload: TranscriptionPayload}
 * @phpstan-type Extraction array{id: string, revision: int, parser_version: string, status: string, reason_codes: list<string>, record_count: int|null}
 * @phpstan-type Document array{id: string, filename: string, sha256: string, media_type: string, size_bytes: int, received_at: string, extraction: Extraction}
 * @phpstan-type SourceProvenance array{sha256: string, extraction: Extraction}
 * @phpstan-type Manifest array{revision: int, documents: list<Document>}
 * @phpstan-type Original array{filename: string, media_type: string, sha256: string, content: string}
 * @phpstan-type AuditFile array{assignment: AcceptedAssignment, evidence: Manifest, transcription: Transcription|null}
 * @phpstan-type VerificationPayload array{business_id: string, assignment: AcceptedAssignment, source_revision: int, transcription: array{id: string, sha256: string}, source_hashes: array<string, string>, source_provenance: array<string, SourceProvenance>, policy_version: string, procedure_version: string, classification_version: string, review: Review, verified_at: string, report_approval: 'not_cosigned', observations: list<VerifiedObservation>}
 * @phpstan-type Verification array{id: string, revision: int, amends_id: string|null, sha256: string, current: bool, payload: VerificationPayload}
 */
interface StatementStore
{
    /** @return array<string, mixed> */
    public function ingest(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $filename, string $content, string $requestId): array;

    /** @return Manifest */
    public function get(int $userId, int $contextRevision, string $businessId): array;

    /** @return Original */
    public function read(int $userId, int $contextRevision, string $businessId, string $documentId): array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId, string $command = 'ingest'): array;

    /**
     * @param  list<Rail>  $rails
     * @param  list<string>  $months
     * @param  list<Statement>  $statements
     * @return array<string, mixed>
     */
    public function recordTranscription(int $userId, int $contextRevision, string $businessId, int $expectedRevision, array $rails, array $months, array $statements, string $requestId): array;

    /** @return Transcription|null */
    public function transcription(int $userId, int $contextRevision, string $businessId, ?string $transcriptionId): ?array;

    /** @return AuditFile */
    public function audit(int $userId, int $contextRevision, string $assignmentId): array;

    /** @return Original */
    public function auditRead(int $userId, int $contextRevision, string $assignmentId, string $documentId): array;

    /** @return Transcription|null */
    public function auditTranscription(int $userId, int $contextRevision, string $assignmentId, ?string $transcriptionId): ?array;

    /**
     * @param  array<string, mixed>  $review
     * @return array<string, mixed>
     */
    public function verify(int $userId, int $contextRevision, string $assignmentId, int $expectedAssignmentRevision, int $expectedEvidenceRevision, int $expectedVerificationRevision, string $transcriptionId, string $transcriptionHash, array $review, string $requestId): array;

    /** @return Verification|null */
    public function verification(int $userId, int $contextRevision, string $businessId, ?string $verificationId): ?array;

    /**
     * @template TResult
     *
     * @param  Closure(Business, AccessSnapshot, Verification|null): TResult  $operation
     * @return TResult
     */
    public function withBusinessVerification(int $userId, int $contextRevision, string $businessId, string $permission, ?int $mandateVersion, Closure $operation): mixed;

    /**
     * Holds current verified Business and source-authority locks for a system publication decision.
     *
     * @template TResult
     *
     * @param  Closure(Business, Verification|null): TResult  $operation
     * @return TResult
     */
    public function withSystemVerification(string $businessId, Closure $operation): mixed;

    /** @return Verification|null */
    public function auditVerification(int $userId, int $contextRevision, string $assignmentId, ?string $verificationId): ?array;

    /** @return array<string, mixed> */
    public function findVerificationOperation(int $userId, int $contextRevision, string $requestId): array;
}
