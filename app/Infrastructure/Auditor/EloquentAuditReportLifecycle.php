<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditReportLifecycle;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;

final class EloquentAuditReportLifecycle implements AuditReportLifecycle
{
    public function __construct(private CanonicalJson $json) {}

    public function withdrawForConflict(string $assignmentId, string $partyId, int $actorId, string $conflictId): void
    {
        $this->withdraw($assignmentId, $partyId, $actorId, ['reason_code' => 'AUDITOR_CONFLICT', 'conflict_id' => $conflictId], 'conflict.declare');
    }

    public function withdrawForOperations(string $assignmentId, string $partyId, int $actorId): void
    {
        $this->withdraw($assignmentId, $partyId, $actorId, ['reason_code' => 'AUDIT_OPERATIONS_WITHDRAWN'], 'audit.assignment.withdraw');
    }

    /**
     * Withdraw the partner's open drafts on the assignment. The version names the partner who wrote
     * them and the user who acted.
     *
     * @param  array<string, string>  $withdrawal
     */
    private function withdraw(string $assignmentId, string $partyId, int $actorId, array $withdrawal, string $command): void
    {
        $reports = AuditReport::query()->where('assignment_id', $assignmentId)->where('author_party_id', $partyId)
            ->where('status', 'draft')->orderBy('id')->lockForUpdate()->get();
        foreach ($reports as $report) {
            $report->forceFill(['revision' => $report->revision + 1, 'status' => 'withdrawn',
                'draft' => [...$report->draft, 'withdrawal' => $withdrawal]])->save();
            $snapshot = ['id' => $report->id, 'revision' => $report->revision, 'status' => $report->status, 'step' => $report->step,
                'binding_sha256' => $report->binding_sha256, 'draft' => $report->draft];
            (new AuditReportVersion)->forceFill(['audit_report_id' => $report->id, 'revision' => $report->revision,
                'status' => $report->status, 'step' => $report->step, 'snapshot' => $snapshot,
                'sha256' => hash('sha256', $this->json->encode($snapshot)), 'actor_party_id' => $partyId,
                'actor_user_id' => $actorId, 'command' => $command])->save();
        }
    }
}
