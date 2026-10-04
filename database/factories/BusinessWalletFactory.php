<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BusinessProfile;
use App\Models\BusinessWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessWallet> */
class BusinessWalletFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_id' => BusinessProfile::factory(), 'currency' => 'RWF'];
    }
}
