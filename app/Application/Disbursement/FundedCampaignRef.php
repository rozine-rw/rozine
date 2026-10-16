<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

/** A fully funded campaign the funding source lists, read without locks. */
final readonly class FundedCampaignRef
{
    public function __construct(public string $campaignId, public string $businessId, public string $fundedAt) {}
}
