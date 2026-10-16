<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\ActivityTrailStore;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Trail from ActivityTrailStore
 * @phpstan-import-type TrailEntry from ActivityTrailStore
 */
final class EloquentActivityTrailStore implements ActivityTrailStore
{
    /** @return Trail */
    public function trail(string $search, ?string $from, ?string $to, int $limit): array
    {
        $identity = DB::table('identity_audit_events')->where('action', '<>', 'bookmark.save')
            ->selectRaw("id, created_at, actor_key, actor_user_id, action AS code, target_type, target_id, reason,
                before, after, NULL::jsonb AS result, 'identity' AS journal");
        $operations = DB::table('command_operations')
            ->selectRaw("id, created_at, actor_key, actor_user_id, command AS code, target_type, target_id, NULL::text AS reason,
                NULL::jsonb AS before, NULL::jsonb AS after, result, 'operations' AS journal");
        $named = DB::query()->fromSub($identity->unionAll($operations), 'trail')->leftJoin('users AS actor', 'actor.id', '=', 'trail.actor_user_id')
            ->selectRaw("trail.*, actor.name AS actor_name, actor.email AS actor_email,
                (trail.actor_key LIKE 'staff:%' OR EXISTS (SELECT 1 FROM staff_accounts s WHERE s.user_id = trail.actor_user_id)) AS actor_staff,
                CASE trail.target_type
                    WHEN 'user' THEN (SELECT u.name FROM users u WHERE u.id::text = trail.target_id)
                    WHEN 'membership' THEN (SELECT u.name FROM role_memberships m JOIN users u ON u.party_id = m.party_id
                        WHERE m.id = trail.target_id ORDER BY u.id LIMIT 1)
                END AS target_name");
        $trail = DB::query()->fromSub($named, 'entries')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $match) => $match->whereLike('actor_name', $term)->orWhereLike('actor_email', $term)
                    ->orWhereLike('code', $term)->orWhereLike('target_id', $term)->orWhereLike('target_name', $term));
            })
            ->when($from !== null, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->where('created_at', '<', $to));
        $total = (clone $trail)->count();

        return ['entries' => array_values(array_map(self::entry(...), $trail->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get()->all())),
            'total' => $total];
    }

    /** @return TrailEntry */
    private static function entry(object $record): array
    {
        $row = (array) $record;
        $result = self::json($row['result']);

        return ['id' => (string) $row['id'], 'at' => Carbon::parse((string) $row['created_at'])->toIso8601String(),
            'journal' => $row['journal'] === 'identity' ? 'identity' : 'operations',
            'actor' => $row['actor_name'] === null ? null : (string) $row['actor_name'],
            'actor_kind' => $row['actor_key'] === 'console' ? 'console' : ((bool) $row['actor_staff'] ? 'staff' : 'participant'),
            'code' => (string) $row['code'], 'target_type' => (string) $row['target_type'], 'target_id' => (string) $row['target_id'],
            'target_name' => $row['target_name'] === null ? null : (string) $row['target_name'],
            'reason' => $row['reason'] === null ? null : (string) $row['reason'],
            'before' => $row['before'] === null ? null : self::json($row['before']), 'after' => $row['after'] === null ? null : self::json($row['after']),
            'outcome' => $result === [] ? null : ['status' => (string) ($result['status'] ?? ''), 'code' => (string) ($result['code'] ?? '')]];
    }

    /** @return array<string, mixed> */
    private static function json(mixed $value): array
    {
        $decoded = $value === null ? null : json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
