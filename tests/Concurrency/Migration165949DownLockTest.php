<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * P3-3 (#96 5876034693): 165949 down() reads the empty-evidence check only after its
 * ACCESS EXCLUSIVE lock, so an in-flight Primary posting is either visible to the check
 * or waits for the rollback. The constraint can never be dropped while a posting the
 * check should have seen commits beside it.
 */
it('refuses 165949 rollback once a competing Primary posting it waited for commits', function (): void {
    $this->freezeSecond();
    $migration = database_path('migrations/2026_09_28_165949_reject_unbound_primary_commitment_sources.php');
    $terminalCash = database_path('migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php');
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $constraintPresent = fn (): bool => DB::selectOne("SELECT count(*) AS total FROM pg_constraint
        WHERE conname = 'primary_commitment_source_unavailable' AND conrelid = 'ledger_entries'::regclass")->total === 1;
    $primaryPostings = fn (): int => DB::table('ledger_entries')->where('kind', '<>', 'deposit_credit')->count();

    // 175455 is the only later migration; with no retained Primary evidence it rolls back first.
    (require $terminalCash)->down();
    expect($constraintPresent())->toBeTrue()->and($primaryPostings())->toBe(0);

    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create the rollback barrier.');
    }
    stream_set_timeout($channels[0], 8);
    stream_set_timeout($channels[1], 8);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork the rollback connection.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '5s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            (require $migration)->down();
            exit(4);
        } catch (QueryException $exception) {
            exit($exception->getCode() === '23514'
                && str_contains($exception->getMessage(), 'Recorded Primary postings require a forward migration') ? 0 : 5);
        } catch (Throwable) {
            exit(6);
        }
    }
    fclose($channels[1]);
    $childStatus = null;
    $rollbackReadLedgerBeforeLock = null;
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        expect($backend)->toBeGreaterThan(0);
        DB::beginTransaction();
        $reserved = app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($reserved['code'])->toBe('RESERVATION_HELD')
            ->and($primaryPostings())->toBe(1)
            ->and(DB::selectOne("SELECT count(*) AS total FROM pg_locks WHERE pid = pg_backend_pid()
                AND relation = 'ledger_entries'::regclass AND mode = 'RowExclusiveLock' AND granted")->total)->toBe(1);
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 3_000_000_000;
        $blocked = false;
        while (hrtime(true) < $deadline) {
            $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
            if ($blocked) {
                break;
            }
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                $childStatus = $status;
                break;
            }
            usleep(10000);
        }
        expect($blocked)->toBeTrue('down() must wait on the uncommitted Primary posting.');
        $rollbackLedgerLocks = DB::selectOne("SELECT count(*) FILTER (WHERE NOT granted) AS waiting,
                count(*) FILTER (WHERE NOT granted AND mode = 'AccessExclusiveLock') AS exclusive,
                count(*) FILTER (WHERE granted) AS held
            FROM pg_locks WHERE pid = ? AND relation = 'ledger_entries'::regclass", [$backend]);
        expect([$rollbackLedgerLocks->waiting, $rollbackLedgerLocks->exclusive])->toBe([1, 1]);
        // A granted lock here means down() already read ledger_entries before it queued for exclusivity.
        $rollbackReadLedgerBeforeLock = $rollbackLedgerLocks->held > 0;
        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($childStatus === null) {
            pcntl_waitpid($pid, $childStatus);
        }
        fclose($channels[0]);
        $survived = [$constraintPresent(), $primaryPostings()];
        if (! $survived[0]) {
            (require $migration)->up();
        }
        (require $terminalCash)->up();
    }

    expect($survived[0] || $survived[1] === 0)->toBeTrue('The constraint was dropped while a Primary posting committed.')
        ->and(pcntl_wifexited($childStatus) ? pcntl_wexitstatus($childStatus) : -1)->toBe(0)
        ->and($survived)->toBe([true, 1])
        ->and($rollbackReadLedgerBeforeLock)->toBeFalse()
        ->and(DB::table('ledger_entries')->where('kind', 'primary_hold')->where('source_type', 'primary_reservation')->count())->toBe(1);
});
