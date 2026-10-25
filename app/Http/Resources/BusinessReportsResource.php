<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessReportsProps`: the Reports tab. It lists the Business's latest sealed report, monthly or
 * the pre-listing flash report, when there is one, under Published once it is published and In
 * audit until then, opening on its own co-sign page. While it waits for the business's signature it
 * says so, with the end of its co-sign window when it has one. No inflow, health or seal date is
 * read for it, so each is null. The monthly review closes a fixed time after delivery rather than
 * on a day of the month, so no audit-cycle day is published and `policy` is null: the guide then
 * names no day.
 */
class BusinessReportsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        $business = (string) $data['business_id'];
        $latest = $data['latest'];
        $reports = ['verified' => [], 'in_audit' => [], 'archived' => []];
        if ($latest !== null) {
            $status = $latest['status'] === 'published' ? 'verified' : 'in_audit';
            $period = $latest['period'] === null ? ['kind' => 'flash', 'starts_on' => null] : ['kind' => 'monthly', 'starts_on' => $latest['period'].'-01'];
            $reports[$status][] = ['id' => $latest['id'], 'period' => $period, 'status' => $status,
                'inflow' => null, 'health' => null, 'auditor' => $latest['auditor'], 'seal_by' => null,
                'awaiting_signature' => $latest['status'] === 'pending', 'cosign_by' => $latest['cosign_by'],
                'link' => BusinessHomeResource::link($request, 'business.audit-reports.show', ['business' => $business, 'report' => $latest['id']])];
        }
        $tabs = BusinessHomeResource::tabs($request, $business);

        return ['reports' => $reports, 'report' => null, 'policy' => null,
            'links' => ['home' => BusinessHomeResource::link($request, 'business.show', ['business' => $business]), ...$tabs,
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'close' => $tabs['reports']]];
    }
}
