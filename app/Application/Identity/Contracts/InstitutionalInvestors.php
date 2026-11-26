<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * The institutions Investor accounts invest for (BRS v3 KYC-R02, 26 Nov 2026).
 *
 * @phpstan-type Institution array{legal_name: string, rdb_code: string, type: string, monthly_commitment: int, approved_at: string}
 */
interface InstitutionalInvestors
{
    /** Records the institution a Party invests for, once Compliance approves its KYB. Runs inside the approval's transaction. */
    public function record(int $actorId, string $partyId, string $applicationId, string $legalName, string $rdbCode, string $type, int $monthlyCommitment): void;

    /** @return Institution|null the institution the Party invests for, if any */
    public function forParty(string $partyId): ?array;
}
