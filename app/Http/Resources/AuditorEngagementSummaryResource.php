<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @phpstan-import-type Summary from \App\Application\Auditor\GetAuditEngagementSummary */
class AuditorEngagementSummaryResource extends JsonResource
{
    /** @return array{status: 'current'|'required'|'unavailable', link: array{url: string, method: 'get'}} */
    public function toArray(Request $request): array
    {
        /** @var Summary $summary */
        $summary = $this->resource;
        $route = $request->routeIs('api.*') ? 'api.v1.auditor.engagement.show' : 'auditor.engagement.show';

        return ['status' => $summary['status'], 'link' => ['url' => route($route, [], false), 'method' => 'get']];
    }
}
