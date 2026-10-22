<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The designed Staff & Roles directory (`admin/parties`, kind staff) from live staff accounts: the
 * three figures, the chips with their counts, every operator with their most privileged role and
 * all their roles, what each role may do (the permission matrix), and one operator's 360. A disabled staff account reads as frozen, attributed to the identity audit
 * entry that disabled it; its enable and disable entries are its freezes and releases. Sign-ins
 * are not recorded, so the 360 shows no last sign-in. A viewer with `staff.manage` may add an
 * operator (`grant`) and suspend or reactivate any other operator from the 360; the admin action log
 * lists the latest staff access changes across every operator. `access` counts how many of the
 * capabilities a role can hold the viewer holds, for the "Signed in as" line.
 *
 * @phpstan-type StaffRow array{user_id: int, name: string, email: string, roles: list<string>, enabled: bool}
 * @phpstan-type Counts array{all: int, active: int, frozen: int, approvers: int}
 * @phpstan-type HistoryEntry array{
 *     id: string, at: string, actor: string|null, action: string, reason: string, before: array<string, mixed>, after: array<string, mixed>
 * }
 * @phpstan-type Member array{row: StaffRow, history: list<HistoryEntry>}
 * @phpstan-type LogEntry array{id: string, at: string, actor: string|null, target: string, enabled: bool, granted: bool, roles: list<string>, reason: string}
 * @phpstan-type Page array{
 *     rows: list<StaffRow>, matching: int, counts: Counts, chip: string, search: string, member: Member|null,
 *     pending: list<array{id: string, recipient_id: int, requester_id: int, recipient: string, email: string, requester: string, operation: string, reason: string}>,
 *     matrix: list<array{key: string, roles: list<string>, withheld?: string}>, roles: list<string>, permissions: list<string>, log: list<LogEntry>
 * }
 */
class StaffDirectoryResource extends JsonResource
{
    private const CHIPS = ['all', 'active', 'frozen'];

    /** Role names as the 360 states them. */
    private const ROLE_NAMES = ['superadmin' => 'Super-admin', 'compliance' => 'Compliance', 'approver' => 'Approver', 'treasury' => 'Treasury', 'analyst' => 'Analyst'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $link = fn (array $query): array => ['url' => route($prefix.'staff.staff.index', $query, false), 'method' => 'get'];
        $filters = array_filter(['q' => $page['search']], fn (string $value): bool => $value !== '');
        $position = [...$filters, ...($page['chip'] === 'all' ? [] : ['chip' => $page['chip']])];
        $counts = $page['counts'];
        $viewer = (string) $request->user()?->getAuthIdentifier();
        $held = array_filter($page['matrix'], fn (array $row): bool => ! isset($row['withheld']));

        return ['contract_version' => 'staff-directory-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => (new StaffViewerResource($page['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($page['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'], 'kind' => 'staff', 'policy' => [],
            'stats' => [['key' => 'operators', 'value' => ['kind' => 'count', 'value' => $counts['all']]],
                ['key' => 'approvers', 'value' => ['kind' => 'count', 'value' => $counts['approvers']]],
                ['key' => 'frozen_accounts', 'value' => ['kind' => 'count', 'value' => $counts['frozen']]]],
            'chips' => array_map(fn (string $chip): array => ['key' => $chip, 'count' => $counts[$chip],
                'link' => $link([...$filters, ...($chip === 'all' ? [] : ['chip' => $chip])]), 'active' => $chip === $page['chip']], self::CHIPS),
            'filters' => [], 'shown' => count($page['rows']), 'total' => $page['matching'],
            'directory' => ['kind' => 'staff', 'rows' => array_map(fn (array $row): array => ['id' => (string) $row['user_id'], 'name' => $row['name'],
                'email' => $row['email'], 'initials' => StaffViewerResource::initials($row['name']), 'role' => StaffViewerResource::role($row['roles']),
                'roles' => array_values(array_intersect(StaffViewerResource::ROLES, $row['roles'])),
                'frozen' => ! $row['enabled'], 'you' => (string) $row['user_id'] === $viewer,
                'link' => $link([...$position, 'operator' => $row['user_id']])], $page['rows'])],
            'party' => $page['member'] === null ? null : self::member($page['member'], $link($position),
                in_array('staff.manage', $page['permissions'], true) && (string) $page['member']['row']['user_id'] !== $viewer && $prefix === ''),
            'permission_matrix' => $page['matrix'],
            'access' => ['granted' => count(array_filter($held, fn (array $row): bool => in_array($row['key'], $page['permissions'], true))), 'total' => count($held)],
            'grant' => in_array('staff.manage', $page['permissions'], true) && $prefix === '' ? ['url' => route('staff.staff.grant', [], false), 'method' => 'post'] : null,
            'pending_approvals' => array_map(fn (array $proposal): array => ['id' => $proposal['id'], 'recipient' => $proposal['recipient'],
                'email' => $proposal['email'], 'requester' => $proposal['requester'], 'operation' => $proposal['operation'], 'reason' => $proposal['reason'],
                'approve' => $prefix === '' && in_array('staff.manage', $page['permissions'], true)
                    && (string) $proposal['requester_id'] !== $viewer && (string) $proposal['recipient_id'] !== $viewer
                    ? ['url' => route('staff.staff.approve', ['proposal' => $proposal['id']], false), 'method' => 'post'] : null], $page['pending']),
            'action_log' => array_map(fn (array $entry): array => ['id' => $entry['id'], 'at' => $entry['at'], 'actor' => $entry['actor'] ?? 'Server console',
                'target' => $entry['target'], 'action' => $entry['granted'] ? 'staff_granted' : ($entry['enabled'] ? 'staff_enabled' : 'staff_suspended'),
                'role' => $entry['granted'] ? StaffViewerResource::role($entry['roles']) : null, 'reason' => $entry['reason']], $page['log'])];
    }

    /**
     * @param  Member  $member
     * @param  array{url: string, method: string}  $close
     * @return array<string, mixed>
     */
    private static function member(array $member, array $close, bool $manage): array
    {
        $row = $member['row'];
        $actor = fn (?string $name): string => $name ?? 'Server console';
        $roles = array_values(array_intersect(StaffViewerResource::ROLES, $row['roles']));
        // Every time the account's enabled flag changed: disabling freezes it, enabling releases it.
        $switches = array_values(array_filter($member['history'], fn (array $entry): bool => $entry['action'] === 'staff.configure'
            && ($entry['before']['enabled'] ?? false) !== ($entry['after']['enabled'] ?? false)));
        $disables = array_values(array_filter($switches, fn (array $entry): bool => ($entry['after']['enabled'] ?? false) === false));
        $freeze = $row['enabled'] ? null : ($disables[0] ?? null);

        return ['id' => (string) $row['user_id'], 'kind' => 'staff', 'name' => $row['name'], 'subtitle' => $row['email'],
            'health' => $row['enabled'] ? 'active' : 'frozen',
            'stats' => [['key' => 'role', 'value' => ['kind' => 'text', 'value' => $roles === [] ? '—'
                : implode(', ', array_map(fn (string $role): string => self::ROLE_NAMES[$role], $roles))]]],
            'list' => null,
            'history' => array_map(fn (array $entry): array => ['id' => $entry['id'], 'at' => $entry['at'], 'actor' => $actor($entry['actor']),
                'action' => StaffActivityResource::action($entry['action']), 'reason' => $entry['reason']], $member['history']),
            'kyc' => null, 'licence' => null,
            'freeze' => $freeze === null ? null : ['actor' => $actor($freeze['actor']), 'at' => $freeze['at'], 'reason' => $freeze['reason']],
            'release_blocked' => null,
            'restrictions' => array_map(function (array $entry) use ($actor): array {
                $frozen = ($entry['after']['enabled'] ?? false) === false;

                return ['id' => $entry['id'], 'at' => $entry['at'], 'actor' => $actor($entry['actor']), 'kind' => $frozen ? 'freeze' : 'release',
                    'action' => ['code' => $entry['action'], 'label' => $frozen ? 'Disabled staff access' : 'Enabled staff access', 'tone' => $frozen ? 'red' : 'green'],
                    'reason' => $entry['reason']];
            }, $switches),
            'links' => ['close' => $close], 'actions' => $manage ? [($row['enabled'] ? 'freeze' : 'release') => [
                'url' => route($row['enabled'] ? 'staff.staff.suspend' : 'staff.staff.reactivate', ['operator' => $row['user_id']], false), 'method' => 'post']] : (object) []];
    }
}
