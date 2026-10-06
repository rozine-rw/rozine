<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Auditor\AuditEngagementDocuments;
use App\Models\AuditEngagementRelease;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditEngagementRelease>
 */
class AuditEngagementReleaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['revision' => 1, 'status' => 'active', 'version' => 'synthetic-'.Str::ulid(),
            'procedure_version' => AuditEngagementDocuments::PROCEDURE,
            'documents' => (new AuditEngagementDocuments)->normalize([
                'master_services' => ['title' => 'Synthetic master services example', 'body' => 'SYNTHETIC ONLY. Isolated test terms; no actual professional engagement is represented.'],
                'agreed_procedures' => ['title' => 'Synthetic MVP-AUP-1 terms', 'body' => 'SYNTHETIC ONLY. Record agreed procedures and retained findings; no assurance is fabricated.'],
            ]), 'synthetic' => true, 'approval_reference' => 'synthetic:engagement-terms', 'reason' => 'Isolated engagement example.',
            'actor_user_id' => User::factory(),
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode(array_intersect_key($attributes,
                array_flip(['revision', 'status', 'version', 'procedure_version', 'documents', 'synthetic']))))];
    }
}
