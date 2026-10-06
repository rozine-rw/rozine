<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Identity\ActiveRolePolicy;
use App\Models\IdentityAuditEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IdentityAuditEvent> */
class IdentityAuditEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'actor_key' => 'console', 'actor_user_id' => null,
            'target_type' => 'user', 'target_id' => '1', 'action' => 'operator.configure',
            'reason' => 'Synthetic test event', 'request_id' => fake()->uuid(),
            'request_hash' => hash('sha256', 'fixture'), 'before' => [], 'after' => [], 'result' => [],
            'policy_version' => ActiveRolePolicy::POLICY_VERSION,
        ];
    }
}
