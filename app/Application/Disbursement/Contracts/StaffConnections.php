<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

/**
 * Whether a staff member is connected to the Business or to the campaign's Investors (#96 answer
 * 5, 5874488658). S3-D owns this interface and the staff-person resolution behind it. A staff
 * account has no marketplace Party: a null `users.party_id` is never evidence of being
 * unconnected, and there is no email or other fallback. `EloquentStaffConnections` answers from
 * the staff member's verified person, the Business's declared connections and the staff member's
 * own signed independence declaration for this disbursement (#96 5956161592); anything it cannot
 * establish is `unavailable`, which fails closed for financial authority.
 */
interface StaffConnections
{
    /**
     * `$operationId` names the staff member's own authorize or approve operation on this
     * disbursement whose declaration counts; no other declaration is evidence.
     *
     * @param  list<string>  $partyIds  the campaign's committed Investor Parties
     */
    public function connection(int $staffUserId, string $disbursementId, string $operationId, string $businessId, array $partyIds): string;
}
