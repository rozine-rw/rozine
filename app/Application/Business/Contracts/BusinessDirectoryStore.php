<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The Admin console's Business directory: every Business whose authority staff have recorded, with
 * the figures its own records hold. Reads only and never locks; the caller checks the permission.
 *
 * A note is active from publication until it closes unfunded; a funded note stays active through
 * repayment. Raised is the principal of the Business's funded notes and investors are the distinct
 * Investors whose commitments funded them, as the Business's own home counts them. The rating is
 * the one its newest rated note published. No arrears, freeze or capacity read exists yet, so no
 * Business reads as on watch, distressed or frozen.
 *
 * @phpstan-type Rating array{band: string, score: string}
 * @phpstan-type DirectoryRow array{
 *     business_id: string, name: string, sector: string, district: string, company_code: string|null, kyc: 'verified'|'pending',
 *     rating: Rating|null, active_notes: int, investors: int, raised: string
 * }
 * @phpstan-type Counts array{all: int, healthy: int, watch: int, distressed: int, frozen: int}
 * @phpstan-type Directory array{
 *     rows: list<DirectoryRow>, matching: int, counts: Counts, active_notes: int, raised: string, average_score: string|null, sectors: list<string>
 * }
 * @phpstan-type Note array{id: string, title: string, status: 'active'|'funded'|'failed'}
 * @phpstan-type HistoryEntry array{
 *     id: string, at: string, actor: string, subject: string|null, reason: string|null,
 *     command: 'business.authority.configure'|'business.authority.revoke'|'campaign.publish'|'campaign.cancel'|'campaign.expire'
 * }
 * @phpstan-type Detail array{row: DirectoryRow, notes: list<Note>, history: list<HistoryEntry>}
 */
interface BusinessDirectoryStore
{
    /**
     * One page of the directory under a chip, sector, sort and name search. The figures and chip
     * counts cover every match of the search and sector; the average is of the published scores.
     *
     * @param  'all'|'healthy'|'watch'|'distressed'|'frozen'  $chip
     * @param  'raised'|'name'  $sort
     * @return Directory
     */
    public function directory(string $chip, string $sort, string $sector, string $search, int $limit): array;

    /** @return Detail|null */
    public function business(string $businessId): ?array;
}
