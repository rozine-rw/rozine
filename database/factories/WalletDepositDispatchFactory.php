<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WalletDepositDispatch> */
class WalletDepositDispatchFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['intent_id' => WalletDepositIntent::factory(), 'phase' => 'queued'];
    }
}
