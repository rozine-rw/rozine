<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * The Admin console's Investor directory: every Party that holds an Investor membership or has
 * started identity verification, with its KYC state and the money the ledger and issued holdings
 * record for it. Reads only and never locks; the caller checks the staff permission.
 *
 * Wallet is the Party's available and held cash; portfolio is its committed cash plus the principal
 * of its issued holdings, so a commitment counts once whether or not its note has issued yet. The
 * 360 counts holdings but does not list them: a holding's title is Business publication evidence,
 * and holdings are read through the S3-C adapter once it exposes them.
 *
 * @phpstan-type DirectoryRow array{
 *     party_id: string, name: string, email: string, kyc: 'verified'|'pending'|'rejected',
 *     verification_id: string|null, wallet: string, portfolio: string, holdings: int, businesses: int, restricted: bool
 * }
 * @phpstan-type Counts array{all: int, verified: int, pending: int, kyc_overdue: int, frozen: int, restricted: int}
 * @phpstan-type Directory array{rows: list<DirectoryRow>, matching: int, counts: Counts, awaiting_review: int, aum: string}
 * @phpstan-type Detail array{
 *     row: DirectoryRow, restricted_since: string|null,
 *     history: list<array{id: string, at: string, actor: string, command: string, reason: string|null}>
 * }
 */
interface InvestorDirectoryStore
{
    /**
     * One page of the directory under a chip, sort and name or email search.
     *
     * @param  'all'|'verified'|'pending'|'kyc_overdue'|'frozen'|'restricted'  $chip
     * @param  'portfolio'|'name'  $sort
     * @return Directory
     */
    public function directory(string $chip, string $sort, string $search, int $limit): array;

    /** @return Detail|null */
    public function party(string $partyId): ?array;

    /** The Party a verification case belongs to, or null when there is no such case. */
    public function partyForVerification(string $verificationId): ?string;
}
