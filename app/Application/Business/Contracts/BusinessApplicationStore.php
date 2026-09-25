<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use Closure;

/**
 * @phpstan-import-type Fields from \App\Domain\Business\ApplicationDraft
 * @phpstan-import-type Work from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type Terms from \App\Domain\Business\MandateAuthority
 *
 * @phpstan-type Application array{id: string, business_id: string, revision: int, status: string, step: string, draft: Fields, mandate_version: int}
 * @phpstan-type AuditApplication array{work: Work, application: array{id: string, revision: int, title: string, target: string|null, term_months: int|null, use_of_funds: list<string>}|null}
 * @phpstan-type EvaluationExpectation array{target: string, term_months: int, evidence_version: string}
 * @phpstan-type AuditBinding array{application: array{id: string, business_id: string, revision: int}, version: array{id: string, sha256: string}, submission: array{id: string, sha256: string, submitted_at: string}, quote: array{id: string, revision: int, sha256: string}, mandate: array{version: int, sha256: string}}
 */
interface BusinessApplicationStore
{
    /** @return array<string, mixed> */
    public function index(int $userId, int $contextRevision, ?string $before = null, int $limit = 20): array;

    /** @return array<string, mixed> */
    public function create(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $requestId): array;

    /**
     * @param  Fields  $fields
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $fields, ?string $step, string $requestId): array;

    /**
     * @param  EvaluationExpectation|null  $expectation
     * @return array<string, mixed>
     */
    public function evaluate(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, ?string $acceptedPrincipal, string $requestId, ?array $expectation = null): array;

    /** @return array<string, mixed> */
    public function page(int $userId, int $contextRevision, string $businessId, string $applicationId): array;

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function projectOperation(int $userId, int $contextRevision, array $result): array;

    /** @return array<string, mixed>|null */
    public function quote(int $userId, int $contextRevision, string $businessId, string $applicationId): ?array;

    /**
     * Records the current required Party's acceptance. Submission occurs only when every
     * required Party has signed the identical quote, mandate and consent release.
     *
     * @param  array<string, mixed>  $acceptance
     * @return array<string, mixed>
     */
    public function submit(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $acceptance, string $requestId): array;

    /** @return array<string, mixed> */
    public function review(int $userId, int $contextRevision, string $businessId, string $applicationId): array;

    /** @return Application */
    public function get(int $userId, int $contextRevision, string $businessId, string $applicationId): array;

    /** @return Application|null */
    public function current(int $userId, int $contextRevision, string $businessId): ?array;

    /** @return AuditApplication */
    public function audit(int $userId, int $contextRevision, string $assignmentId): array;

    /**
     * Holds current accepted-assignment authority through the caller's effect. This private
     * report input pins a submitted application; it is not an audience Resource or a new offer.
     *
     * @template TResult
     *
     * @param  Closure(AcceptedAssignment, AuditBinding): TResult  $operation
     * @return TResult
     */
    public function withAuditBinding(int $userId, int $contextRevision, string $assignmentId, string $applicationId, Closure $operation): mixed;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;
}
