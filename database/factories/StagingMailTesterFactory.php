<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StagingMailTester;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StagingMailTester>
 */
class StagingMailTesterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => strtolower(fake()->unique()->safeEmail()),
            'added_by_user_id' => User::factory(),
        ];
    }
}
