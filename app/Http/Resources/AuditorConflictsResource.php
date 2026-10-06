<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @phpstan-import-type OwnConflict from \App\Application\Auditor\Contracts\AuditAssignmentStore
 *
 * @phpstan-type Page array{data: list<OwnConflict>, next_cursor: string|null, identity_context_revision: int, limit: int}
 */
class AuditorConflictsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;

        return [...AuditorJobsResource::envelope($page['identity_context_revision']), 'conflicts' => $page['data'],
            'links' => AuditorJobsResource::links($request),
            'pagination' => AuditorJobsResource::pagination($request, 'conflicts.index', $page['next_cursor'], $page['limit'])];
    }
}
