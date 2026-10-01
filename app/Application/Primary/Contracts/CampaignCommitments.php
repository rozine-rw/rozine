<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/** Primary-owned evidence used to keep legacy investor-free campaign closure conservative. */
interface CampaignCommitments
{
    /**
     * The caller holds the Business lock through its transaction; closure also locks the campaign.
     * Any retained commitment requires the future settlement path, even after its hold expires.
     */
    public function anyForCampaign(string $campaignId): bool;
}
