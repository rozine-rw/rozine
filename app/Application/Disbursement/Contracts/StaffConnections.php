<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

/**
 * The authoritative staff-person to Business and Investor connection source (#96 answer 5). A
 * staff account has no marketplace Party, so `users.party_id` is never used for this. The answer is
 * `unconnected`, `connected` or `unavailable`; unavailable fails closed for financial authority.
 */
interface StaffConnections
{
    /** @param list<string> $partyIds the campaign's committed Investor Parties */
    public function connection(int $staffUserId, string $businessId, array $partyIds): string;
}
