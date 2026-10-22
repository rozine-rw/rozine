<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * `AdminPaymentsProps` (`staff-payments-v1`) from the money that moved. The four figures follow the
 * design's Payment Center over the last fourteen Kigali days: credited deposits, paid-out
 * withdrawals and closed disbursements with their counts, and how many withdrawals are still in
 * flight. The bars are deposits plus payouts per Kigali day. No payout has a release, hold or
 * reverse command, so none is offered.
 *
 * The search matches the reference, the owner's name and the account; the limit then cuts the list,
 * oldest first.
 *
 * @phpstan-import-type Bar from \App\Application\Operations\Contracts\OperationsDashboardStore
 * @phpstan-import-type Movements from \App\Application\Operations\Contracts\OperationsDashboardStore
 * @phpstan-import-type Payout from \App\Application\Operations\Contracts\OperationsDashboardStore
 */
class StaffPaymentsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{from: string, movements: Movements, bars: list<Bar>, payouts: list<Payout>, search: string, limit: int, roles: list<string>, permissions: list<string>, policy: list<array{key: string, value: string}>} $resource */
        $resource = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $route = fn (array $query = []): array => ['url' => route($prefix.'staff.payments.index', $query, false), 'method' => 'get'];
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
        $movements = $resource['movements'];

        $items = array_map(fn (array $payout): array => ['id' => $payout['id'], 'reference' => 'WDR-'.Str::upper(Str::substr($payout['id'], -6)),
            'owner' => $payout['owner'], 'name' => $payout['name'], 'amount' => $money($payout['amount']), 'received' => $money($payout['received']),
            'account' => $payout['account'], 'state' => $payout['state'], 'requested_at' => $payout['requested_at']], $resource['payouts']);
        $search = $resource['search'];
        $matched = $search === '' ? $items : array_values(array_filter($items, fn (array $item): bool => Str::contains(
            $item['reference'].' '.($item['name'] ?? '').' '.$item['account'], $search, true)));
        $query = array_filter(['q' => $search === '' ? null : $search]);

        return ['contract_version' => 'staff-payments-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'viewer' => (new StaffViewerResource($resource['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($resource['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $search, 'from' => $resource['from'], 'policy' => $resource['policy'],
            'stats' => [['key' => 'deposits', 'value' => $money($movements['deposits']['amount']), 'count' => $movements['deposits']['count']],
                ['key' => 'withdrawals', 'value' => $money($movements['withdrawals']['amount']), 'count' => $movements['withdrawals']['count']],
                ['key' => 'disbursed', 'value' => $money($movements['disbursed']['amount']), 'count' => $movements['disbursed']['count']]],
            'in_flight' => count($items),
            'bars' => array_map(fn (array $bar): array => ['label' => $bar['label'], 'amount' => $money($bar['amount']), 'height_pct' => $bar['height_pct']], $resource['bars']),
            'payouts' => array_slice($matched, 0, $resource['limit']),
            'pagination' => ['next' => count($matched) > $resource['limit'] && $resource['limit'] < 500 ? $route([...$query, 'limit' => 500]) : null]];
    }
}
