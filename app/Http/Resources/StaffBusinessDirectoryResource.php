<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * The designed Business directory (`admin/parties`, kind business) from live records: the four
 * figures, the chips with their counts, the sector and sort filters, the first sixty matches and one
 * Business's 360. Fields the platform does not record yet are not invented: no capacity read exists,
 * so capacity is null; no arrears or freeze read exists, so health reads not tracked, frozen is null
 * and the only chip is All. The policy strip shows who may apply (`PlatformPolicies::businesses()`).
 * A viewer who verifies Businesses may onboard one; the notice after it says whether its representative
 * can open the Business app yet.
 * A Business with no rated
 * note reads as pending its audit, and the average is of published scores only. A viewer who manages
 * audit assignments sees the audit open on the Business and, in the console, may request a Flash audit
 * when none is open or redispatch one that waits on Audit Operations.
 *
 * @phpstan-import-type DirectoryRow from \App\Application\Business\Contracts\BusinessDirectoryStore
 * @phpstan-import-type Counts from \App\Application\Business\Contracts\BusinessDirectoryStore
 * @phpstan-import-type Detail from \App\Application\Business\Contracts\BusinessDirectoryStore
 * @phpstan-import-type ReviewedLocation from \App\Application\Auditor\GetAuditLocation
 * @phpstan-import-type Engagement from \App\Application\Auditor\GetBusinessAuditEngagement
 * @phpstan-import-type NearbyPartner from \App\Application\Auditor\GetBusinessAuditEngagement
 *
 * @phpstan-type Page array{
 *     rows: list<DirectoryRow>, matching: int, counts: Counts, active_notes: int, raised: string, average_score: string|null,
 *     sectors: list<string>, chip: string, sort: string, sector: string, search: string, business: Detail|null, premises?: ReviewedLocation|null, audit?: array{engagement: Engagement|null, closed: bool, partners: list<NearbyPartner>}|null,
 *     roles: list<string>, permissions: list<string>, policy: list<array{key: string, value: string}>,
 *     onboarded?: array{business_id: string, representative: string, business_access: bool}|null, onboarding_sectors?: list<string>
 * }
 */
class StaffBusinessDirectoryResource extends JsonResource
{
    /** The frame names the most privileged role the viewer holds. */
    private const ROLES = ['superadmin', 'compliance', 'approver', 'analyst'];

    /** How each record in a Business's history reads in its 360. */
    private const COMMANDS = [
        'business.authority.configure' => ['Authority recorded', 'blue'], 'business.authority.revoke' => ['Authority revoked', 'red'],
        'campaign.publish' => ['Note published', 'green'], 'campaign.cancel' => ['Note cancelled', 'red'],
        'campaign.expire' => ['Note closed unfunded at its deadline', 'amber'],
        'campaign.failed_closing' => ['Funded note closed after failure', 'red'],
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
                'initials' => StaffViewerResource::initials($name), 'role' => current(array_intersect(self::ROLES, $page['roles'])) ?: 'analyst'],
            'nav' => (new StaffNavigationResource($page['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'], 'kind' => 'business', 'policy' => $page['policy'],
            'stats' => [['key' => 'total_businesses', 'value' => ['kind' => 'count', 'value' => $counts['all']]],
                ['key' => 'avg_rating', 'value' => ['kind' => 'text', 'value' => $page['average_score'] === null ? '—' : $page['average_score'].' / 5']],
                ['key' => 'active_notes', 'value' => ['kind' => 'count', 'value' => $page['active_notes']]],
                ['key' => 'capital_raised', 'value' => ['kind' => 'money', 'value' => $money($page['raised'])]]],
            // Health chips wait for an arrears and freeze read, so All is the only one.
            'chips' => [['key' => 'all', 'count' => $counts['all'], 'link' => $link($filters), 'active' => true]],
            'filters' => [...$sectors, ['key' => 'sort', 'value' => $page['sort'],
                'options' => [['value' => 'raised', 'label' => 'Sort: Capital raised'], ['value' => 'active_notes', 'label' => 'Sort: Active notes'],
                    ['value' => 'investors', 'label' => 'Sort: Investors'], ['value' => 'name', 'label' => 'Sort: Name (A–Z)']]]],
            'shown' => count($page['rows']), 'total' => $page['matching'],
            'onboard' => $prefix === '' && in_array('businesses.verify', $page['permissions'], true)
                ? ['url' => route('staff.businesses.onboard', [], false), 'method' => 'post', 'effective_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'),
                    'sectors' => $page['onboarding_sectors'] ?? [], 'districts' => config('rwanda.districts'), 'latest_year' => now()->year] : null,
            'onboarded' => $page['onboarded'] ?? null,
            'directory' => ['kind' => 'business', 'rows' => array_map(fn (array $row): array => ['id' => $row['business_id'], 'name' => $row['name'],
                'sector' => $row['sector'], 'rating' => $row['rating'], 'active_notes' => $row['active_notes'], 'investors' => $row['investors'],
                'raised' => $money($row['raised']), 'capacity_used_pct' => null, 'health' => 'not_tracked', 'frozen' => null, 'kyc' => $row['kyc'],
                'link' => $link([...$position, 'business' => $row['business_id']])], $page['rows'])],
            'party' => $page['business'] === null ? null : [...self::business($page['business'], $link($position)),
                'premises' => ($page['premises'] ?? null) === null ? null : StaffAuditorDirectoryResource::location($page['premises'], $prefix === ''
                    ? ['url' => route('staff.businesses.premises.verify', ['business' => $page['business']['row']['business_id']], false), 'method' => 'post'] : null),
                'audit' => ($page['audit'] ?? null) === null ? null : self::audit($page['audit']['engagement'], $page['audit']['closed'], $page['audit']['partners'], $page['business']['row']['business_id'], $prefix === '')]];
    }

    /**
     * @param  Engagement|null  $engagement
     * @param  list<NearbyPartner>  $partners
     * @return array<string, mixed>
     */
    private static function audit(?array $engagement, bool $closed, array $partners, string $businessId, bool $console): array
    {
        $allowed = $engagement === null ? [] : $engagement['allowed_actions'];
        $action = fn (string $decision, string $route): ?array => $console && $engagement !== null && in_array('audit.assignment.'.$decision, $allowed, true)
            ? ['url' => route($route, ['business' => $businessId, 'assignment' => $engagement['id']], false), 'method' => 'post'] : null;
        $reason = $engagement['operations_reason'] ?? null;

        return ['engagement' => $engagement === null ? null : ['id' => $engagement['id'], 'kind' => $engagement['kind'], 'status' => $engagement['status'],
            'revision' => $engagement['revision'], 'attempt' => $engagement['attempt'], 'operations_reason' => $reason],
            // A Business whose last audit Audit Operations closed can't be sent a new one from here.
            'closed' => $engagement === null && $closed,
            'request_flash' => $console && $engagement === null && ! $closed ? ['url' => route('staff.businesses.audit.flash', ['business' => $businessId], false), 'method' => 'post'] : null,
            // Redispatch is offered only when a partner can take the audit now; otherwise it would change nothing.
            'redispatch' => $reason === 'AUDIT_PARTNER_AVAILABLE' ? $action('redispatch', 'staff.businesses.audit.redispatch') : null,
            'close' => $action('close', 'staff.businesses.audit.close'),
            'remedy' => $console && $reason === 'AUDIT_NO_PARTNER_IN_RANGE' ? ['url' => route('staff.auditors.index', [], false), 'method' => 'get'] : null,
            // The review is checked as of when the drawer issued the form, so an exact retry replays it.
            'partners' => array_map(fn (array $partner): array => ['party_id' => $partner['party_id'], 'name' => $partner['name'], 'reviewed' => $partner['reviewed'],
                'revision' => $partner['revision'], 'review' => $console ? ['url' => route('staff.businesses.audit.independence',
                    ['business' => $businessId, 'party' => $partner['party_id']], false), 'method' => 'post'] : null,
                'checked_at' => now('UTC')->format('Y-m-d\TH:i:s\Z')], $partners)];
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
            'subtitle' => implode(' · ', array_filter([$row['sector'], $row['company_code'] === null ? '' : (Str::startsWith(Str::upper($row['company_code']), 'RDB') ? $row['company_code'] : 'RDB '.$row['company_code']), $row['district']],
                fn (string $part): bool => $part !== '')),
            'health' => 'not_tracked',
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
