<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\CampaignCommitments;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;

final class EloquentCampaignCommitments implements CampaignCommitments
{
    public function anyForCampaign(string $campaignId): bool
    {
        return PrimaryCommitment::query()->whereIn('primary_reservation_id',
            PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->select('id'))->exists();
    }
}
