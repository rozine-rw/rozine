<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The designed Audit Partner network (`admin/parties`, kind auditor) from live records: the four
 * figures, the chips with their counts, the first sixty matches and one partner's 360 with their
 * licence, engagements and accreditation history. Fields the platform does not record yet are not
 * invented: no firm, ICPAR member ID or district is captured, no on-time record is kept and no audit
 * fee has been paid, so those read null; no partner can be frozen. A licence waiting for review
 * carries verify and reject, which run the accreditation review that already exists.
 *
 * @phpstan-import-type DirectoryRow from \App\Application\Auditor\Contracts\AuditorDirectoryStore
 * @phpstan-import-type Counts from \App\Application\Auditor\Contracts\AuditorDirectoryStore
 * @phpstan-import-type Detail from \App\Application\Auditor\Contracts\AuditorDirectoryStore
 *
 * @phpstan-type Page array{
 *     rows: list<DirectoryRow>, matching: int, counts: Counts, licences_expiring: int, chip: string, search: string,
 *     partner: Detail|null, roles: list<string>, permissions: list<string>
 * }
 */
class StaffAuditorDirectoryResource extends JsonResource
{
    private const CHIPS = ['all', 'active', 'pending', 'licence_expired'];

    /** The frame names the most privileged role the viewer holds; every role here may verify partners. */
    private const ROLES = ['superadmin', 'compliance', 'approver'];

    /** How a partner's standing reads in the 360's header. */
    private const HEALTH = ['active' => 'active', 'pending' => 'kyc_pending', 'licence_expired' => 'watch', 'suspended' => 'distressed'];

    /** How each accreditation event reads in the 360's activity. */
    private const EVENTS = [
        'submitted' => ['Submitted a licence for verification', 'blue'], 'renewal_submitted' => ['Submitted a licence renewal', 'blue'],
        'withdrawn' => ['Withdrew a licence submission', 'grey'], 'availability' => ['Changed availability for audits', 'grey'],
        'approved' => ['Licence verified', 'green'], 'rejected' => ['Licence rejected', 'red'],
        'suspended' => ['Standing suspended', 'red'], 'revoked' => ['Standing revoked', 'red'],
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $link = fn (array $query): array => ['url' => route($prefix.'staff.auditors.index', $query, false), 'method' => 'get'];
        $filters = array_filter(['q' => $page['search']], fn (string $value): bool => $value !== '');
        $position = array_filter([...$filters, 'chip' => $page['chip'] === 'all' ? '' : $page['chip']], fn (string $value): bool => $value !== '');
        $counts = $page['counts'];
        $name = (string) $request->user()?->name;
        $verify = ! $request->routeIs('api.*') || $request->user()?->tokenCan('staff:auditors:verify') === true;

        return ['contract_version' => 'staff-auditor-directory-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => mb_strtoupper(mb_substr($name, 0, 1)), 'role' => current(array_intersect(self::ROLES, $page['roles'])) ?: 'approver'],
            'nav' => (new StaffNavigationResource($page['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'], 'kind' => 'auditor', 'policy' => [],
            'stats' => [['key' => 'partners', 'value' => ['kind' => 'count', 'value' => $counts['all']]],
                ['key' => 'active_partners', 'value' => ['kind' => 'count', 'value' => $counts['active']]],
                ['key' => 'pending_partners', 'value' => ['kind' => 'count', 'value' => $counts['pending']]],
                ['key' => 'licences_expiring', 'value' => ['kind' => 'count', 'value' => $page['licences_expiring']]]],
            'chips' => array_map(fn (string $chip): array => ['key' => $chip, 'count' => $counts[$chip], 'link' => $link([...$filters, ...($chip === 'all' ? [] : ['chip' => $chip])]),
                'active' => $chip === $page['chip']], self::CHIPS),
            'filters' => [], 'shown' => count($page['rows']), 'total' => $page['matching'],
            'directory' => ['kind' => 'auditor', 'rows' => array_map(fn (array $row): array => ['id' => $row['party_id'], 'name' => $row['name'],
                'firm' => null, 'licence' => $row['licence'], 'district' => null, 'active_engagements' => $row['active_engagements'],
                'on_time_pct' => null, 'share_mtd' => null, 'standing' => $row['standing'], 'frozen' => false,
                'link' => $link([...$position, 'auditor' => $row['party_id']])], $page['rows'])],
            'party' => $page['partner'] === null ? null : self::partner($page['partner'], $link($position), $verify ? $prefix : null)];
    }

    /**
     * @param  Detail  $partner
     * @param  array{url: string, method: string}  $close
     * @param  string|null  $prefix  the route prefix for licence decisions, or null when the viewer may not decide
     * @return array<string, mixed>
     */
    private static function partner(array $partner, array $close, ?string $prefix): array
    {
        $row = $partner['row'];
        $licence = $partner['licence'];
        $waiting = $prefix !== null && $licence !== null && $licence['submission_id'] !== null;
        $decide = fn (string $decision): array => ['url' => route((string) $prefix.'staff.auditors.licence.'.$decision, ['party' => $row['party_id']], false), 'method' => 'post'];

        return ['id' => $row['party_id'], 'kind' => 'auditor', 'name' => $row['name'], 'subtitle' => $row['email'],
            'health' => self::HEALTH[$row['standing']],
            'stats' => [['key' => 'engagements', 'value' => ['kind' => 'count', 'value' => $row['active_engagements']]]],
            'list' => ['key' => 'engagements', 'rows' => array_map(fn (array $engagement): array => ['id' => $engagement['id'], 'title' => $engagement['business'],
                'tone' => 'blue', 'detail' => ['kind' => 'text', 'value' => $engagement['district']]], $partner['engagements'])],
            'history' => array_map(fn (array $entry): array => ['id' => $entry['id'], 'at' => $entry['at'], 'actor' => $entry['actor'],
                'action' => ['code' => 'accreditation.'.$entry['event'], 'label' => self::EVENTS[$entry['event']][0], 'tone' => self::EVENTS[$entry['event']][1]],
                'reason' => $entry['reason']], $partner['history']),
            'kyc' => null,
            'licence' => $licence === null ? null : ['member_id' => null, 'licence' => $licence['licence'], 'expires_on' => $licence['expires_on'],
                'district' => null, 'state' => $licence['state'],
                'review' => $licence['submission_id'] === null ? null : ['revision' => $licence['revision'], 'submission_id' => $licence['submission_id']]],
            'freeze' => null, 'release_blocked' => null, 'restrictions' => [], 'links' => ['close' => $close],
            'actions' => $waiting ? ['verify_licence' => $decide('approve'), 'reject_licence' => $decide('reject')] : (object) []];
    }
}
