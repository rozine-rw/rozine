<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Application\Environment\UatMoneySimulation;
use App\Domain\Operations\CommandRejection;
use LogicException;

/**
 * The funding source on UAT while its money simulation is on: the real retained funding adapter
 * (`EloquentFundedCampaigns`, given by the container binding: funded raises, their commitments, Holding issuance and failed closings) with the pre-disbursement
 * recheck passed under the simulation policy, because the live checks behind it (DSCR, exposure,
 * restrictions, conditions precedent) are not built yet. Bound nowhere else.
 */
final readonly class SimulatedFundedCampaigns implements FundedCampaigns
{
    public const string POLICY_VERSION = 'uat-simulation-v1';

    public function __construct(private FundedCampaigns $retained, private UatMoneySimulation $simulation) {}

    public function funded(?string $before, int $limit): array
    {
        $this->assertSimulating();

        // Scope before limiting: page past unnamed Businesses' raises so they cannot crowd out named ones.
        $named = [];
        while ($limit > 0 && count($named) < $limit) {
            $page = $this->retained->funded($before, $limit);
            foreach ($page as $campaign) {
                if (count($named) < $limit && $this->simulation->covers($campaign->businessId)) {
                    $named[] = $campaign;
                }
            }
            if (count($page) < $limit) {
                break;
            }
            $before = $page[count($page) - 1]->campaignId;
        }

        return $named;
    }

    public function lockBusiness(string $businessId): void
    {
        $this->assertSimulating();
        $this->retained->lockBusiness($businessId);
    }

    public function lockFunded(string $campaignId): FundedCampaign
    {
        $this->assertSimulating();
        $campaign = $this->retained->lockFunded($campaignId);
        // A disbursement already queued for a Business taken off the list stops here: the store reads this as the funding source being unavailable.
        if (! $this->simulation->covers($campaign->businessId)) {
            throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
        }

        return $campaign;
    }

    public function recheck(FundedCampaign $campaign): RecheckResult
    {
        $this->assertSimulating();

        return RecheckResult::passed(self::POLICY_VERSION);
    }

    public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
    {
        $this->assertSimulating();
        $this->retained->issue($campaign, $instruction);
    }

    public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
    {
        $this->assertSimulating();
        $this->retained->failClose($campaign, $closing);
    }

    private function assertSimulating(): void
    {
        if (! $this->simulation->active()) {
            throw new LogicException('UAT_SIMULATION_ONLY: the simulated funding source runs only on UAT with its money simulation on.');
        }
    }
}
