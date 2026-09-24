<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @phpstan-import-type AuditApplication from \App\Application\Business\Contracts\BusinessApplicationStore
 * @phpstan-import-type Page from AuditorJobsResource
 *
 * @phpstan-type File array{record: AuditApplication, jobs: Page}
 */
class AuditorFileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var File $file */
        $file = $this->resource;
        $record = $file['record'];
        $job = AuditorJobsResource::job($record);

        return [...AuditorJobsResource::envelope($file['jobs']['identity_context_revision']), 'allowed_actions' => $job['allowed_actions'],
            'job' => $job, 'actions' => AuditorJobsResource::actions($request, $job['id']), 'decline_options' => AuditorJobsResource::declineOptions(),
            'links' => ['close' => AuditorJobsResource::link('auditor.jobs.index'), 'procedure' => null, 'operation' => AuditorJobsResource::links($request)['operation']],
            'jobs' => (new AuditorJobsResource($file['jobs']))->resolve($request), 'blocked' => null,
            'file' => ['raise' => ['requested' => AuditorJobsResource::money($record['application']['target'] ?? null),
                'term_months' => $record['application']['term_months'] ?? null, 'return_pct' => null,
                'sector' => AuditorJobsResource::sector($record['work']['business']['industry']),
                'use_of_funds' => implode(', ', $record['application']['use_of_funds'] ?? [])],
                'documents' => [], 'prescreen' => [], 'reason' => null, 'mandate' => [], 'history' => ['last_audit' => null, 'flags' => []]]];
    }
}
