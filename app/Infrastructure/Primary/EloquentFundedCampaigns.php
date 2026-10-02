<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\FundedCampaignRef;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Business\RetainedFundedCampaignFacts;
use App\Models\BusinessCampaign;
use App\Models\BusinessProfile;
use App\Models\PrimaryCampaignFunding;

/**
 * Staged retained-funding adapter, deliberately not bound to the production port. Discovery
 * authenticates historical funding and purchases; locking takes only Business then campaign
 * gates in the caller's transaction. The caller places staff locks between those calls and
 * retains the full financial order through commit. No verified-fact or lock cache is retained.
 * Current admission is unavailable and financial writes refuse until their authoritative sources
 * and forward settlement exist. Historical funding never supplies current admission authority.
 */
final class EloquentFundedCampaigns implements FundedCampaigns
{
    public function __construct(private RetainedFundedCampaignFacts $facts) {}

    public function funded(?string $before, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        return array_values(PrimaryCampaignFunding::query()
            ->when($before !== null, fn ($query) => $query->where('business_campaign_id', '<', $before))
            ->orderByDesc('business_campaign_id')->limit($limit)->get(['business_campaign_id'])
            ->map(function (PrimaryCampaignFunding $funding): FundedCampaignRef {
                $campaign = $this->retained($funding->business_campaign_id);

                return new FundedCampaignRef($campaign->campaignId, $campaign->businessId, $campaign->fundedAt);
            })->all());
    }

    public function lockBusiness(string $businessId): void
    {
        $this->assertTransaction();
        if (BusinessProfile::query()->whereKey($businessId)->lockForUpdate()->first() === null) {
            throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
        }
    }

    public function lockFunded(string $campaignId): FundedCampaign
    {
        $this->assertTransaction();
        if (BusinessCampaign::query()->whereKey($campaignId)->lockForUpdate()->first() === null) {
            throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
        }

        return $this->retained($campaignId);
    }

    public function recheck(FundedCampaign $campaign): RecheckResult
    {
        $this->assertTransaction();

        return RecheckResult::unavailable(['policy', 'evidence', 'dscr', 'exposure', 'restriction', 'mandate', 'conditions_precedent'], 'unavailable');
    }

    public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
    {
        $this->assertTransaction();
        throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
    }

    public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
    {
        $this->assertTransaction();
        throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
    }

    private function retained(string $campaignId): FundedCampaign
    {
        return $this->facts->find($campaignId) ?? throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
    }

    private function assertTransaction(): void
    {
        if (app('db.transactions')->callbackApplicableTransactions()->isEmpty()) {
            throw new DisbursementViolation('FUNDING_TRANSACTION_REQUIRED');
        }
    }
}
