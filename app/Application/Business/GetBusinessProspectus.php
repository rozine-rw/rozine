<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessProspectuses;

/**
 * The Listing Builder's prospectus: the latest draft or the signed version, with every PRO-R03 gate
 * and its fix, read under `business.view`.
 *
 * @phpstan-import-type Prospectus from BusinessProspectuses
 */
final class GetBusinessProspectus
{
    public function __construct(private BusinessProspectuses $prospectuses) {}

    /** @return Prospectus */
    public function handle(int $userId, int $contextRevision, string $businessId, string $applicationId): array
    {
        return $this->prospectuses->current($userId, $contextRevision, $businessId, $applicationId);
    }
}
