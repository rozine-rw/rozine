<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @phpstan-import-type AuditApplication from \App\Application\Business\Contracts\BusinessApplicationStore
 * @phpstan-import-type Page from AuditorJobsResource
 * @phpstan-import-type Report from \App\Application\Auditor\Contracts\AuditReportStore
 * @phpstan-import-type Document from \App\Application\Evidence\Contracts\StatementStore
 * @phpstan-import-type StatementReview from \App\Application\Auditor\GetAuditStatementReview
 *
 * @phpstan-type File array{record: AuditApplication, jobs: Page, report?: Report|null, can_start?: bool, documents?: list<Document>|null, review?: StatementReview|null}
 *
 * The file states why the field visit is needed and what it must clear from the rules that govern
 * it (BRS v2.0 AUD-R03 and AUD-R07 for a Flash Audit, AUD-R04 for a monthly visit), which match
 * the procedure's own steps. The statements on file are listed once the work is the partner's,
 * each with when it arrived and whether its text was extracted; an offer lists none, and the
 * application's own prose is never shown. An accepted statement review (#396) also carries what its
 * verification binds: each statement to open, the Business's figures on file and the checks to make.
 */
class AuditorFileResource extends JsonResource
{
    /** The brief each assignment kind reads: the app holds its reason and mandate in every locale. */
    private const array BRIEF = ['flash' => 'flash', 'routine' => 'monthly'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var File $file */
        $file = $this->resource;
        $record = $file['record'];
        $job = AuditorJobsResource::job($record);
        $prefix = $request->routeIs('api.*') ? 'api.v1.auditor.' : 'auditor.';
        $report = $file['report'] ?? null;
        $canCommand = ! $request->routeIs('api.*') || $request->user()?->tokenCan('auditor:command') === true;
        $canStart = ($file['can_start'] ?? false) && $canCommand;
        // The statement review is worked on the web for now; the API file leaves it out.
        $review = $request->routeIs('api.*') ? null : self::review($file['review'] ?? null, $record['work']['assignment']['id']);
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $operation = AuditorJobsResource::link($prefix.'reports.operations.show', ['request_id' => $placeholder]);
        $operation['url'] = str_replace($placeholder, '{request_id}', $operation['url']);

        $reportState = self::reportState($record['work']['report'] ?? null);
        // A sealed report's file offers no conflict declaration: the work is done and the seal is never rewritten (#588).
        $allowed = $reportState === null ? $job['allowed_actions'] : array_values(array_diff($job['allowed_actions'], ['conflict.declare']));

        return [...AuditorJobsResource::envelope($file['jobs']['identity_context_revision']), 'allowed_actions' => [...$allowed, ...($canStart ? ['audit.start'] : []), ...(($review['verify'] ?? null) !== null ? ['statement.verify'] : [])],
            'engagement' => (new AuditorEngagementSummaryResource($file['jobs']['engagement']))->resolve($request),
            'job' => [...$job, 'allowed_actions' => $allowed, 'report_state' => $reportState], 'actions' => [...AuditorJobsResource::actions($request, $job['id']),
                'start' => $canStart ? ['url' => route($prefix.'reports.start', ['assignment' => $job['id']], false), 'method' => 'post'] : null],
            'application' => $record['application'] === null ? null : ['id' => $record['application']['id'], 'revision' => $record['application']['revision']],
            'decline_options' => AuditorJobsResource::declineOptions(),
            'links' => ['close' => AuditorJobsResource::links($request)['jobs'],
                'procedure' => $report === null ? null : AuditorJobsResource::link($prefix.'reports.show', ['report' => $report['id']]),
                'start_operation' => $operation, 'operation' => AuditorJobsResource::links($request)['operation']],
            'jobs' => (new AuditorJobsResource($file['jobs']))->resolve($request), 'blocked' => null,
            'file' => ['raise' => ['requested' => AuditorJobsResource::money($record['application']['target'] ?? null),
                'term_months' => $record['application']['term_months'] ?? null, 'return_pct' => null,
                'sector' => AuditorJobsResource::sector($record['work']['business']['industry']),
                'use_of_funds' => implode(', ', $record['application']['use_of_funds'] ?? [])],
                'documents' => array_map(self::document(...), $file['documents'] ?? []), 'prescreen' => [],
                'brief' => $record['work']['assignment']['purpose'] === 'statement_review'
                    ? 'statement_review' : self::BRIEF[$record['work']['assignment']['kind']] ?? null,
                'history' => ['last_audit' => null, 'flags' => []]], 'review' => $review];
    }

    /**
     * The statement review's working file: the revisions its verification binds, each statement with
     * where to open it, the Business's saved monthly figures and the procedure's checks. It can be
     * verified only once the Business has saved its figures.
     *
     * @param  StatementReview|null  $review
     * @return array<string, mixed>|null
     */
    private static function review(?array $review, string $assignmentId): ?array
    {
        if ($review === null) {
            return null;
        }
        $file = $review['file'];
        $transcription = $file['transcription'];
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $operation = AuditorJobsResource::link('auditor.jobs.verification.operations.show', ['request_id' => $placeholder]);
        $operation['url'] = str_replace($placeholder, '{request_id}', $operation['url']);

        return ['assignment_revision' => $file['assignment']['revision'], 'evidence_revision' => $file['evidence']['revision'],
            'verification_revision' => $review['verification_revision'], 'procedure_version' => $review['procedure_version'], 'checks' => $review['checks'],
            'documents' => array_map(fn (array $document): array => ['id' => $document['id'], 'filename' => $document['filename'], 'sha256' => $document['sha256'],
                'received_on' => CarbonImmutable::parse($document['received_at'])->setTimezone('Africa/Kigali')->format('Y-m-d'),
                'open' => AuditorJobsResource::link('auditor.jobs.statements.show', ['assignment' => $assignmentId, 'document' => $document['id']])], $file['evidence']['documents']),
            'transcription' => $transcription === null ? null : ['id' => $transcription['id'], 'sha256' => $transcription['sha256'],
                'months' => $transcription['payload']['months'],
                'observations' => array_map(fn (array $observation): array => ['month' => $observation['month'], 'operating_inflow' => $observation['operating_inflow'],
                    'operating_outflow' => $observation['operating_outflow'], 'owner_draw' => $observation['owner_draw'], 'debt_service' => $observation['debt_service']],
                    $transcription['payload']['observations']),
                // Each account's own opening and closing balance, so the "balances match" check can be run here (#511).
                'balances' => array_map(fn (array $statement): array => ['account' => $statement['rail_id'], 'month' => $statement['month'],
                    'opening_balance' => $statement['opening_balance'], 'closing_balance' => $statement['closing_balance']],
                    $transcription['payload']['statements'])],
            'verify' => $transcription !== null
                ? ['url' => route('auditor.jobs.verification.store', ['assignment' => $assignmentId], false), 'method' => 'post'] : null,
            'operation' => $operation];
    }

    /**
     * Where a sealed report stands, as Jobs shows it (#490): awaiting the Business's co-signature,
     * disputed and under desk review, escalated to Rozine, or published. Null while it is not sealed,
     * when the file's countdown still runs.
     *
     * @param  array{status: string, publication_status: string|null}|null  $report
     */
    private static function reportState(?array $report): ?string
    {
        if ($report === null || $report['status'] !== 'sealed') {
            return null;
        }

        return in_array($report['publication_status'], ['disputed', 'escalated', 'published'], true) ? $report['publication_status'] : 'awaiting_cosign';
    }

    /**
     * @param  Document  $document
     * @return array{kind: string, format: string, status: string, received_on: string, records: int|null}
     */
    private static function document(array $document): array
    {
        return ['kind' => 'statement', 'format' => $document['media_type'] === 'application/pdf' ? 'pdf' : 'csv',
            'status' => $document['extraction']['status'] === 'text_extracted' ? 'parsed' : 'present',
            'received_on' => CarbonImmutable::parse($document['received_at'])->setTimezone('Africa/Kigali')->format('Y-m-d'),
            'records' => $document['extraction']['record_count']];
    }
}
