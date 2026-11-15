<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/** The published title of a campaign, for servicing screens that name the note being repaid. */
interface CampaignTitles
{
    public function title(string $campaignId): string;
}
