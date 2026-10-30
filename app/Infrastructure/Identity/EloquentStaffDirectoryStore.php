<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\StaffDirectoryStore;
use App\Domain\Identity\StaffPermission;
use App\Models\StaffAccount;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * @phpstan-import-type Directory from StaffDirectoryStore
 * @phpstan-import-type StaffRow from StaffDirectoryStore
 * @phpstan-import-type Member from StaffDirectoryStore
 * @phpstan-import-type LogEntry from StaffDirectoryStore
 */
final class EloquentStaffDirectoryStore implements StaffDirectoryStore
{
    /** @return Directory */
    public function directory(string $chip, string $search): array
    {
        $staff = fn (): Builder => DB::table('staff_accounts')->join('users', 'users.id', '=', 'staff_accounts.user_id')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $match) => $match->whereLike('users.name', $term)->orWhereLike('users.email', $term));
            });
        $totals = $staff()->selectRaw("count(*) AS everyone, count(*) FILTER (WHERE staff_accounts.enabled) AS active,
            count(*) FILTER (WHERE EXISTS (SELECT 1 FROM model_has_roles mr JOIN roles r ON r.id = mr.role_id WHERE mr.model_id = staff_accounts.user_id AND r.name = 'approver' AND r.guard_name = 'staff')) AS approvers")->first();
        $rows = $staff()->when($chip !== 'all', fn (Builder $query) => $query->where('staff_accounts.enabled', $chip === 'active'))
            ->orderBy('users.name')->orderBy('users.id')->get(['users.id', 'users.name', 'users.email', 'staff_accounts.enabled']);
        $assignments = StaffAccount::query()->whereKey($rows->pluck('id'))->with('roles')->get()->keyBy('user_id');
        foreach ($rows as $row) {
            $row->roles = $assignments[$row->id]->roles->pluck('name')->sort()->values()->all();
        }
        $everyone = (int) ($totals->everyone ?? 0);
        $active = (int) ($totals->active ?? 0);

        return ['rows' => array_values(array_map(self::row(...), $rows->all())), 'matching' => $rows->count(),
            'counts' => ['all' => $everyone, 'active' => $active, 'frozen' => $everyone - $active, 'approvers' => (int) ($totals->approvers ?? 0)]];
    }

    public function permissions(): array
    {
        $roles = Role::query()->where('guard_name', 'staff')->with('permissions')->get()->keyBy('name');
        $matrix = ['admin.open' => StaffPermission::roles()];
        foreach (StaffPermission::roles() as $name) {
            foreach ($roles[$name]->permissions->sortBy('id') as $permission) {
                $matrix[$permission->name][] = $name;
            }
        }

        return [...array_map(fn (string $key, array $grants): array => ['key' => $key, 'roles' => array_values(array_unique($grants))],
            array_keys($matrix), array_values($matrix)), ...StaffPermission::withheld()];
    }

    /** @return Member|null */
    public function member(int $userId): ?array
    {
        $row = DB::table('staff_accounts')->join('users', 'users.id', '=', 'staff_accounts.user_id')->where('users.id', $userId)
            ->first(['users.id', 'users.name', 'users.email', 'staff_accounts.enabled']);
        if ($row === null) {
            return null;
        }
        $row->roles = StaffAccount::query()->findOrFail($userId)->roleNames();
        $history = DB::table('identity_audit_events AS event')->leftJoin('users AS actor', 'actor.id', '=', 'event.actor_user_id')
            ->where('event.target_type', 'user')->where('event.target_id', (string) $userId)
            ->orderByDesc('event.created_at')->orderByDesc('event.id')
            ->get(['event.id', 'event.created_at', 'actor.name', 'event.action', 'event.reason', 'event.before', 'event.after']);

        return ['row' => self::row($row), 'history' => array_values($history->map(fn (object $event): array => [
            'id' => (string) $event->id, 'at' => Carbon::parse((string) $event->created_at)->toIso8601String(),
            'actor' => $event->name === null ? null : (string) $event->name, 'action' => (string) $event->action, 'reason' => (string) $event->reason,
            'before' => self::json($event->before), 'after' => self::json($event->after)])->all())];
    }

    /** @return list<LogEntry> */
    public function log(int $limit): array
    {
        $events = DB::table('identity_audit_events AS event')->join('users AS target', DB::raw('target.id::text'), '=', 'event.target_id')
            ->leftJoin('users AS actor', 'actor.id', '=', 'event.actor_user_id')->where('event.target_type', 'user')
            ->where('event.action', 'staff.configure')
            ->orderByDesc('event.created_at')->orderByDesc('event.id')->limit($limit)
            ->get(['event.id', 'event.created_at', 'actor.name AS actor', 'target.name AS target', 'event.reason', 'event.before', 'event.after']);

        return array_values($events->map(function (object $event): array {
            $before = self::json($event->before);
            $after = self::json($event->after);
            $enabled = (bool) ($after['enabled'] ?? false);
            $roles = is_array($after['roles'] ?? null) ? array_values(array_filter($after['roles'], is_string(...))) : [];

            return ['id' => (string) $event->id, 'at' => Carbon::parse((string) $event->created_at)->toIso8601String(),
                'actor' => $event->actor === null ? null : (string) $event->actor, 'target' => (string) $event->target, 'enabled' => $enabled,
                'granted' => $enabled && ($before['enabled'] ?? false) === false && ($before['roles'] ?? []) === [], 'roles' => $roles,
                'reason' => (string) $event->reason];
        })->all());
    }

    public function pending(): array
    {
        return array_values(DB::table('identity_audit_events AS proposal')->join('users AS recipient', DB::raw('recipient.id::text'), '=', 'proposal.target_id')
            ->join('users AS requester', 'requester.id', '=', 'proposal.actor_user_id')->where('proposal.action', 'staff.superadmin.request')
            ->where('proposal.target_type', 'user')->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('identity_audit_events AS approval')
            ->where('approval.action', 'staff.configure')->whereRaw('approval.after->>\'proposal_id\' = proposal.id'))
            ->orderByDesc('proposal.created_at')->orderByDesc('proposal.id')->limit(50)
            ->get(['proposal.id', 'recipient.id AS recipient_id', 'requester.id AS requester_id', 'recipient.name AS recipient', 'recipient.email',
                'requester.name AS requester', 'proposal.reason', 'proposal.after'])
            ->map(fn (object $row): array => ['id' => (string) $row->id, 'recipient_id' => (int) $row->recipient_id, 'requester_id' => (int) $row->requester_id,
                'recipient' => (string) $row->recipient, 'email' => (string) $row->email, 'requester' => (string) $row->requester,
                'operation' => (string) (self::json($row->after)['operation'] ?? ''), 'reason' => (string) $row->reason])->all());
    }

    /** @return StaffRow */
    private static function row(object $record): array
    {
        $row = (array) $record;

        return ['user_id' => (int) $row['id'], 'name' => (string) $row['name'], 'email' => (string) $row['email'],
            'roles' => $row['roles'], 'enabled' => (bool) $row['enabled']];
    }

    /** @return array<array-key, mixed> */
    private static function json(mixed $value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
