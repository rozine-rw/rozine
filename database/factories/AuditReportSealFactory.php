<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditReport;
use App\Models\AuditReportSeal;
use App\Models\AuditSigningKey;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Deliberately unsigned data for insertion/integrity denial tests; valid seals use SealAuditReport.
 *
 * @extends Factory<AuditReportSeal>
 */
class AuditReportSealFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['audit_report_id' => AuditReport::factory(), 'report_revision' => 2, 'audit_signing_key_id' => AuditSigningKey::factory(),
            'author_party_id' => Party::factory(), 'actor_user_id' => User::factory(), 'digest' => hash('sha256', 'synthetic-report'),
            'payload' => ['synthetic' => true], 'jws' => 'synthetic-invalid-signature'];
    }

    public function forReport(AuditReport $report, User $user): static
    {
        return $this->state(['audit_report_id' => $report->id, 'report_revision' => $report->revision + 1,
            'author_party_id' => $report->author_party_id, 'actor_user_id' => $user->id]);
    }
}
