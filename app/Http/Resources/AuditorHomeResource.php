<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Auditor Home (auditor-filing-v1, design L86–203) from the reads the Auditor app already serves:
 * the accreditation facts and dispatch availability the Profile shows, the engagement summary, the
 * partner's work as Jobs reads it, and their filed reports. A fact with no record yet stays null
 * and the page shows it as unavailable or leaves it out — the firm and professional body, the
 * quality score, earnings, active deals, variance and the wallet —
 * and destinations that do not exist yet (statement, withdraw) are null, so their controls stay
 * hidden. The bell opens the partner's own notices. Recent activity is the partner's own filings,
 * newest first. The on-time share and clock expiries are the partner's own track record.
 *
 * @phpstan-import-type AuditApplication from \App\Application\Business\Contracts\BusinessApplicationStore
 * @phpstan-import-type Summary from \App\Application\Auditor\GetAuditEngagementSummary
 * @phpstan-import-type FiledReports from \App\Application\Auditor\Contracts\AuditorFiledReportStore
 * @phpstan-import-type Accreditation from AuditorProfileResource
 * @phpstan-import-type TrackRecord from \App\Application\Auditor\Contracts\AuditorFiledReportStore
 *
 * @phpstan-type Home array{accreditation: Accreditation, name: string, engagement: Summary, jobs: array{data: list<AuditApplication>, next_cursor: string|null}, reports: FiledReports, unread_notifications?: int, track_record?: TrackRecord}
 */
class AuditorHomeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Home $page */
        $page = $this->resource;
        $facts = $page['accreditation'];
        /** @var array{expires_on: string|null} $accreditation */
        $accreditation = $facts['accreditation'];
        /** @var array{eligible: list<array{distance_km: string|null}>, assigned: list<array<string, mixed>>} $jobs */
        $jobs = (new AuditorJobsResource([...$page['jobs'], 'identity_context_revision' => $facts['identity_context_revision'],
            'limit' => 25, 'engagement' => $page['engagement']]))->resolve($request);
        $open = AuditorJobsResource::openJobs($page['jobs']);
        $distances = array_filter(array_column($jobs['eligible'], 'distance_km'), fn (?string $distance): bool => $distance !== null);

        return [
            'contract_version' => $facts['contract_version'],
            'identity_context_revision' => $facts['identity_context_revision'],
            'server_time' => $facts['server_time'],
            // Home offers one command, the dispatch switch; the accreditation commands live on Profile.
            'allowed_actions' => array_values(array_intersect($facts['allowed_actions'], ['availability.update'])),
            'engagement' => (new AuditorEngagementSummaryResource($page['engagement']))->resolve($request),
            'auditor' => ['name' => $page['name'], 'firm' => null, 'accreditation' => null, 'avatar_url' => null, 'since_year' => null],
            'quality_score' => null,
            'earned_this_month' => null,
            'active_deals' => null,
            'licence_expires_on' => $accreditation['expires_on'],
            'availability' => [...$facts['availability'], 'update' => ['url' => route('auditor.availability.update', [], false), 'method' => 'post']],
            'nearby' => ['count' => $open, 'closest_km' => $open === 0 || $distances === [] ? null : min($distances)],
            'in_progress' => $jobs['assigned'],
            'standing' => [...$facts['standing'], 'on_time_pct' => $page['track_record']['on_time_pct'] ?? null, 'avg_variance_pct' => null, 'variance_flagged' => false,
                'jobs_done' => $page['reports']['counts']['all'], 'clock_expiries' => $page['track_record']['clock_expiries'] ?? null],
            'activity' => array_map(fn (array $report): array => ['kind' => 'report_filed', 'at' => $report['sealed_at'],
                'business' => $report['business'], 'variance_pct' => null], $page['reports']['data']),
            'wallet' => ['available' => null],
            'unread_notifications' => $page['unread_notifications'] ?? 0,
            'links' => [...AuditorJobsResource::links($request), 'operation' => AuditorProfileResource::operationLink('auditor.'),
                'statement' => null, 'withdraw' => null, 'notifications' => ['url' => route('auditor.notices', [], false), 'method' => 'get']],
        ];
    }
}
