<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessApplicationsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $prefix = BusinessApplicationResource::prefix($request);
        $entries = array_map(function (array $entry) use ($request, $prefix): array {
            $allowed = $request->routeIs('api.*') && ! $request->user()?->tokenCan('business:command') ? [] : $entry['allowed_actions'];
            $application = $entry['application'];
            if ($application !== null) {
                $application['link'] = ['url' => route($prefix.'show', ['business' => $entry['business_id'], 'application' => $application['id']], false), 'method' => 'get'];
            }

            $report = $entry['audit_report'] ?? null;
            if ($report !== null) {
                $report['link'] = ['url' => route($request->routeIs('api.*') ? 'api.v1.business.audit-reports.show' : 'business.audit-reports.show',
                    ['business' => $entry['business_id'], 'report' => $report['id']], false), 'method' => 'get'];
            }

            return ['audit_report' => $report, 'business_id' => $entry['business_id'], 'name' => $entry['name'], 'allowed_actions' => $allowed,
                'application' => $application, 'actions' => ['create' => in_array('application.create', $allowed, true)
                    ? ['url' => route($prefix.'create', ['business' => $entry['business_id']], false), 'method' => 'post'] : null]];
        }, $page['entries']);
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $lookup = str_replace($placeholder, '{request_id}', route($prefix.'operations.show', ['request_id' => $placeholder], false));
        $next = $page['next_cursor'] === null ? null : ['url' => route($request->routeIs('api.*') ? 'api.v1.business.index' : 'business.home',
            ['before' => $page['next_cursor'], 'limit' => $page['limit'], 'identity_context_revision' => $page['identity_context_revision']], false), 'method' => 'get'];

        return ['identity_context_revision' => $page['identity_context_revision'], 'entries' => $entries,
            'operation' => ['url' => $lookup, 'method' => 'get'], 'pagination' => ['next' => $next]];
    }
}
