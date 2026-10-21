<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * `AdminPrimaryMarketProps` (`staff-primary-market-v1`) from the live raises. The four figures
 * follow the design's monitor over every listed raise: how many are filling, what they have raised
 * so far, their combined fill of target, and the share of ended raises that reached target, each 0
 * when there is nothing to measure yet. The trend's bars are the cash committed per Kigali day, or per month over a long window. No raise has a moderation command, so none is offered.
 *
 * The search matches the reference, the business, the sector and the district; the limit then cuts
 * the list, newest first.
 *
 * @phpstan-import-type Raise from \App\Application\Operations\BrowsePrimaryMarket
 * @phpstan-import-type Bar from \App\Application\Operations\Contracts\OperationsDashboardStore
 */
class StaffPrimaryMarketResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{raises: list<Raise>, ended: array{funded: int, failed: int}, trend: array{from: string, to: string|null, grain: 'day'|'month', bars: list<Bar>}, search: string, limit: int, roles: list<string>, permissions: list<string>} $resource */
        $resource = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $route = fn (array $query = []): array => ['url' => route($prefix.'staff.primary-market.index', $query, false), 'method' => 'get'];

        $raised = BigInteger::zero();
        $target = BigInteger::zero();
        $items = [];
        foreach ($resource['raises'] as $raise) {
            $raised = $raised->plus($raise['raised']['amount']);
            $target = $target->plus($raise['target']['amount']);
            $items[] = ['id' => $raise['campaign_id'], 'reference' => 'RSE-'.Str::upper(Str::substr($raise['campaign_id'], -6)),
                'business' => $raise['name'], 'sector' => $raise['industry'], 'district' => $raise['district'], 'rating' => $raise['rating'],
                'rate_pct' => $raise['rate_pct'], 'term_months' => $raise['term_months'], 'funded_pct' => $raise['funded_pct'],
                'raised' => $raise['raised'], 'target' => $raise['target'], 'investors' => $raise['investors'], 'lifecycle' => $raise['lifecycle'],
                'opened_at' => $raise['starts_at'], 'closes_at' => $raise['closes_at'], 'days_left' => $raise['days_left']];
        }
        $ended = $resource['ended']['funded'] + $resource['ended']['failed'];
        $share = fn (BigInteger|int $part, BigInteger|int $whole): string => BigInteger::of($whole)->isZero() ? '0'
            : (string) BigInteger::of($part)->multipliedBy(100)->dividedBy($whole, RoundingMode::HalfUp);

        $search = $resource['search'];
        $matched = $search === '' ? $items : array_values(array_filter($items, fn (array $item): bool => Str::contains(
            $item['reference'].' '.$item['business'].' '.$item['sector'].' '.$item['district'], $search, true)));
        $query = array_filter(['q' => $search === '' ? null : $search]);

        return ['contract_version' => 'staff-primary-market-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'viewer' => (new StaffViewerResource($resource['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($resource['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $search,
            'stats' => [['key' => 'active', 'value' => ['kind' => 'count', 'value' => count($items)]],
                ['key' => 'in_flight', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => (string) $raised]]],
                ['key' => 'fill', 'value' => ['kind' => 'percent', 'value' => $share($raised, $target)]],
                ['key' => 'completion', 'value' => ['kind' => 'percent', 'value' => $share($resource['ended']['funded'], $ended)]]],
            'trend' => ['from' => $resource['trend']['from'], 'to' => $resource['trend']['to'], 'grain' => $resource['trend']['grain'], 'bars' => array_map(fn (array $bar): array => ['label' => $bar['label'],
                'amount' => ['currency' => 'RWF', 'amount' => $bar['amount']], 'height_pct' => $bar['height_pct']], $resource['trend']['bars'])],
            'raises' => array_slice($matched, 0, $resource['limit']),
            'pagination' => ['next' => count($matched) > $resource['limit'] && $resource['limit'] < 500 ? $route([...$query, 'limit' => 500]) : null]];
    }
}
