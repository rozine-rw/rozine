<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `AdminMessagingProps` (`staff-messaging-v1`): the History tab of Messaging & Automations. Each
 * row is one notice receipt: who it reached, the notice's kind (the client words its title from
 * the same catalog the participant saw) and category, the automation that sent it, the channel
 * and whether it arrived. Every notice today comes from a recorded event, so each went out by
 * automation. `older` opens the next page of receipts; `newest` returns to the first.
 *
 * @phpstan-import-type FiledDelivery from \App\Application\Identity\BrowseMessageHistory
 *
 * @phpstan-type Page array{
 *     deliveries: list<FiledDelivery>, older: string|null, search: string, paged: bool, roles: list<string>, permissions: list<string>
 * }
 */
class StaffMessagingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $query = $page['search'] === '' ? [] : ['q' => $page['search']];
        $link = fn (array $parameters): array => ['url' => route($prefix.'staff.messaging.index', $parameters, false), 'method' => 'get'];

        return ['contract_version' => 'staff-messaging-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => (new StaffViewerResource($page['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($page['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'],
            'history' => array_map(fn (array $delivery): array => ['id' => $delivery['id'], 'recipient' => $delivery['recipient'],
                'recipient_kind' => $delivery['recipient_kind'], 'kind' => $delivery['kind'],
                'category' => $delivery['category'], 'source' => 'automation',
                'channel' => $delivery['channel'], 'status' => $delivery['status'], 'at' => $delivery['delivered_at']], $page['deliveries']),
            'older' => $page['older'] === null ? null : $link([...$query, 'before' => $page['older']]),
            'newest' => $page['paged'] ? $link($query) : null];
    }
}
