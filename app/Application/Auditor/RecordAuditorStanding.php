<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

final class RecordAuditorStanding
{
    public function __construct(private AuditorProfileStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $partyId, int $expectedRevision, string $decision, ?string $submissionId, string $checkedAt, string $reference, string $reason, string $requestId): array
    {
        return $this->store->review($actorId, $partyId, $expectedRevision, $decision, $submissionId, $checkedAt, $reference, $reason, $requestId);
    }
}
