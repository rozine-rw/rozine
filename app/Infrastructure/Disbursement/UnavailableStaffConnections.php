<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\StaffConnections;

/** No authoritative staff-person connection source exists yet (#96 answer 5): unknown is never unconnected. */
final class UnavailableStaffConnections implements StaffConnections
{
    public function connection(int $staffUserId, string $businessId, array $partyIds): string
    {
        return 'unavailable';
    }
}
