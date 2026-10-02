<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Domain\Operations\CommandRejection;

/**
 * Bound until the S3-C adapter exists and wherever the synthetic source is not allowed. Nothing
 * is funded, locked, rechecked, issued or refunded through it: every disbursement fails closed.
 */
final class UnavailableFundedCampaigns implements FundedCampaigns
{
    public function funded(?string $before, int $limit): array
    {
        throw $this->unavailable();
    }

    public function lockBusiness(string $businessId): void
    {
        throw $this->unavailable();
    }

    public function lockFunded(string $campaignId): FundedCampaign
    {
        throw $this->unavailable();
    }

    public function recheck(FundedCampaign $campaign): RecheckResult
    {
        throw $this->unavailable();
    }

    public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
    {
        throw $this->unavailable();
    }

    public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
    {
        throw $this->unavailable();
    }

    private function unavailable(): CommandRejection
    {
        return new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
    }
}
