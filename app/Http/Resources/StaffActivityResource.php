<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The designed Activity & Audit trail (`admin/events`) from the identity audit log and the command
 * journal, newest first. Each row names who acted, the action, its target, the channel it arrived
 * through (the server console, the Admin console or a participant app; no IP address is recorded),
 * the reason when one was given, and what changed: an identity entry's before/after values for its
 * access fields only (evidence references and identity digests stay out), a command's recorded
 * outcome. Nothing here can be edited; no export exists yet, so none is offered.
 *
 * @phpstan-type TrailEntry array{
 *     id: string, at: string, journal: string, actor: string|null, actor_kind: 'console'|'staff'|'participant',
 *     code: string, target_type: string, target_id: string, target_name: string|null, reason: string|null,
 *     before: array<string, mixed>|null, after: array<string, mixed>|null, outcome: array{status: string, code: string}|null
 * }
 * @phpstan-type Page array{
 *     entries: list<TrailEntry>, total: int, search: string, preset: 'today'|'7d'|'30d'|null, from: string|null, to: string|null,
 *     limit: int, roles: list<string>, permissions: list<string>
 * }
 */
class StaffActivityResource extends JsonResource
{
    /** The most entries one page shows; "see all" opens this many. */
    public const MAX = 500;

    /** How each recorded action reads, and its tone when it completed. */
    private const ACTIONS = [
        'staff.configure' => ['Changed staff access', 'purple'], 'operator.configure' => ['Changed identity operator access', 'purple'],
        'staff.person.record' => ['Recorded the person behind a staff account', 'blue'], 'person.resolve' => ['Verified a person', 'green'],
        'investor.verify' => ['Verified an Investor', 'green'], 'organization.resolve' => ['Verified an organization', 'green'],
        'membership.change' => ['Changed a membership', 'amber'], 'role.select' => ['Switched active role', 'grey'],
        'application.create' => ['Started an application', 'blue'], 'application.save' => ['Saved an application', 'grey'],
        'application.evaluate' => ['Priced an application', 'blue'], 'application.submit' => ['Submitted an application', 'blue'],
        'application.release' => ['Released an application', 'green'], 'application.publish' => ['Published a note', 'green'],
        'campaign.cancel' => ['Cancelled a campaign', 'amber'], 'primary.reserve' => ['Reserved notes', 'blue'],
        'primary.confirm' => ['Confirmed a purchase', 'green'], 'primary.release' => ['Released a reservation', 'grey'],
        'primary.refund' => ['Refunded a reservation', 'amber'], 'wallet.deposit' => ['Started a deposit', 'blue'],
        'business.wallet.deposit' => ['Started a Business deposit', 'blue'], 'repayment.pay' => ['Paid a repayment', 'green'],
        'disbursement.authorize' => ['Authorized a disbursement', 'blue'], 'disbursement.approve' => ['Approved a disbursement', 'green'],
        'disbursement.reject' => ['Rejected a disbursement', 'red'], 'disbursement.hold' => ['Held a disbursement', 'amber'],
        'disbursement.release_hold' => ['Released a disbursement hold', 'blue'], 'disbursement.requery' => ['Re-queried a disbursement', 'grey'],
        'investor.verification.approve' => ['Approved an identity submission', 'green'],
        'investor.verification.reject' => ['Rejected an identity submission', 'red'],
        'business.authority.configure' => ['Configured a Business mandate', 'purple'], 'consent.release.record' => ['Recorded a consent release', 'blue'],
    ];

    /** The access fields an identity entry's before/after may show. */
    private const FIELDS = ['enabled', 'roles', 'status'];

    /** How each actor kind reached the platform. */
    private const SOURCES = ['console' => 'Server console', 'staff' => 'Admin console', 'participant' => 'Participant app'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $filters = ['q' => $page['search'], 'preset' => $page['preset'], 'from' => $page['from'], 'to' => $page['to']];
        $more = $page['total'] > count($page['entries']) && $page['limit'] < self::MAX
            ? ['url' => route($prefix.'staff.events.index', [...array_filter($filters, fn (?string $value): bool => $value !== null && $value !== ''),
                'limit' => self::MAX], false), 'method' => 'get'] : null;

        return ['contract_version' => 'staff-activity-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => (new StaffViewerResource($page['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($page['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'],
            'events' => array_map(fn (array $entry): array => ['id' => $entry['id'], 'at' => $entry['at'],
                'actor' => $entry['actor'] ?? self::SOURCES['console'], 'action' => self::action($entry['code'], $entry['outcome']['status'] ?? null),
                'target' => $entry['target_name'] ?? ucfirst(str_replace(['.', '_'], ' ', $entry['target_type'])).' '.$entry['target_id'],
                'source' => self::SOURCES[$entry['actor_kind']], 'reason' => $entry['reason'], 'changes' => self::changes($entry)], $page['entries']),
            'total' => $page['total'], 'filters' => $filters, 'more' => $more, 'export' => ['state' => 'idle'], 'actions' => (object) []];
    }

    /**
     * How an action reads: its label and tone, or a label made from its code when the console has
     * none yet. A refused command reads red.
     *
     * @return array{code: string, label: string, tone: string}
     */
    public static function action(string $code, ?string $status = null): array
    {
        [$label, $tone] = self::ACTIONS[$code] ?? [ucfirst(str_replace(['.', '_'], ' ', $code)), 'grey'];

        return $status === 'rejected' ? ['code' => $code, 'label' => $label.' (refused)', 'tone' => 'red'] : ['code' => $code, 'label' => $label, 'tone' => $tone];
    }

    /**
     * @param  TrailEntry  $entry
     * @return list<array{field: string, before: string|null, after: string|null}>
     */
    private static function changes(array $entry): array
    {
        if ($entry['outcome'] !== null) {
            return [['field' => 'outcome', 'before' => null, 'after' => $entry['outcome']['code']]];
        }
        $before = $entry['before'] ?? [];
        $after = $entry['after'] ?? [];
        $changes = [];
        foreach (self::FIELDS as $field) {
            [$was, $now] = [self::text($before[$field] ?? null), self::text($after[$field] ?? null)];
            if ($was !== $now) {
                $changes[] = ['field' => $field, 'before' => $was, 'after' => $now];
            }
        }

        return $changes;
    }

    private static function text(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => implode(', ', array_map(fn (mixed $item): string => is_scalar($item) ? (string) $item : '', $value)),
            default => is_scalar($value) ? (string) $value : null,
        };
    }
}
