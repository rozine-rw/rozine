<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InvestorWallet;
use App\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LedgerAccount> */
class LedgerAccountFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['wallet_id' => InvestorWallet::factory(), 'kind' => 'investor_available', 'currency' => 'RWF'];
    }

    /** A platform account, which belongs to no wallet. */
    public function system(string $kind = 'deposit_clearing'): static
    {
        return $this->state(fn (): array => ['wallet_id' => null, 'kind' => $kind]);
    }
}
