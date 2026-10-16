<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RoleBookmark;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RoleBookmark> */
class RoleBookmarkFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 'role' => 'investor',
            'membership_id' => RoleMembership::factory()->active(), 'membership_revision' => 1,
            'route' => 'investor.home', 'parameters' => [], 'query' => [],
        ];
    }
}
