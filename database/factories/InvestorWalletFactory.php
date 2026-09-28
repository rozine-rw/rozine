<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InvestorWallet;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InvestorWallet> */
class InvestorWalletFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['party_id' => Party::factory()->verified(), 'currency' => 'RWF'];
    }
}
