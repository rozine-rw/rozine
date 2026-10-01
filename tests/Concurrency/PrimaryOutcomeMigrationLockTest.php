<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('installs the outcome guard without blocking the journal reader or its later receipt insert', function (bool $reservationAlreadyWritten): void {
    $path = database_path('migrations/2026_09_28_163057_require_completed_primary_command_outcomes.php');
    (require $path)->down();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create the migration barrier.');
    }
    stream_set_timeout($channels[0], 8);
    stream_set_timeout($channels[1], 8);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork the migration connection.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '4s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            (require $path)->up();
            fwrite($channels[1], "done\n");
            exit(0);
        } catch (Throwable) {
            exit(2);
        }
    }
    fclose($channels[1]);
    $status = null;
    try {
        $backend = trim((string) fgets($channels[0]));
        expect(ctype_digit($backend))->toBeTrue();
        DB::beginTransaction();
        DB::select('SELECT id FROM command_operations LIMIT 1');
        if ($reservationAlreadyWritten) {
            DB::statement('LOCK TABLE primary_reservations IN ROW EXCLUSIVE MODE');
        }
        fwrite($channels[0], "go\n");
        if ($reservationAlreadyWritten) {
            $deadline = hrtime(true) + 2_000_000_000;
            $blocked = false;
            while (hrtime(true) < $deadline) {
                $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
                if ($blocked) {
                    break;
                }
                usleep(10000);
            }
            expect($blocked)->toBeTrue('The migration must wait for the in-flight reservation.');
        } else {
            $read = [$channels[0]];
            $write = null;
            $except = null;
            expect(stream_select($read, $write, $except, 2))->toBe(1, 'A journal lookup must not block guard installation.');
            expect(fgets($channels[0]))->toBe("done\n");
        }
        DB::statement('LOCK TABLE command_operations IN ROW EXCLUSIVE MODE NOWAIT');
        DB::statement('LOCK TABLE primary_reservations IN ROW EXCLUSIVE MODE NOWAIT');
        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        pcntl_waitpid($pid, $status);
        fclose($channels[0]);
    }
    expect(pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1)->toBe(0)
        ->and(DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname IN ('primary_reservation_outcome_bound', 'primary_version_outcome_bound')")->total)->toBe(2);
})->with([false, true]);
