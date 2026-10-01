<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrimaryCampaignFunding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrimaryCampaignFunding>
 * Complete real campaign/purchase bindings must be supplied; a factory cannot admit funding.
 */
class PrimaryCampaignFundingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['payload' => [], 'sha256' => str_repeat('0', 64), 'created_at' => now()];
    }
}
