<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InvestorFundingMethod;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<InvestorFundingMethod> */
class InvestorFundingMethodFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['party_id' => Party::factory()->verified(), 'kind' => 'mtn', 'label' => 'MTN MoMo', 'masked' => '+250 788 ···· 456',
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
