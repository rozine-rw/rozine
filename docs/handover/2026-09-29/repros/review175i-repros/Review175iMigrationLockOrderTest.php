<?php

declare(strict_types=1);

/*
 * Review repro for PR #175 migration 2026_09_28_163057 up(): it takes ACCESS EXCLUSIVE on
 * primary_reservations and primary_reservation_versions before command_operations. The command
 * journal takes the opposite order: EloquentOperationJournal::lookup() reads command_operations
 * (ACCESS SHARE) and only then does the reserve closure lock/insert primary_reservations. A reserve
 * in flight at deploy time therefore deadlocks with the migration; PostgreSQL aborts one side.
 *
 * Copy into tests/Concurrency/ (DatabaseTruncation, real commits). FAILS on ad598a09 with one side
 * exiting 2 (SQLSTATE 40P01). Locking command_operations first, or not at all (it is immutable),
 * removes the cycle.
 */

use Illuminate\Support\Facades\DB;

function r175iLockWait(string $sql, float $seconds = 10.0): bool
{
    $deadline = microtime(true) + $seconds;
    while (microtime(true) < $deadline) {
        if ((int) DB::selectOne($sql)->total > 0) {
            return true;
        }
        usleep(20000);
    }

    return false;
}

it('DEFECT: installing the outcome guard does not deadlock with a reserve already reading the journal', function (): void {
    $path = database_path('migrations/2026_09_28_163057_require_completed_primary_command_outcomes.php');
    (require $path)->down();
    $signal = sys_get_temp_dir().'/r175i-lock-'.getmypid();
    @unlink($signal);
    DB::disconnect();

    $journal = pcntl_fork();
    if ($journal === 0) {
        DB::purge();
        try {
            DB::beginTransaction();
            DB::select('SELECT id FROM command_operations LIMIT 1'); // EloquentOperationJournal::lookup()
            $deadline = microtime(true) + 10;
            while (! file_exists($signal) && microtime(true) < $deadline) {
                usleep(20000);
            }
            DB::statement('LOCK TABLE primary_reservations IN ROW EXCLUSIVE MODE'); // reserve() insert
            DB::commit();
            exit(0);
        } catch (Throwable $exception) {
            exit(str_contains($exception->getMessage(), '40P01') ? 2 : 1);
        }
    }

    $migration = pcntl_fork();
    if ($migration === 0) {
        DB::purge();
        try {
            r175iLockWait("SELECT count(*) AS total FROM pg_locks l JOIN pg_class c ON c.oid = l.relation
                WHERE c.relname = 'command_operations' AND l.mode = 'AccessShareLock' AND l.granted AND l.pid <> pg_backend_pid()");
            (require $path)->up();
            exit(0);
        } catch (Throwable $exception) {
            exit(str_contains($exception->getMessage(), '40P01') ? 2 : 1);
        }
    }

    DB::purge();
    $waiting = r175iLockWait("SELECT count(*) AS total FROM pg_locks l JOIN pg_class c ON c.oid = l.relation
        WHERE c.relname = 'command_operations' AND l.mode = 'AccessExclusiveLock' AND NOT l.granted");
    touch($signal);
    $results = [];
    foreach ([$journal, $migration] as $pid) {
        pcntl_waitpid($pid, $status);
        $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
    }
    @unlink($signal);
    DB::purge();
    if ((int) DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname = 'primary_reservation_outcome_bound'")->total === 0) {
        (require $path)->up();
    }

    // On ad598a09 $waiting is true (the migration queues for command_operations behind the reader).
    expect($results)->toBe([0, 0]);
});
