<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\ChangeFeed;
use App\Domain\Operations\ChangeCursor;
use App\Domain\Operations\ChangeScope;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * The change feed over PostgreSQL. A row's `created_xid` is its writer's transaction, stamped by a
 * trigger. A read delivers the changes of transactions between the cursor's horizon and its own
 * snapshot's `xmin`: every transaction below `xmin` has finished, so no change can still commit
 * behind the cursor. An id cursor alone could skip one, because a transaction can take its id
 * early (say, on its journal insert) and its feed row late, after a younger transaction's row.
 *
 * @phpstan-import-type Audience from ChangeFeed
 * @phpstan-import-type Change from ChangeFeed
 */
final class PostgresChangeFeed implements ChangeFeed
{
    /** Which topics each audience can carry. */
    private const array TOPICS = [ChangeScope::PARTY => ['wallet'], ChangeScope::BUSINESS => ['campaign'], ChangeScope::STAFF_QUEUE => ['staff_queue']];

    public function record(ChangeScope $scope, string $topic, string $subject, ?int $revision = null): void
    {
        if (app('db.transactions')->callbackApplicableTransactions()->isEmpty()) {
            throw new LogicException('CHANGE_FEED_TRANSACTION_REQUIRED');
        }
        if (! in_array($topic, self::TOPICS[$scope->kind], true) || preg_match('/^[0-9A-Za-z_]{1,64}$/', $subject) !== 1 || ($revision !== null && $revision < 1)) {
            throw new InvalidArgumentException('CHANGE_INVALID');
        }
        if ($revision === null) {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['change-feed|'.$scope->key().'|'.$topic.'|'.$subject]);
            $revision = (int) DB::scalar('SELECT coalesce(max(revision), 0) + 1 FROM change_feed WHERE topic = ? AND subject = ?', [$topic, $subject]);
        }
        DB::insert('INSERT INTO change_feed (party_id, business_id, staff_queue, topic, subject, revision) VALUES (?, ?, ?, ?, ?, ?)', [
            $scope->kind === ChangeScope::PARTY ? $scope->id : null, $scope->kind === ChangeScope::BUSINESS ? $scope->id : null,
            $scope->kind === ChangeScope::STAFF_QUEUE ? $scope->id : null, $topic, $subject, $revision]);
    }

    public function horizon(): int
    {
        return (int) DB::scalar('SELECT pg_snapshot_xmin(pg_current_snapshot())::text');
    }

    public function read(array $audiences, ChangeCursor $after, int $limit): array
    {
        /** @var object{xmin: string, own: string|null} $snapshot */
        $snapshot = DB::selectOne('SELECT pg_snapshot_xmin(pg_current_snapshot())::text AS xmin, pg_current_xact_id_if_assigned()::text AS own');
        $xmin = max($after->xmin, (int) $snapshot->xmin);
        [$scope, $scopeBindings] = $this->scope($audiences);
        if ($scope === null) {
            return ['changes' => [], 'xmin' => $xmin, 'after_id' => $after->afterId, 'overflow' => false];
        }
        // A transaction sees its own uncommitted changes; they are delivered once, by id.
        $own = $snapshot->own === null ? ['', []] : [' OR (created_xid = ?::xid8 AND id > ?)', [$snapshot->own, $after->afterId]];
        $rows = DB::select("SELECT id, topic, subject, revision FROM change_feed WHERE ({$scope})
            AND ((created_xid >= ?::xid8 AND created_xid < ?::xid8){$own[0]}) ORDER BY id LIMIT ?",
            [...$scopeBindings, (string) $after->xmin, (string) $xmin, ...$own[1], $limit + 1]);
        if (count($rows) > $limit) {
            return ['changes' => [], 'xmin' => $xmin, 'after_id' => $after->afterId, 'overflow' => true];
        }

        $latest = [];
        $afterId = $after->afterId;
        foreach ($rows as $row) {
            $afterId = max($afterId, (int) $row->id);
            $key = $row->topic.'|'.$row->subject;
            if (! isset($latest[$key]) || $latest[$key]['revision'] < (int) $row->revision) {
                $latest[$key] = ['topic' => (string) $row->topic, 'subject' => (string) $row->subject, 'revision' => (int) $row->revision];
            }
        }

        return ['changes' => $this->withoutDelivered(array_values($latest), $scope, $scopeBindings, $after, $snapshot->own),
            'xmin' => $xmin, 'after_id' => $afterId, 'overflow' => false];
    }

    public function prune(int $retentionHours): int
    {
        if ($retentionHours < 24) {
            throw new InvalidArgumentException('CHANGE_RETENTION_TOO_SHORT');
        }

        return DB::transaction(function () use ($retentionHours): int {
            DB::statement("SET LOCAL rozine.change_feed_prune = 'on'");

            return DB::delete('DELETE FROM change_feed stale WHERE stale.created_at < now() - make_interval(hours => ?)
                AND EXISTS (SELECT 1 FROM change_feed newer WHERE newer.topic = stale.topic AND newer.subject = stale.subject AND newer.revision > stale.revision)',
                [$retentionHours]);
        });
    }

    /**
     * Leaves out a change whose revision is not newer than one the reader already had: the page
     * it rendered, or an earlier read, already reflected a state at least that recent.
     *
     * @param  list<Change>  $changes
     * @param  list<string|int>  $scopeBindings
     * @return list<Change>
     */
    private function withoutDelivered(array $changes, string $scope, array $scopeBindings, ChangeCursor $after, ?string $own): array
    {
        if ($changes === []) {
            return [];
        }
        $subjects = implode(' OR ', array_fill(0, count($changes), '(topic = ? AND subject = ?)'));
        $ownDelivered = $own === null ? ['', []] : [' OR (created_xid = ?::xid8 AND id <= ?)', [$own, $after->afterId]];
        $delivered = collect(DB::select("SELECT topic, subject, max(revision) AS revision FROM change_feed WHERE ({$scope}) AND ({$subjects})
            AND (created_xid < ?::xid8{$ownDelivered[0]}) GROUP BY topic, subject",
            [...$scopeBindings, ...array_merge(...array_map(fn (array $change): array => [$change['topic'], $change['subject']], $changes)),
                (string) $after->xmin, ...$ownDelivered[1]]))
            ->mapWithKeys(fn (object $row): array => [$row->topic.'|'.$row->subject => (int) $row->revision]);

        return array_values(array_filter($changes,
            fn (array $change): bool => $change['revision'] > ($delivered[$change['topic'].'|'.$change['subject']] ?? 0)));
    }

    /**
     * The SQL filter for exactly the given audiences, or null when there are none.
     *
     * @param  list<Audience>  $audiences
     * @return array{0: string|null, 1: list<string>}
     */
    private function scope(array $audiences): array
    {
        $clauses = [];
        $bindings = [];
        foreach ($audiences as $audience) {
            $column = match ($audience['scope']->kind) {
                ChangeScope::PARTY => 'party_id', ChangeScope::BUSINESS => 'business_id', default => 'staff_queue',
            };
            $clauses[] = "({$column} = ? AND topic = ?)";
            array_push($bindings, $audience['scope']->id, $audience['topic']);
        }

        return [$clauses === [] ? null : implode(' OR ', $clauses), $bindings];
    }
}
