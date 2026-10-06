<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditPublicationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Synthetic denied-insert data; valid history is produced by the publication commands.
 * @extends Factory<AuditPublicationEvent>
 */
class AuditPublicationEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['audit_report_publication_id' => (string) Str::ulid(), 'publication_revision' => 1,
            'command' => 'report.delivered', 'actor_kind' => 'system', 'actor_user_id' => null, 'actor_party_id' => null,
            'payload' => ['synthetic' => true], 'sha256' => hash('sha256', '{"synthetic":true}'), 'created_at' => now()];
    }
}
