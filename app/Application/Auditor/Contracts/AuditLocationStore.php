<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\VerifiedAuditLocation
 *
 * @phpstan-type Location array{id: string|null, kind: string, subject_id: string, revision: int, state: State}
 */
interface AuditLocationStore
{
    /** @return array<string, mixed> */
    public function verify(int $actorId, string $kind, string $subjectId, int $expectedRevision, string $latitude, string $longitude, int $uncertainty, string $verifiedAt, string $reference, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function moved(int $actorId, string $kind, string $subjectId, int $expectedRevision, string $movedAt, string $reason, string $requestId): array;

    /** @return Location */
    public function get(int $actorId, string $kind, string $subjectId): array;
}
