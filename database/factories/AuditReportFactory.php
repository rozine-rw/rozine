<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\AuditReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditReport>
 *
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type AuditBinding from \App\Application\Business\Contracts\BusinessApplicationStore
 */
class AuditReportFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['revision' => 1, 'kind' => 'flash', 'status' => 'draft', 'step' => 'review',
            'amends_id' => null, 'draft' => ['note' => '', 'completed_steps' => [], 'fields' => []]];
    }

    /**
     * The caller supplies an actual protected submission; no financial history is invented.
     *
     * @param  AcceptedAssignment  $assignment
     * @param  AuditBinding  $application
     */
    public function forBinding(array $assignment, array $application): static
    {
        $binding = ['assignment' => ['id' => $assignment['id'], 'revision' => $assignment['revision'], 'party_id' => $assignment['party_id']], 'application' => $application];

        return $this->state(['assignment_id' => $assignment['id'], 'business_id' => $assignment['business_id'],
            'assignment_revision' => $assignment['revision'], 'author_party_id' => $assignment['party_id'],
            'application_id' => $application['application']['id'], 'application_revision' => $application['application']['revision'],
            'application_version_id' => $application['version']['id'], 'submission_id' => $application['submission']['id'],
            'quote_id' => $application['quote']['id'], 'kind' => $assignment['kind'] === 'routine' ? 'monthly' : 'flash',
            'step' => $assignment['kind'] === 'routine' ? 'statements' : 'review', 'binding' => $binding,
            'binding_sha256' => hash('sha256', app(CanonicalJson::class)->encode($binding))]);
    }
}
