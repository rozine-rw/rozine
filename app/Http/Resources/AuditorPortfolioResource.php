<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Auditor Portfolio (auditor-filing-v1, MVP-AUDITOR-SCR-07 and AC-08). Reports are the
 * partner's sealed filings: `published` once publication completes and `awaiting_cosign` until
 * then, as Jobs already reads a sealed report. Lateness and a review rejection are not served by
 * any read yet, so neither status nor its filter is sent. The conflict register offers each
 * accepted assignment under its own `allowed_actions`; the declaration goes to that assignment's
 * own command, whose `{assignment}` the page fills in. Declarations on record come from the
 * partner's private conflict read, which names no Business or note, so neither is sent. The
 * audit calendar reads what the partner owes from the same work: every accepted assignment with a
 * due date whose report is not sealed yet, linked to its file (a sealed one is already a report).
 * Earnings, origination, yield-share and managed deals have no read yet, so nothing is sent for
 * them and the page shows the design's empty states.
 *
 * @phpstan-import-type AuditApplication from \App\Application\Business\Contracts\BusinessApplicationStore
 * @phpstan-import-type FiledReport from \App\Application\Auditor\Contracts\AuditorFiledReportStore
 * @phpstan-import-type FiledReports from \App\Application\Auditor\Contracts\AuditorFiledReportStore
 * @phpstan-import-type ConflictPage from \App\Application\Auditor\Contracts\AuditAssignmentStore
 *
 * @phpstan-type Portfolio array{identity_context_revision: int, filter: string, reports: FiledReports, jobs: array{data: list<AuditApplication>, next_cursor: string|null}, conflicts: ConflictPage}
 */
class AuditorPortfolioResource extends JsonResource
{
    /** The report filters the page offers, the first being every filed report. */
    public const FILTERS = ['all', 'awaiting_cosign', 'published'];

    /** A syntactically valid assignment ID, swapped for the literal token the page fills in. */
    private const ASSIGNMENT_PLACEHOLDER = '00000000000000000000000000';

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Portfolio $page */
        $page = $this->resource;
        $counts = $page['reports']['counts'];
        $counts = [...$counts, 'awaiting_cosign' => $counts['all'] - $counts['published']];
        $files = [];
        $owed = [];
        foreach ($page['jobs']['data'] as $record) {
            $job = AuditorJobsResource::job($record);
            if ($job['state'] === 'assigned') {
                $files[] = ['id' => $job['id'], 'revision' => $job['revision'], 'business' => $job['business'], 'note_id' => null, 'allowed_actions' => $job['allowed_actions']];
                if ($job['deadline'] !== null && (($record['work']['report'] ?? null)['status'] ?? null) !== 'sealed') {
                    $owed[] = ['id' => $job['id'], 'business' => $job['business'], 'district' => $job['district'], 'kind' => $job['kind'],
                        'due_at' => $job['deadline']['due_at'], 'link' => AuditorJobsResource::link('auditor.jobs.show', ['assignment' => $job['id']])];
                }
            }
        }
        $declare = route('auditor.jobs.conflict', ['assignment' => self::ASSIGNMENT_PLACEHOLDER], false);

        return [...AuditorJobsResource::envelope($page['identity_context_revision']),
            'reports' => array_map(fn (array $report): array => self::report($report), $page['reports']['data']),
            'filter' => $page['filter'],
            'filters' => array_map(fn (string $key): array => ['key' => $key, 'count' => $counts[$key],
                'link' => AuditorJobsResource::link('auditor.portfolio.index', $key === self::FILTERS[0] ? [] : ['filter' => $key])], self::FILTERS),
            'conflicts' => ['files' => $files,
                'record' => array_map(fn (array $entry): array => ['conflict_id' => $entry['conflict']['conflict_id'], 'assignment_id' => $entry['assignment_id'],
                    'business' => null, 'note_id' => null, 'kind' => $entry['conflict']['kind'], 'declared_on' => $entry['conflict']['declared_at']], $page['conflicts']['data']),
                'declare' => ['url' => str_replace(self::ASSIGNMENT_PLACEHOLDER, '{assignment}', $declare), 'method' => 'post']],
            'owed' => $owed,
            'outcome' => null,
            'open_jobs' => AuditorJobsResource::openJobs($page['jobs']),
            'links' => AuditorJobsResource::links($request)];
    }

    /**
     * @param  FiledReport  $report
     * @return array<string, mixed>
     */
    private static function report(array $report): array
    {
        return ['id' => $report['id'], 'business' => $report['business'], 'kind' => $report['kind'],
            'month' => $report['period'] === null ? null : $report['period'].'-01', 'district' => $report['district'],
            'filed_on' => $report['sealed_at'], 'due_on' => $report['due_at'], 'status' => $report['published'] ? 'published' : 'awaiting_cosign',
            'late_days' => null, 'rejection' => null, 'link' => AuditorJobsResource::link('auditor.reports.show', ['report' => $report['id']])];
    }
}
