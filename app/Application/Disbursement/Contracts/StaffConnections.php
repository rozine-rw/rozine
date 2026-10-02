<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

/**
 * Whether a staff member is connected to the Business or to the campaign's Investors (#96 answer
 * 5, 5874488658). S3-D owns this interface and the staff-person resolution behind it; the
 * Business/Investor connection projection it will read is Hussain's, and no adapter for it exists
 * yet, so `UnavailableStaffConnections` is bound and every answer is `unavailable`, which fails
 * closed for financial authority. A staff account has no marketplace Party: a null
 * `users.party_id` is never evidence of being unconnected, and there is no email or other fallback.
 * Only the synthetic local/testing fixture ever answers `unconnected`.
 */
interface StaffConnections
{
    /** @param list<string> $partyIds the campaign's committed Investor Parties */
    public function connection(int $staffUserId, string $businessId, array $partyIds): string;
}
