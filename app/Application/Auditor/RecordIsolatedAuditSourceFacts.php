<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditSourceFactsStore;

final class RecordIsolatedAuditSourceFacts
{
    public function __construct(private AuditSourceFactsStore $store) {}

    /**
     * A null payload withdraws the source without erasing its historical snapshots.
     *
     * @param  array<string, mixed>|null  $facts
     * @return array<string, mixed>
     */
    public function handle(int $actorId, string $assignmentId, int $assignmentRevision, int $expectedRevision, ?array $facts, string $sourceReference, string $reason, string $requestId): array
    {
        return $this->store->recordFixture($actorId, $assignmentId, $assignmentRevision, $expectedRevision, $facts, $sourceReference, $reason, $requestId);
    }
}
