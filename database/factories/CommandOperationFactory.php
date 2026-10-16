<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommandOperation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CommandOperation> */
class CommandOperationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'actor_key' => 'party:'.strtolower((string) Str::ulid()), 'actor_user_id' => 1, 'command' => 'fixture.save',
            'request_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'fixture'),
            'target_type' => 'fixture', 'target_id' => (string) Str::ulid(),
            'result' => ['status' => 'completed', 'code' => 'FIXTURE_SAVED'], 'retain_until' => now()->addDays(8),
        ];
    }
}
