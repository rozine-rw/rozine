<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditEngagementStore;

final class RecordAuditEngagementTerms
{
    public function __construct(private AuditEngagementStore $engagements) {}

    /**
     * @param  array<string, mixed>  $documents
     * @return array<string, mixed>
     */
    public function handle(int $actorId, int $expectedRevision, string $status, ?string $version, array $documents, bool $synthetic, string $approvalReference, string $reason, string $requestId): array
    {
        return $this->engagements->record($actorId, $expectedRevision, $status, $version, $documents, $synthetic, $approvalReference, $reason, $requestId);
    }
}
