<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * `AdminNotesProps` (`staff-notes-v1`) from the Notes register. The four figures follow the design:
 * every note, the raises still live, the notes repaying, and capital raised (issued Holding
 * principal, the dashboard's figure). Each tab carries its server count; the search matches the
 * reference, the business and the sector, the list is newest first or sorted largest first by
 * capital raised, yield, fill, investors or term, then the limit cuts it.
 *
 * @phpstan-import-type NoteRow from \App\Application\Operations\BrowseNotes
 */
class StaffNotesResource extends JsonResource
{
    private const array TABS = ['all', 'live', 'repaying', 'matured', 'failed'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{notes: list<NoteRow>, capital_raised: string, tab: string, search: string, sort: string, limit: int, roles: list<string>, permissions: list<string>} $resource */
        $resource = $this->resource;
        $items = array_map(fn (array $note): array => ['id' => $note['campaign_id'], 'reference' => 'NTE-'.Str::upper(Str::substr($note['campaign_id'], -6)),
            'business' => $note['name'], 'sector' => $note['industry'], 'state' => $note['state'], 'rate_pct' => $note['rate_pct'],
            'term_months' => $note['term_months'], 'funded_pct' => $note['funded_pct'], 'raised' => $note['raised'], 'target' => $note['target'],
            'investors' => $note['investors'], 'listed_at' => $note['live_at']], $resource['notes']);
        $sort = $resource['sort'];
        if ($sort !== 'newest') {
            $key = fn (array $item): BigDecimal => BigDecimal::of(match ($sort) {
                'raised' => $item['raised']['amount'], 'yield' => $item['rate_pct'], 'funded' => $item['funded_pct'],
                'investors' => (string) $item['investors'], default => (string) $item['term_months'],
            });
            // Largest first; notes that tie keep the register's newest-first order.
            $order = array_flip(array_column($items, 'id'));
            usort($items, fn (array $a, array $b): int => $key($b)->compareTo($key($a)) ?: $order[$a['id']] <=> $order[$b['id']]);
        }
        $search = $resource['search'];
        $matched = $search === '' ? $items : array_values(array_filter($items, fn (array $item): bool => Str::contains(
            $item['reference'].' '.$item['business'].' '.$item['sector'], $search, true)));
        $count = fn (string $tab): int => $tab === 'all' ? count($matched) : count(array_filter($matched, fn (array $item): bool => $item['state'] === $tab));
        $shown = $resource['tab'] === 'all' ? $matched : array_values(array_filter($matched, fn (array $item): bool => $item['state'] === $resource['tab']));
        $query = array_filter(['q' => $search === '' ? null : $search, 'sort' => $sort === 'newest' ? null : $sort]);
        $route = fn (array $parameters): array => ['url' => route('staff.notes.index', $parameters, false), 'method' => 'get'];
        $byState = fn (string $state): int => count(array_filter($items, fn (array $item): bool => $item['state'] === $state));

        return ['contract_version' => 'staff-notes-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'viewer' => (new StaffViewerResource($resource['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($resource['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $search, 'sort' => $sort,
            'stats' => [['key' => 'total', 'value' => ['kind' => 'count', 'value' => count($items)]],
                ['key' => 'live', 'value' => ['kind' => 'count', 'value' => $byState('live')]],
                ['key' => 'repaying', 'value' => ['kind' => 'count', 'value' => $byState('repaying')]],
                ['key' => 'raised', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => $resource['capital_raised']]]]],
            'tabs' => array_map(fn (string $tab): array => ['key' => $tab, 'count' => $count($tab),
                'link' => $route(array_filter([...$query, 'tab' => $tab === 'all' ? null : $tab])), 'active' => $tab === $resource['tab']], self::TABS),
            'notes' => array_slice($shown, 0, $resource['limit']),
            'pagination' => ['next' => count($shown) > $resource['limit'] && $resource['limit'] < 500
                ? $route(array_filter([...$query, 'tab' => $resource['tab'] === 'all' ? null : $resource['tab'], 'limit' => 500])) : null]];
    }
}
