<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Auditor\AuditEngagementDocuments;
use App\Models\AuditAssignment;
use App\Models\AuditSourceSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * The caller binds an actual current accepted assignment; the database refuses any other binding.
 *
 * @extends Factory<AuditSourceSnapshot>
 */
class AuditSourceSnapshotFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['actor_user_id' => User::factory(), 'revision' => 1, 'status' => 'withdrawn', 'source_kind' => 'isolated_synthetic',
            'source_reference' => 'synthetic:unavailable-audit-source', 'procedure_version' => AuditEngagementDocuments::PROCEDURE,
            'facts' => null, 'reason' => 'Synthetic historical source; no Business assertion.',
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode(array_intersect_key($attributes,
                array_flip(['assignment_id', 'business_id', 'assignment_revision', 'party_id', 'revision', 'status', 'source_kind',
                    'source_reference', 'procedure_version', 'facts']))))];
    }

    public function forAssignment(AuditAssignment $assignment): static
    {
        return $this->state(['assignment_id' => $assignment->id, 'business_id' => $assignment->business_id,
            'assignment_revision' => $assignment->revision, 'party_id' => $assignment->party_id]);
    }
}
