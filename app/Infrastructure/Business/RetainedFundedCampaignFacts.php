<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\FundedCommitment;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Models\BusinessCampaign;
use App\Models\BusinessProfile;
use RuntimeException;

/**
 * Immutable purchase projection for FundedCampaigns integration, including issue retries after
 * committed cash has left the wallets. It neither locks nor writes; the caller retains Business,
 * staff and campaign/disbursement gates before using these facts to settle. Current admission,
 * authenticated closing authority and Holding-source verification remain separate requirements.
 * The Business name is a current display label and supplies no financial or mandate authority.
 */
final class RetainedFundedCampaignFacts
{
    public function __construct(private CampaignFundingEvidence $fundings, private PublishedCampaignEvidence $publications) {}

    public function find(string $campaignId): ?FundedCampaign
    {
        $funding = $this->fundings->find($campaignId);
        if ($funding === null) {
            return null;
        }
        $publication = $this->publications->find($campaignId);
        $campaign = BusinessCampaign::query()->whereKey($campaignId)->sole();
        foreach (['campaign_id', 'business_id', 'exposure_reservation_id', 'principal'] as $binding) {
            if (($funding[$binding] ?? null) !== ($publication[$binding] ?? null)) {
                throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
            }
        }
        $termMonths = $publication['quote']['term_months'] ?? null;
        if (($funding['publication_sha256'] ?? null) !== $campaign->sha256 || ! is_int($termMonths)) {
            throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
        }
        $commitments = array_map(function (array $purchase) use ($termMonths): FundedCommitment {
            if (($purchase['terms']['term_months'] ?? null) !== $termMonths) {
                throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
            }

            return new FundedCommitment($purchase['commitment_id'], $purchase['party_id'], $purchase['cash']['origin_operation_id'],
                (int) $purchase['units'], $purchase['ordinals'], $purchase['rights'], $purchase['terms'], $purchase['principal']);
        }, $funding['commitments']);
        usort($commitments, fn (FundedCommitment $left, FundedCommitment $right): int => strcmp($left->id, $right->id));
        $business = BusinessProfile::query()->whereKey($funding['business_id'])->sole();

        return new FundedCampaign($campaignId, $funding['business_id'], $business->profile['name'], $publication['title'],
            $funding['exposure_reservation_id'], $funding['principal'], $funding['recorded_at'], $termMonths, $commitments);
    }
}
