<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * `AdminComplaintsProps` (`staff-complaints-v1`) from the live complaints read (BRS v2.0 CMP-R03).
 * Each complaint carries its reference, customer, status, both clocks and the clock it is past; it
 * opens in the drawer with its whole conversation and, while unresolved, the answer form on the
 * web. The figures count every complaint, whatever the search; the search matches the reference and
 * the customer.
 *
 * @phpstan-import-type Listed from \App\Application\Identity\BrowseComplaints
 */
class StaffComplaintsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{complaints: list<Listed>, search: string, open: string|null, roles: list<string>, permissions: list<string>} $resource */
        $resource = $this->resource;
        $api = $request->routeIs('api.*');
        $prefix = $api ? 'api.v1.' : '';
        $search = $resource['search'];
        $complaints = $resource['complaints'];
        $count = fn (int $value): array => ['kind' => 'count', 'value' => $value];
        $query = array_filter(['q' => $search === '' ? null : $search]);
        $link = fn (array $extra = []): array => ['url' => route($prefix.'staff.complaints.index', [...$query, ...$extra], false), 'method' => 'get'];

        $rows = array_map(fn (array $thread): array => ['id' => $thread['id'], 'reference' => $thread['reference'], 'customer' => $thread['customer'],
            'role' => $thread['role'], 'status' => $thread['status'], 'late' => $thread['late'], 'opened_at' => $thread['opened_at'],
            'acknowledge_by' => $thread['acknowledge_by'], 'resolve_by' => $thread['resolve_by'], 'messages' => count($thread['messages']),
            'link' => $link(['complaint' => $thread['id']])], $complaints);
        $open = $resource['open'] === null ? null : Arr::first($complaints, fn (array $thread): bool => $thread['id'] === Str::lower((string) $resource['open']));

        return ['contract_version' => 'staff-complaints-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'viewer' => (new StaffViewerResource($resource['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($resource['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $search,
            'stats' => [['key' => 'unresolved', 'value' => $count(count(array_filter($complaints, fn (array $thread): bool => $thread['status'] !== 'resolved')))],
                ['key' => 'awaiting', 'value' => $count(count(array_filter($complaints, fn (array $thread): bool => $thread['status'] === 'awaiting')))],
                ['key' => 'late', 'value' => $count(count(array_filter($complaints, fn (array $thread): bool => $thread['late'] !== null)))]],
            'complaints' => $search === '' ? $rows : array_values(array_filter($rows,
                fn (array $row): bool => Str::contains($row['reference'].' '.$row['customer'], $search, true))),
            'complaint' => $open === null ? null : ['id' => $open['id'], 'reference' => $open['reference'], 'customer' => $open['customer'], 'role' => $open['role'],
                'status' => $open['status'], 'late' => $open['late'], 'opened_at' => $open['opened_at'], 'acknowledge_by' => $open['acknowledge_by'],
                'resolve_by' => $open['resolve_by'], 'messages' => $open['messages'], 'close' => $link(),
                'answer' => $api || $open['status'] === 'resolved' ? null
                    : ['url' => route('staff.complaints.answer', ['complaint' => $open['id']], false), 'method' => 'post']]];
    }
}
