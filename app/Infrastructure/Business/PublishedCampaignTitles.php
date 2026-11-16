<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\CampaignTitles;
use App\Application\Business\Contracts\PrimaryCampaignSource;

/** Reads the title from the campaign's sealed publication. */
final readonly class PublishedCampaignTitles implements CampaignTitles
{
    public function __construct(private PrimaryCampaignSource $campaigns) {}

    public function title(string $campaignId): string
    {
        return $this->campaigns->presentation($campaignId)['title'];
    }
}
