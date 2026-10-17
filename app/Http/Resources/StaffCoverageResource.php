<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * `AdminCoverageProps` (`staff-coverage-v1`) from the live coverage read. Each district a Business
 * operates in carries the server's count of reachable partners and open audits and its verdict;
 * a district with partners opens the Audit Partner directory. The figures count every district,
 * whatever the search; the search matches the district and its province.
 *
 * @phpstan-import-type District from \App\Application\Auditor\Contracts\AuditCoverage
 */
class StaffCoverageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{districts: list<District>, search: string, roles: list<string>, permissions: list<string>} $resource */
        $resource = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $search = $resource['search'];
        $districts = $resource['districts'];
        $count = fn (int $value): array => ['kind' => 'count', 'value' => $value];

        $rows = array_map(fn (array $row): array => ['id' => 'dst_'.Str::lower($row['district']), 'district' => $row['district'], 'province' => $row['province'],
            'active_partners' => $row['active_partners'], 'audits_open' => $row['audits_open'], 'capacity' => $row['covered'] ? 'covered' : 'uncovered',
            'link' => $row['active_partners'] > 0 ? ['url' => route($prefix.'staff.auditors.index', [], false), 'method' => 'get'] : null], $districts);

        return ['contract_version' => 'staff-coverage-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'viewer' => (new StaffViewerResource($resource['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($resource['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $search,
            'stats' => [['key' => 'districts', 'value' => $count(count($districts))],
                ['key' => 'uncovered', 'value' => $count(count(array_filter($districts, fn (array $row): bool => ! $row['covered'])))],
                ['key' => 'audits_open', 'value' => $count(array_sum(array_column($districts, 'audits_open')))]],
            'districts' => $search === '' ? $rows : array_values(array_filter($rows,
                fn (array $row): bool => Str::contains($row['district'].' '.$row['province'], $search, true)))];
    }
}
