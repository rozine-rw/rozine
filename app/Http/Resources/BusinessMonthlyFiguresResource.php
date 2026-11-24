<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessMonthlyFiguresProps`: the monthly submission (BRS v3.0 VIS-R04) for the month the open
 * monthly audit covers. Six screens: money in, costs in the CPA's six categories, paid to owners,
 * next month's forecast pre-filled with the verified 3-month average, four yes/no changes, then a
 * one-line summary to send. `sent` is what was sent for that month; once sent it is final and the
 * page only shows it. Amounts are whole Rwandan francs.
 *
 * @phpstan-import-type Page from \App\Application\Business\GetMonthlyFigures
 */
class BusinessMonthlyFiguresResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $data */
        $data = $this->resource;
        $business = $data['business_id'];
        /** @var array{period: string, seal_by: string} $window */
        $window = $data['window'];
        $sent = $data['submission'];

        return ['identity_context_revision' => $data['identity_context_revision'], 'period' => $window['period'], 'seal_by' => $window['seal_by'],
            'forecast_period' => $data['forecast_period'].'-01', 'forecast_basis' => $data['forecast_basis'],
            'cost_categories' => $data['cost_categories'],
            'sent' => $sent === null ? null : [...$sent['figures'], 'submitted_at' => $sent['submitted_at']],
            'links' => ['submit' => ['url' => route('business.monthly-figures.store', ['business' => $business], false), 'method' => 'post'],
                'close' => BusinessHomeResource::link($request, 'business.audit-prep', ['business' => $business]),
                'home' => BusinessHomeResource::link($request, 'business.show', ['business' => $business]), ...BusinessHomeResource::tabs($request, $business),
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get']]];
    }
}
