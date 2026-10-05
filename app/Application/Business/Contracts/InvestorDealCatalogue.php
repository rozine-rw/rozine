<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The Investor projection of live publications: allowlisted disclosure aggregates (H9) and live
 * fill from retained Primary facts. Takes no lock, grants nothing and supplies no terms: the
 * caller holds current Investor authority, and only admission may quote or reserve.
 *
 * @phpstan-type DealCard array<string, mixed>
 */
interface InvestorDealCatalogue
{
    /**
     * Unclosed publications, newest first, bounded. A publication without a published rating is
     * never listed.
     *
     * @return list<DealCard>
     */
    public function deals(): array;

    /**
     * One unclosed, rated publication with its detail sections; null when there is none to show.
     *
     * @return DealCard|null
     */
    public function deal(string $campaignId): ?array;
}
