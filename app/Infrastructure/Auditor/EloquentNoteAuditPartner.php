<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\NoteAuditPartner;
use App\Models\AuditReport;
use Illuminate\Database\Query\JoinClause;

/** The author of the latest sealed audit report on the application a campaign was raised from. */
final class EloquentNoteAuditPartner implements NoteAuditPartner
{
    public function forCampaign(string $campaignId): ?array
    {
        $report = AuditReport::query()
            ->join('business_campaigns as campaigns', fn (JoinClause $join): JoinClause => $join->on('campaigns.business_application_id', '=', 'audit_reports.application_id'))
            ->where('campaigns.id', $campaignId)->where('audit_reports.status', 'sealed')
            ->orderByDesc('audit_reports.id')->first(['audit_reports.id', 'audit_reports.author_party_id']);

        return $report === null ? null : ['report_id' => $report->id, 'party_id' => $report->author_party_id];
    }
}
