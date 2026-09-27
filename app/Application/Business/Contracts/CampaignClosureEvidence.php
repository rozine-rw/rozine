<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

interface CampaignClosureEvidence
{
    /** @return array<string, mixed>|null */
    public function find(string $campaignId): ?array;

    /** @return array<string, string> Reservation IDs and verified released principals. */
    public function released(string $businessId): array;
}
