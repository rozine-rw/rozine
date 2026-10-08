<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The designed Business directory (`admin/parties`, kind business) from live records: the four
 * figures, the chips with their counts, the sector and sort filters, the first sixty matches and one
 * Business's 360. Fields the platform does not record yet are not invented: no capacity read exists,
 * so capacity is null; no arrears or freeze read exists, so every Business reads healthy and open;
 * and no eligibility policy is published, so the policy strip is empty. A Business with no rated
 * note reads as pending its audit, and the average is of published scores only.
 *
 * @phpstan-import-type DirectoryRow from \App\Application\Business\Contracts\BusinessDirectoryStore
 * @phpstan-import-type Counts from \App\Application\Business\Contracts\BusinessDirectoryStore
 * @phpstan-import-type Detail from \App\Application\Business\Contracts\BusinessDirectoryStore
 *
 * @phpstan-type Page array{
 *     rows: list<DirectoryRow>, matching: int, counts: Counts, active_notes: int, raised: string, average_score: string|null,
 *     sectors: list<string>, chip: string, sort: string, sector: string, search: string, business: Detail|null,
 *     roles: list<string>, permissions: list<string>
 * }
 */
class StaffBusinessDirectoryResource extends JsonResource
{
    private const CHIPS = ['all', 'healthy', 'watch', 'distressed', 'frozen'];

    /** The frame names the most privileged role the viewer holds. */
    private const ROLES = ['superadmin', 'compliance', 'approver', 'analyst'];

    /** How each record in a Business's history reads in its 360. */
    private const COMMANDS = [
        'business.authority.configure' => ['Authority recorded', 'blue'], 'business.authority.revoke' => ['Authority revoked', 'red'],
        'campaign.publish' => ['Note published', 'green'], 'campaign.cancel' => ['Note cancelled', 'red'],
        'campaign.expire' => ['Note closed unfunded at its deadline', 'amber'],
    ];

    /** A note's tone beside its state in the 360's list. */
    private const NOTE_TONES = ['active' => 'green', 'funded' => 'blue', 'failed' => 'red'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $link = fn (array $query): array => ['url' => route($prefix.'staff.businesses.index', $query, false), 'method' => 'get'];
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
        $filters = array_filter(['q' => $page['search'], 'sector' => $page['sector'], 'sort' => $page['sort'] === 'raised' ? '' : $page['sort']],
            fn (string $value): bool => $value !== '');
        $position = array_filter([...$filters, 'chip' => $page['chip'] === 'all' ? '' : $page['chip']], fn (string $value): bool => $value !== '');
        $counts = $page['counts'];
        $name = (string) $request->user()?->name;
        $sectors = $page['sectors'] === [] ? [] : [['key' => 'sector', 'value' => $page['sector'] === '' ? 'all' : $page['sector'],
            'options' => [['value' => 'all', 'label' => 'All sectors'], ...array_map(fn (string $sector): array => ['value' => $sector, 'label' => $sector], $page['sectors'])]]];

        return ['contract_version' => 'staff-business-directory-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => mb_strtoupper(mb_substr($name, 0, 1)), 'role' => current(array_intersect(self::ROLES, $page['roles'])) ?: 'analyst'],
            'nav' => (new StaffNavigationResource($page['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'], 'kind' => 'business', 'policy' => [],
            'stats' => [['key' => 'total_businesses', 'value' => ['kind' => 'count', 'value' => $counts['all']]],
                ['key' => 'avg_rating', 'value' => ['kind' => 'text', 'value' => $page['average_score'] === null ? '—' : $page['average_score'].' / 5']],
                ['key' => 'active_notes', 'value' => ['kind' => 'count', 'value' => $page['active_notes']]],
                ['key' => 'capital_raised', 'value' => ['kind' => 'money', 'value' => $money($page['raised'])]]],
            'chips' => array_map(fn (string $chip): array => ['key' => $chip, 'count' => $counts[$chip], 'link' => $link([...$filters, ...($chip === 'all' ? [] : ['chip' => $chip])]),
                'active' => $chip === $page['chip']], self::CHIPS),
            'filters' => [...$sectors, ['key' => 'sort', 'value' => $page['sort'],
                'options' => [['value' => 'raised', 'label' => 'Sort: Capital raised'], ['value' => 'name', 'label' => 'Sort: Name']]]],
            'shown' => count($page['rows']), 'total' => $page['matching'],
            'directory' => ['kind' => 'business', 'rows' => array_map(fn (array $row): array => ['id' => $row['business_id'], 'name' => $row['name'],
                'sector' => $row['sector'], 'rating' => $row['rating'], 'active_notes' => $row['active_notes'], 'investors' => $row['investors'],
                'raised' => $money($row['raised']), 'capacity_used_pct' => null, 'health' => 'healthy', 'frozen' => false, 'kyc' => $row['kyc'],
                'link' => $link([...$position, 'business' => $row['business_id']])], $page['rows'])],
            'party' => $page['business'] === null ? null : self::business($page['business'], $link($position))];
    }

    /**
     * @param  Detail  $business
     * @param  array{url: string, method: string}  $close
     * @return array<string, mixed>
     */
    private static function business(array $business, array $close): array
    {
        $row = $business['row'];

        return ['id' => $row['business_id'], 'kind' => 'business', 'name' => $row['name'],
            'subtitle' => implode(' · ', array_filter([$row['sector'], $row['company_code'] === null ? '' : 'RDB '.$row['company_code'], $row['district']],
                fn (string $part): bool => $part !== '')),
            'health' => 'healthy',
            'stats' => [['key' => 'active_notes', 'value' => ['kind' => 'count', 'value' => $row['active_notes']]],
                ['key' => 'investors', 'value' => ['kind' => 'count', 'value' => $row['investors']]],
                ['key' => 'raised', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => $row['raised']]]],
                ['key' => 'rating', 'value' => ['kind' => 'rating', 'value' => $row['rating']]]],
            'list' => ['key' => 'active_notes', 'rows' => array_map(fn (array $note): array => ['id' => $note['id'], 'title' => $note['title'],
                'tone' => self::NOTE_TONES[$note['status']], 'detail' => ['kind' => 'note_status', 'value' => $note['status']]], $business['notes'])],
            'history' => array_map(fn (array $entry): array => ['id' => $entry['id'], 'at' => $entry['at'], 'actor' => $entry['actor'],
                'action' => ['code' => $entry['command'], 'tone' => self::COMMANDS[$entry['command']][1],
                    'label' => self::COMMANDS[$entry['command']][0].($entry['subject'] === null ? '' : ' · '.$entry['subject'])],
                'reason' => $entry['reason']], $business['history']),
            'kyc' => ['state' => $row['kyc'], 'due_on' => null], 'licence' => null, 'freeze' => null, 'release_blocked' => null, 'restrictions' => [],
            'links' => ['close' => $close], 'actions' => (object) []];
    }
}
