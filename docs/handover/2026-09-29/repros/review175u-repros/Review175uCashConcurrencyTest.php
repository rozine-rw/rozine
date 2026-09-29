<?php

declare(strict_types=1);

/* Review 175u cash-port probes (1cffe3b4). Copy into tests/Concurrency. Real transactions, so isolation can be set at BEGIN. */

use App\Application\Wallet\CommittedCash;
use App\Application\Wallet\Contracts\PrimaryCommittedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\InvestorWallet;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->freezeSecond();
});

/** @return array{LockedWallet, PostingSource, LockedWallet} */
function review175uCash(): array
{
    return DB::transaction(function (): array {
        PrimaryCommitment::factory()->create();
        $root = PrimaryReservationRecord::query()->sole();
        $wallet = InvestorWallet::query()->where('party_id', $root->party_id)->sole();
        $unrelated = InvestorWallet::factory()->create();

        return [new LockedWallet($wallet->id, $wallet->party_id), new PostingSource('primary_reservation', $root->id, $root->origin_operation_id),
            new LockedWallet($unrelated->id, $unrelated->party_id)];
    });
}

function review175uOutcome(Closure $call): string
{
    try {
        $result = $call();

        return $result instanceof CommittedCash ? 'evidence' : 'other';
    } catch (WalletViolation $exception) {
        return $exception->reason;
    } catch (QueryException $exception) {
        return 'sql:'.$exception->errorInfo[0];
    }
}

/** @return array{int, resource} a child that holds $walletId FOR UPDATE until released */
function review175uHolder(string $walletId): array
{
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 15);
    stream_set_timeout($channels[1], 15);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        DB::beginTransaction();
        InvestorWallet::query()->whereKey($walletId)->lockForUpdate()->first();
        fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
        fgets($channels[1]);
        DB::commit();
        exit(0);
    }
    fclose($channels[1]);
    DB::purge();

    return [$pid, $channels[0]];
}

/** Runs $call while another backend holds $walletId; returns [outcome, blocked-on-holder]. */
function review175uAgainstHeld(string $walletId, Closure $call): array
{
    [$pid, $channel] = review175uHolder($walletId);
    $holder = (int) trim((string) fgets($channel));
    $watcher = pcntl_fork();
    if ($watcher === 0) {
        DB::purge();
        $deadline = microtime(true) + 3;
        $seen = false;
        while (microtime(true) < $deadline) {
            if ((int) DB::selectOne('SELECT count(*) AS n FROM pg_stat_activity WHERE ? = ANY(pg_blocking_pids(pid))', [$holder])->n > 0) {
                $seen = true;
                break;
            }
            usleep(10000);
        }
        fwrite($channel, "release\n");
        exit($seen ? 0 : 4);
    }
    try {
        DB::statement("SET lock_timeout = '6s'");
        $outcome = DB::transaction($call);
        pcntl_waitpid($watcher, $watchStatus);
    } finally {
        @fwrite($channel, "release\n");
        pcntl_waitpid($pid, $status);
        fclose($channel);
        DB::purge();
    }

    return [$outcome, pcntl_wexitstatus($watchStatus) === 0];
}

it('K1 (inverted 175r C1): a REPEATABLE READ caller whose snapshot predates a refund is refused, and no FOR UPDATE runs', function (): void {
    [$wallet, $source] = review175uCash();
    try {
        DB::beginTransaction();
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::selectOne('SELECT count(*) FROM ledger_entries');
        $statements = [];
        DB::listen(function (QueryExecuted $query) use (&$statements): void {
            $statements[] = $query->sql;
        });
        $outcome = review175uOutcome(fn () => app(PrimaryCommittedCash::class)->requireCommitted($wallet, WalletMoney::of('5000'), $source));
    } finally {
        DB::rollBack();
    }
    expect($outcome)->toBe('PRIMARY_CASH_ISOLATION_REQUIRED')
        ->and(array_values(array_filter($statements, fn (string $sql): bool => str_contains($sql, 'for update'))))->toBe([]);
});

it('K2 isolation bypass attempts are refused or impossible', function (string $case): void {
    [$wallet, $source] = review175uCash();
    $call = fn () => app(PrimaryCommittedCash::class)->requireCommitted($wallet, WalletMoney::of('5000'), $source);
    $outcome = 'none';
    try {
        match ($case) {
            'session characteristics RR' => DB::statement('SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL REPEATABLE READ'),
            'default_transaction_isolation serializable' => DB::statement("SET default_transaction_isolation = 'serializable'"),
            'read uncommitted' => null,
            default => null,
        };
        DB::beginTransaction();
        if ($case === 'read uncommitted') {
            DB::statement('SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
        }
        if ($case === 'RR outer, call inside savepoint') {
            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $outcome = review175uOutcome(fn () => DB::transaction($call));
        } elseif ($case === 'RC outer, SET TRANSACTION after the check') {
            $first = review175uOutcome($call);
            DB::statement('SAVEPOINT late');
            $late = review175uOutcome(fn () => DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ'));
            DB::statement('ROLLBACK TO SAVEPOINT late');
            $lateGuc = review175uOutcome(fn () => DB::statement("SET transaction_isolation = 'repeatable read'"));
            DB::statement('ROLLBACK TO SAVEPOINT late');
            $lateLocal = review175uOutcome(fn () => DB::statement("SET LOCAL transaction_isolation = 'serializable'"));
            DB::statement('ROLLBACK TO SAVEPOINT late');
            $outcome = implode('|', [$first, $late, $lateGuc, $lateLocal, DB::scalar("SELECT current_setting('transaction_isolation')")]);
        } elseif ($case === 'RC outer, SET TRANSACTION inside a savepoint first') {
            DB::statement('SAVEPOINT probe');
            $outcome = review175uOutcome(fn () => DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ'));
        } else {
            $outcome = review175uOutcome($call);
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::statement('RESET default_transaction_isolation');
    }
    expect($outcome)->toBe(match ($case) {
        'RC outer, SET TRANSACTION after the check' => 'evidence|sql:25001|sql:25001|sql:25001|read committed',
        'RC outer, SET TRANSACTION inside a savepoint first' => 'sql:25001',
        'RC control' => 'evidence',
        default => 'PRIMARY_CASH_ISOLATION_REQUIRED',
    });
})->with(['session characteristics RR', 'default_transaction_isolation serializable', 'read uncommitted', 'RR outer, call inside savepoint',
    'RC outer, SET TRANSACTION after the check', 'RC outer, SET TRANSACTION inside a savepoint first', 'RC control']);

it('K3 observation: the transaction guard trusts Laravel\'s counter; after a raw COMMIT the port runs in autocommit', function (): void {
    [$wallet, $source] = review175uCash();
    try {
        DB::beginTransaction();
        DB::statement('COMMIT');
        $outcome = review175uOutcome(fn () => app(PrimaryCommittedCash::class)->requireCommitted($wallet, WalletMoney::of('5000'), $source));
        $lockStillHeld = DB::scalar('SELECT pg_current_xact_id_if_assigned() IS NOT NULL');
    } finally {
        try {
            DB::rollBack();
        } catch (Throwable) {
        }
        DB::purge();
    }
    expect($outcome)->toBe('evidence')->and($lockStillHeld)->toBeFalse(); // the FOR UPDATE ended with its own statement
});

it('K4 (inverted 175r C2): a foreign wallet token is refused without waiting on that wallet', function (): void {
    [$wallet, $source, $unrelated] = review175uCash();
    $started = microtime(true);
    [$outcome, $blocked] = review175uAgainstHeld($unrelated->walletId, function () use ($wallet, $source, $unrelated): string {
        app(WalletPostings::class)->lockForParty($wallet->partyId);

        return review175uOutcome(fn () => app(PrimaryCommittedCash::class)->requireCommitted($unrelated, WalletMoney::of('5000'), $source));
    });
    expect($outcome)->toBe('WALLET_POSTING_CONFLICT')->and($blocked)->toBeFalse();
});

it('K5 residual: a source with no ledger entries still locks an unrelated supplied wallet before refusing', function (): void {
    [$wallet, , $unrelated] = review175uCash();
    $bogus = new PostingSource('primary_reservation', strtolower((string) Str::ulid()), strtolower((string) Str::ulid()));
    [$outcome, $blocked] = review175uAgainstHeld($unrelated->walletId, function () use ($wallet, $bogus, $unrelated): string {
        app(WalletPostings::class)->lockForParty($wallet->partyId);

        return review175uOutcome(fn () => app(PrimaryCommittedCash::class)->requireCommitted($unrelated, WalletMoney::of('5000'), $bogus));
    });
    expect($outcome)->toBe('PRIMARY_COMMITTED_CASH_REQUIRED')->and($blocked)->toBeTrue(); // residual: blocked on a wallet the source never touched
});
