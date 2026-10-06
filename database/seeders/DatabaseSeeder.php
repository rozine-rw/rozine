<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Environment\EnvironmentIsolation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(EnvironmentIsolation $isolation): void
    {
        $isolation->assertSeedingAllowed();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
