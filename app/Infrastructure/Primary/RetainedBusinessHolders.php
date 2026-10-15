<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\BusinessHolders;
use Illuminate\Support\Facades\DB;

final class RetainedBusinessHolders implements BusinessHolders
{
    public function parties(string $businessId): array
    {
        return array_values(array_map(strval(...), DB::table('primary_holdings')
            ->join('business_campaigns', 'business_campaigns.id', '=', 'primary_holdings.business_campaign_id')
            ->where('business_campaigns.business_id', $businessId)->distinct()->orderBy('primary_holdings.party_id')
            ->pluck('primary_holdings.party_id')->all()));
    }
}
