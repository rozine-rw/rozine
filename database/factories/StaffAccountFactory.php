<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StaffAccount> */
class StaffAccountFactory extends Factory
{
    /** @param list<string> $roles */
    public function withRoles(array $roles): static
    {
        return $this->afterCreating(function (StaffAccount $staff) use ($roles): void {
            $staff->syncRoles($roles);
        });
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_id' => User::factory()->withTwoFactor(), 'enabled' => true];
    }
}
