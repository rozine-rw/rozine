<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

use App\Application\Disbursement\FundedCampaign;

/**
 * Local and testing control of the synthetic funding source, destination, connection and payout
 * provider fixtures, behind the synthetic guard. None of it is production funding authority,
 * verification or joint acceptance; demo, UAT and production never reach it.
 */
interface SyntheticDisbursementFixtures
{
    /**
     * A synthetic fully funded campaign with a verified synthetic destination.
     *
     * @param  list<int>  $commitmentUnits  units per commitment, one Party each
     */
    public function fund(array $commitmentUnits = [300, 300], int $termMonths = 12): FundedCampaign;

    /**
     * Scripts the next rechecks of a campaign.
     *
     * @param  'passed'|'failed'|'unavailable'  $outcome
     * @param  list<string>  $causes
     */
    public function scriptRecheck(string $campaignId, string $outcome, array $causes = []): void;

    /** @param 'verified'|'revoked'|'expired'|'rotated' $state */
    public function setDestination(string $businessId, string $state): void;

    /** @param 'connected'|'unconnected'|'unavailable' $state */
    public function setConnection(?int $userId, string $state): void;

    /**
     * The funding effects the synthetic source recorded for a campaign.
     *
     * @return list<array<string, mixed>>
     */
    public function effects(string $campaignId): array;
}
