<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * @phpstan-import-type Fields from \App\Domain\Business\ApplicationDraft
 * @phpstan-import-type Work from \App\Application\Auditor\Contracts\AuditAssignmentStore
 *
 * @phpstan-type Application array{id: string, business_id: string, revision: int, status: string, step: string, draft: Fields, mandate_version: int}
 * @phpstan-type AuditApplication array{work: Work, application: array{id: string, revision: int, title: string, target: string|null, term_months: int|null, use_of_funds: list<string>}|null}
 */
interface BusinessApplicationStore
{
    /** @return array<string, mixed> */
    public function create(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $requestId): array;

    /**
     * @param  Fields  $fields
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $fields, string $step, string $requestId): array;

    /** @return array<string, mixed> */
    public function evaluate(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, ?string $acceptedPrincipal, string $requestId): array;

    /** @return array<string, mixed>|null */
    public function quote(int $userId, int $contextRevision, string $businessId, string $applicationId): ?array;

    /** @return Application */
    public function get(int $userId, int $contextRevision, string $businessId, string $applicationId): array;

    /** @return Application|null */
    public function current(int $userId, int $contextRevision, string $businessId): ?array;

    /** @return AuditApplication */
    public function audit(int $userId, int $contextRevision, string $assignmentId): array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;
}
