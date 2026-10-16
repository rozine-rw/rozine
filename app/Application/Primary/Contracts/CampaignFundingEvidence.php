<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/** Immutable funding-lock evidence, not current eligibility or disbursement authority. */
interface CampaignFundingEvidence
{
    /** @return array<string, mixed>|null */
    public function find(string $campaignId): ?array;
}
