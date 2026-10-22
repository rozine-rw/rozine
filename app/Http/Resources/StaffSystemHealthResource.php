<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `AdminSystemHealthProps` (`staff-system-health-v1`) from the monitored signals, worst first:
 * critical, then watch, then healthy, then the signals the platform does not track yet. A signal
 * that needs a look links to the page that resolves it, for a viewer who may open that page: the
 * ledger to reconciliation, the underwriting queue to the application queue. The overall status
 * counts the tracked signals only. The page filters the signals by the search itself, since it
 * matches their translated labels.
 *
 * @phpstan-import-type Signal from \App\Application\Operations\CheckSystemHealth
 */
class StaffSystemHealthResource extends JsonResource
{
    private const array RANK = ['critical' => 0, 'watch' => 1, 'healthy' => 2, 'untracked' => 3];

    /** @var array<string, array{permission: string, route: string}> */
    private const array RESOLVED_BY = [
        'ledger' => ['permission' => 'reconciliation.view', 'route' => 'staff.reconciliation.index'],
        'queue' => ['permission' => 'applications.review', 'route' => 'staff.applications.index'],
        'frozen' => ['permission' => 'staff.manage', 'route' => 'staff.staff.index'],
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{signals: list<Signal>, search: string, roles: list<string>, permissions: list<string>} $resource */
        $resource = $this->resource;
        $signals = $resource['signals'];
        usort($signals, fn (array $a, array $b): int => self::RANK[$a['status']] <=> self::RANK[$b['status']]);

        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $link = function (array $signal) use ($resource, $prefix): ?array {
            $target = self::RESOLVED_BY[$signal['key']] ?? null;

            return $target === null || in_array($signal['status'], ['healthy', 'untracked'], true) || ! in_array($target['permission'], $resource['permissions'], true)
                ? null : ['url' => route($prefix.$target['route'], [], false), 'method' => 'get'];
        };
        $count = fn (string $status): int => count(array_filter($signals, fn (array $signal): bool => $signal['status'] === $status));

        return ['contract_version' => 'staff-system-health-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'viewer' => (new StaffViewerResource($resource['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($resource['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $resource['search'],
            'overall' => ['critical' => $count('critical'), 'watch' => $count('watch')],
            'signals' => array_map(fn (array $signal): array => [...$signal, 'action' => $link($signal)], $signals)];
    }
}
