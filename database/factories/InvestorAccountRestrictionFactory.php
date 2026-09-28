<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InvestorAccountRestriction;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Synthetic account cases. The default is the section 11.4 high-risk temporary hold.
 *
 * @extends Factory<InvestorAccountRestriction>
 */
class InvestorAccountRestrictionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['party_id' => Party::factory()->verified(), 'cause' => 'high_risk_hold',
            'scope' => ['withdrawals', 'primary_commitments', 'secondary_trading'], 'source' => 'synthetic',
            'effective_at' => now()->subMinute(), 'expires_at' => null];
    }

    /** @param  list<string>  $scope */
    public function externalOrder(array $scope = ['deposits', 'withdrawals']): static
    {
        return $this->state(fn (): array => ['cause' => 'external_order', 'scope' => $scope]);
    }
}
