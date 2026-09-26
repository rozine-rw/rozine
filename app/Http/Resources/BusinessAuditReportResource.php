<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessAuditReportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $report = $page['report'];
        $parameters = ['business' => $page['business']['id'], 'report' => $report['id']];
        $prefix = self::prefix($request);
        $canSign = $page['can_cosign'] && (! $request->routeIs('api.*') || $request->user()?->tokenCan('business:command'));
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $operation = self::link($prefix.'operations.show', ['request_id' => $placeholder]);
        $operation['url'] = str_replace($placeholder, '{request_id}', $operation['url']);
        $home = self::link($request->routeIs('api.*') ? 'api.v1.business.index' : 'business.home');
        $report['findings'] = array_map(function (array $finding): array {
            $values = AuditorJobsResource::figures(array_intersect_key($finding, array_flip(['reported', 'observed', 'difference'])));

            return ['code' => $finding['code'], 'title' => match ($finding['measure']) {
                'stock_value' => __('Stock value'), 'cash' => __('Cash balance'), 'stock_units' => __('Inventory units'), default => __('Operating status'),
            }, 'body' => isset($finding['reported']) ? __('Reported: :reported. Observed: :observed. Difference: :difference.', $values)
                : __('Observed status: :observed.', $values), 'evidence_ids' => $finding['evidence_ids']];
        }, $report['findings']);
        $report['seal']['verification'] = self::link($request->routeIs('api.*') ? 'api.v1.audit.seals.verify' : 'audit.seals.verify', ['report' => $report['id']]);

        return ['contract_version' => 'business-audit-report-v1', 'identity_context_revision' => $page['identity_context_revision'],
            'server_time' => now()->toIso8601String(), 'business' => $page['business'], 'report' => $report, 'cosign' => $page['cosign'],
            'allowed_actions' => $canSign ? ['report.cosign'] : [],
            'actions' => ['cosign' => $canSign ? ['url' => route($prefix.'cosign', $parameters, false), 'method' => 'post'] : null],
            'links' => ['current' => self::link($prefix.'show', $parameters), 'close' => $home, 'operation' => $operation],
            'shell_links' => ['home' => $home, 'launcher' => self::link($request->routeIs('api.*') ? 'api.v1.identity.show' : 'dashboard'),
                'reports' => null, 'profile' => null]];
    }

    public static function prefix(Request $request): string
    {
        return $request->routeIs('api.*') ? 'api.v1.business.audit-reports.' : 'business.audit-reports.';
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: string}
     */
    public static function link(string $name, array $parameters = []): array
    {
        return ['url' => route($name, $parameters, false), 'method' => 'get'];
    }
}
