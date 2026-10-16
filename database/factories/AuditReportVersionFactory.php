<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditReportVersion>
 */
class AuditReportVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['command' => 'audit.start', 'created_at' => now()];
    }

    public function forReport(AuditReport $report, string $actorPartyId, int $actorUserId): static
    {
        $snapshot = ['id' => $report->id, 'revision' => $report->revision, 'status' => $report->status, 'step' => $report->step,
            'binding_sha256' => $report->binding_sha256, 'draft' => $report->draft];

        return $this->state(['audit_report_id' => $report->id, 'revision' => $report->revision, 'status' => $report->status,
            'step' => $report->step, 'snapshot' => $snapshot, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($snapshot)),
            'actor_party_id' => $actorPartyId, 'actor_user_id' => $actorUserId]);
    }
}
