<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DepositPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Synthetic deposit policies only. These values confer no live policy authority.
 *
 * @extends Factory<DepositPolicy>
 */
class DepositPolicyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['version' => 'synthetic-deposit-policy-'.Str::lower(Str::random(10)), 'synthetic' => true, 'status' => 'active',
            'fee' => '0', 'minimum' => '1000', 'maximum' => '1000000', 'effective_at' => now()->subMinute()];
    }

    public function withdrawn(): static
    {
        return $this->state(fn (): array => ['status' => 'withdrawn', 'fee' => null, 'minimum' => null, 'maximum' => null]);
    }
}
