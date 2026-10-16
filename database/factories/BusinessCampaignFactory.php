<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessCampaign;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BusinessCampaign> */
class BusinessCampaignFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['id' => (string) Str::ulid(), 'business_application_release_id' => BusinessApplicationRelease::factory(),
            'business_application_id' => fn (array $a): string => BusinessApplicationRelease::query()->whereKey($a['business_application_release_id'])->firstOrFail()->business_application_id,
            'business_id' => fn (array $a): string => BusinessApplicationRelease::query()->whereKey($a['business_application_release_id'])->firstOrFail()->business_id,
            'actor_user_id' => User::factory(),
            'exposure_reservation_id' => fn (array $a): string => BusinessApplicationRelease::query()->whereKey($a['business_application_release_id'])->firstOrFail()->exposure_reservation_id,
            'principal' => '3000000', 'live_at' => now()->startOfSecond(), 'expires_at' => fn (array $a) => CarbonImmutable::parse($a['live_at'])->addDays(30),
            'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $a): string => hash('sha256', app(CanonicalJson::class)->encode($a['payload']))];
    }
}
