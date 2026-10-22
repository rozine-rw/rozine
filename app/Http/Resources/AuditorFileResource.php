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
 *
 * @phpstan-type File array{record: AuditApplication, jobs: Page, report?: Report|null, can_start?: bool, documents?: list<Document>|null}
 *
 * The file states why the field visit is needed and what it must clear from the rules that govern
 * it (BRS v2.0 AUD-R03 and AUD-R07 for a Flash Audit, AUD-R04 for a monthly visit), which match
 * the procedure's own steps. The statements on file are listed once the work is the partner's,
 * each with when it arrived and whether its text was extracted; an offer lists none, and the
 * application's own prose is never shown.
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
        $canStart = ($file['can_start'] ?? false) && (! $request->routeIs('api.*') || $request->user()?->tokenCan('auditor:command'));
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $operation = AuditorJobsResource::link($prefix.'reports.operations.show', ['request_id' => $placeholder]);
        $operation['url'] = str_replace($placeholder, '{request_id}', $operation['url']);

        return [...AuditorJobsResource::envelope($file['jobs']['identity_context_revision']), 'allowed_actions' => [...$job['allowed_actions'], ...($canStart ? ['audit.start'] : [])],
            'engagement' => (new AuditorEngagementSummaryResource($file['jobs']['engagement']))->resolve($request),
            'job' => $job, 'actions' => [...AuditorJobsResource::actions($request, $job['id']),
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
                'brief' => self::BRIEF[$record['work']['assignment']['kind']] ?? null,
                'history' => ['last_audit' => null, 'flags' => []]]];
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
