<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditLocationStore;

final class MarkAuditLocationMoved
{
    public function __construct(private AuditLocationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $kind, string $subjectId, int $expectedRevision, string $movedAt, string $reason, string $requestId): array
    {
        return $this->store->moved($actorId, $kind, $subjectId, $expectedRevision, $movedAt, $reason, $requestId);
    }
}
