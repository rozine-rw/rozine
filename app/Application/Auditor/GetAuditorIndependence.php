<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorIndependenceStore;

/** @phpstan-import-type Review from AuditorIndependenceStore */
final class GetAuditorIndependence
{
    public function __construct(private AuditorIndependenceStore $store) {}

    /** @return Review|null */
    public function handle(int $actorId, string $businessId, string $partyId): ?array
    {
        return $this->store->get($actorId, $businessId, $partyId);
    }
}
