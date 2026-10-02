<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffApplicationsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $route = ($request->routeIs('api.*') ? 'api.v1.' : '').'staff.applications.index';
        $filters = ['tab' => $page['tab'], 'search' => $page['search'], 'limit' => $page['limit']];
        $position = array_filter([...$filters, 'before' => $page['before']], fn (mixed $value): bool => $value !== null);
        $link = fn (array $query): array => ['url' => route($route, $query, false), 'method' => 'get'];
        $review = null;
        if ($page['selected'] !== null) {
            $release = (new StaffApplicationReleaseResource($page['release_page']))->resolve($request)['release'];
            $review = [...$page['selected'], 'state' => $release['state'] === 'released' ? 'approved' : 'submitted',
                'factors' => [], 'audit' => ['state' => null, 'sealed_at' => null], 'reviewer' => null, 'trail' => [],
                'links' => ['close' => $link($position), 'business' => null], 'actions' => (object) [], 'release' => $release];
        }
        $name = $request->user()->name;

        return ['contract_version' => 'staff-applications-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()->getAuthIdentifier(), 'name' => $name, 'email' => $request->user()->email,
                'initials' => mb_strtoupper(mb_substr($name, 0, 1)), 'role' => in_array('superadmin', $page['roles'], true) ? 'superadmin' : 'approver'],
            'nav' => ['applications' => $link([]), 'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'],
                'today' => null, 'disbursements' => null, 'repayments' => null, 'businesses' => null, 'investors' => null,
                'auditors' => null, 'staff' => null, 'ledger' => null, 'events' => null],
            'badges' => ['applications' => $page['counts']['pending'], 'disbursements' => null], 'policy' => [],
            'search' => $page['search'], 'active_tab' => $page['tab'], 'tabs' => array_map(fn (string $tab): array => ['key' => $tab, 'count' => $page['counts'][$tab], 'link' => $link([...$filters, 'tab' => $tab])], ['pending', 'approved']),
            'applications' => array_map(function (array $entry) use ($link, $position): array {
                unset($entry['capacity'], $entry['use_of_funds']);

                return [...$entry, 'link' => $link([...$position, 'application' => $entry['id']]), 'approve_link' => null];
            }, $page['entries']), 'review' => $review, 'stage' => null,
            'pagination' => ['next' => $page['next_cursor'] === null ? null : $link([...$filters, 'before' => $page['next_cursor']])],
            'links' => ['operation' => StaffApplicationReleaseResource::lookup($request), 'changes' => $request->routeIs('api.*') ? null
                : ['url' => route('staff.changes.index', ['topics' => 'staff_queue', 'after' => (string) $page['changes_cursor']], false), 'method' => 'get']]];
    }
}
