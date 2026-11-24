<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

/** A partner's districts, weekly limit and address, for staff who verify partners. */
final class GetAuditorCoverage
{
    public function __construct(private AuditorProfileStore $store) {}

    /** @return array{accepting: bool, districts: list<string>, max_jobs_per_week: int, address: array{province: string, district: string, sector: string, cell: string, street: string}|null}|null */
    public function handle(int $actorId, string $partyId): ?array
    {
        return $this->store->coverage($actorId, $partyId);
    }
}
