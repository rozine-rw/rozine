<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/** Internal Business evidence; the full retained payload is never an Investor projection. */
interface PublishedCampaignEvidence
{
    /** @return array<string, mixed> */
    public function find(string $campaignId): array;
}
