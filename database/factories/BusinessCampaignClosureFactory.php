<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BusinessCampaignClosure> */
class BusinessCampaignClosureFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['id' => (string) Str::ulid(), 'business_campaign_id' => BusinessCampaign::factory(),
            'business_id' => fn (array $a): string => BusinessCampaign::query()->whereKey($a['business_campaign_id'])->firstOrFail()->business_id,
            'exposure_reservation_id' => fn (array $a): string => BusinessCampaign::query()->whereKey($a['business_campaign_id'])->firstOrFail()->exposure_reservation_id,
            'principal' => fn (array $a): string => BusinessCampaign::query()->whereKey($a['business_campaign_id'])->firstOrFail()->principal,
            'phase' => 'cancelled',
            'actor_user_id' => fn (array $a): int => BusinessCampaign::query()->whereKey($a['business_campaign_id'])->firstOrFail()->actor_user_id,
            'closed_at' => now()->startOfSecond(), 'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $a): string => hash('sha256', app(CanonicalJson::class)->encode($a['payload']))];
    }
}
