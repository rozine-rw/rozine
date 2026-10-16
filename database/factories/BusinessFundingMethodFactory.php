<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Synthetic Business funding methods only. A verified method here confers no live verification.
 *
 * @extends Factory<BusinessFundingMethod>
 */
class BusinessFundingMethodFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_id' => BusinessProfile::factory(), 'kind' => 'mtn', 'label' => 'MTN MoMo', 'masked' => '+250 788 ···· 456',
            'reference' => 'synthetic-msisdn-'.Str::lower(Str::random(12)), 'verification_source' => 'synthetic',
            'verified_at' => now()->subMinute(), 'revoked_at' => null];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['verified_at' => null]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }
}
