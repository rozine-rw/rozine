<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\AuditorIndependence
 *
 * @phpstan-type Review array{id: string, business_id: string, party_id: string, revision: int, state: State}
 */
interface AuditorIndependenceStore
{
    /**
     * @param  array<string, mixed>  $facts
     * @return array<string, mixed>
     */
    public function record(int $actorId, string $businessId, string $partyId, int $expectedRevision, array $facts, string $checkedAt, string $reference, string $reason, string $requestId): array;

    /** @return Review|null */
    public function get(int $actorId, string $businessId, string $partyId): ?array;
}
