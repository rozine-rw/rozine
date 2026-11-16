<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Disbursement\Contracts\SimulatedRaiseFunding;
use App\Application\Environment\UatMoneySimulation;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessCampaign;
use Illuminate\Support\Facades\DB;

/**
 * Records a named synthetic Business's fully committed raise as funded on UAT under the money
 * simulation, through the real Primary funding lock, which still checks every commitment and its
 * committed cash. The admission it hands that lock is the simulation's own, never live evidence.
 * A raise not yet fully committed, closed or otherwise refused is left as it is and tried again on
 * the next run. Outside the simulation, and for any Business staging does not name, it does nothing.
 */
final readonly class SimulatedCommittedRaiseFunding implements SimulatedRaiseFunding
{
    public const string POLICY_VERSION = 'uat-simulation-v1';

    public function __construct(private UatMoneySimulation $simulation, private PrimaryFunding $funding,
        private CampaignFundingEvidence $fundings, private PrimaryCampaignSource $campaigns) {}

    public function fundCommitted(int $limit): array
    {
        $result = ['funded' => 0, 'waiting' => 0];
        if ($limit < 1 || ! $this->simulation->active()) {
            return $result;
        }
        $candidates = BusinessCampaign::query()->where('live_at', '<=', now('UTC'))->orderByDesc('id')->get(['id', 'business_id'])
            ->filter(fn (BusinessCampaign $campaign): bool => $this->simulation->covers($campaign->business_id)
                && $this->fundings->find($campaign->id) === null)
            ->take($limit);
        foreach ($candidates as $campaign) {
            try {
                DB::transaction(fn (): array => $this->funding->lock($campaign->id, fn (string $campaignId): array => $this->admission($campaignId)));
                $result['funded']++;
            } catch (CommandRejection|WalletViolation) {
                $result['waiting']++;
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function admission(string $campaignId): array
    {
        $campaign = $this->campaigns->lockForFunding($campaignId);
        $evidence = ['policy_version' => self::POLICY_VERSION, 'environment' => 'uat', 'business_id' => $campaign['business_id'],
            'source' => 'UAT money simulation: synthetic test Business named by staging, not live admission.'];

        return ['campaign_id' => $campaignId, 'publication_sha256' => $campaign['publication_sha256'],
            ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => $evidence])];
    }
}
