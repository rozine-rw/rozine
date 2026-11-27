<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * The Admin console's Institutions directory (Erastus, 27 Nov 2026): every institution application, whether
 * approved, in review, sent back, rejected or still a draft, with the institution the approval recorded, the
 * representative's account and the money the ledger and issued holdings record for the Party that invests for
 * it. Reads only and never locks; the caller checks the staff permission.
 *
 * Wallet is the Party's available and held cash; invested is its committed cash plus the principal of its
 * issued holdings, as the Investor directory counts a portfolio. A raise's share is the units the Party holds
 * or has committed in it (held or confirmed reservations) against the raise's units, as checkout's cap counts.
 *
 * @phpstan-type Status 'draft'|'submitted'|'changes_requested'|'approved'|'rejected'
 * @phpstan-type DirectoryRow array{
 *     application_id: string, party_id: string, status: Status, legal_name: string, rdb_code: string, institution_type: string,
 *     monthly_commitment: string, representative: string, email: string, user_id: int|null, wallet: string, invested: string,
 *     holdings: int, submitted_at: string|null, approved_at: string|null
 * }
 * @phpstan-type Counts array{all: int, approved: int, submitted: int, changes_requested: int, draft: int, rejected: int}
 * @phpstan-type Directory array{rows: list<DirectoryRow>, matching: int, counts: Counts, committed_monthly: string, invested: string}
 * @phpstan-type Raise array{
 *     campaign_id: string, business: string, units: string, amount: string, raise_amount: string, share_bps: int, cap_percent: int
 * }
 * @phpstan-type Detail array{
 *     row: DirectoryRow, approved_by: string|null, committed: string, cap_percent: int,
 *     holdings: list<array{business_id: string, name: string, principal: string, notes: int}>, raises: list<Raise>
 * }
 */
interface InstitutionDirectoryStore
{
    /**
     * One page of the directory under a status chip and a search of the institution's name, the representative's
     * name or email, and an approved institution's RDB code (before approval the code is only in the encrypted answers).
     *
     * @param  'all'|Status  $chip
     * @return Directory
     */
    public function directory(string $chip, string $search, int $limit): array;

    /** @return Detail|null */
    public function institution(string $applicationId): ?array;
}
