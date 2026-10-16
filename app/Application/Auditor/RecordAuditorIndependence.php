<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorIndependenceStore;

final class RecordAuditorIndependence
{
    public function __construct(private AuditorIndependenceStore $store) {}

    /**
     * @param  array<string, mixed>  $facts
     * @return array<string, mixed>
     */
    public function handle(int $actorId, string $businessId, string $partyId, int $expectedRevision, array $facts, string $checkedAt, string $reference, string $reason, string $requestId): array
    {
        return $this->store->record($actorId, $businessId, $partyId, $expectedRevision, $facts, $checkedAt, $reference, $reason, $requestId);
    }
}
