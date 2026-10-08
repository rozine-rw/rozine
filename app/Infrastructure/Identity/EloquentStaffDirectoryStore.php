<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\StaffDirectoryStore;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Directory from StaffDirectoryStore
 * @phpstan-import-type StaffRow from StaffDirectoryStore
 * @phpstan-import-type Member from StaffDirectoryStore
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
            count(*) FILTER (WHERE staff_accounts.roles @> '[\"approver\"]'::jsonb) AS approvers")->first();
        $rows = $staff()->when($chip !== 'all', fn (Builder $query) => $query->where('staff_accounts.enabled', $chip === 'active'))
            ->orderBy('users.name')->orderBy('users.id')->get(['users.id', 'users.name', 'users.email', 'staff_accounts.roles', 'staff_accounts.enabled']);
        $everyone = (int) ($totals->everyone ?? 0);
        $active = (int) ($totals->active ?? 0);

        return ['rows' => array_values(array_map(self::row(...), $rows->all())), 'matching' => $rows->count(),
            'counts' => ['all' => $everyone, 'active' => $active, 'frozen' => $everyone - $active, 'approvers' => (int) ($totals->approvers ?? 0)]];
    }

    /** @return Member|null */
    public function member(int $userId): ?array
    {
        $row = DB::table('staff_accounts')->join('users', 'users.id', '=', 'staff_accounts.user_id')->where('users.id', $userId)
            ->first(['users.id', 'users.name', 'users.email', 'staff_accounts.roles', 'staff_accounts.enabled']);
        if ($row === null) {
            return null;
        }
        $history = DB::table('identity_audit_events AS event')->leftJoin('users AS actor', 'actor.id', '=', 'event.actor_user_id')
            ->where('event.target_type', 'user')->where('event.target_id', (string) $userId)
            ->orderByDesc('event.created_at')->orderByDesc('event.id')
            ->get(['event.id', 'event.created_at', 'actor.name', 'event.action', 'event.reason', 'event.before', 'event.after']);

        return ['row' => self::row($row), 'history' => array_values($history->map(fn (object $event): array => [
            'id' => (string) $event->id, 'at' => Carbon::parse((string) $event->created_at)->toIso8601String(),
            'actor' => $event->name === null ? null : (string) $event->name, 'action' => (string) $event->action, 'reason' => (string) $event->reason,
            'before' => self::json($event->before), 'after' => self::json($event->after)])->all())];
    }

    /** @return StaffRow */
    private static function row(object $record): array
    {
        $row = (array) $record;

        return ['user_id' => (int) $row['id'], 'name' => (string) $row['name'], 'email' => (string) $row['email'],
            'roles' => array_values(array_filter(self::json($row['roles']), is_string(...))), 'enabled' => (bool) $row['enabled']];
    }

    /** @return array<array-key, mixed> */
    private static function json(mixed $value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
