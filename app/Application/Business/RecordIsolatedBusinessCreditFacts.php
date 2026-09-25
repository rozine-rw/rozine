<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessCreditFactsStore;

final class RecordIsolatedBusinessCreditFacts
{
    public function __construct(private BusinessCreditFactsStore $store) {}

    /**
     * A null payload withdraws the source without erasing its historical snapshots.
     *
     * @param  array<string, mixed>|null  $facts
     * @return array<string, mixed>
     */
    public function handle(int $actorId, string $businessId, int $expectedRevision, ?array $facts, string $sourceReference, string $reason, string $requestId): array
    {
        return $this->store->recordFixture($actorId, $businessId, $expectedRevision, $facts, $sourceReference, $reason, $requestId);
    }
}
