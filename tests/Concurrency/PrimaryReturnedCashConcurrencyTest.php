<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->freezeSecond();
});

/** @return array{LockedWallet, PostingSource} */
function retainedReturnCash(): array
{
    return DB::transaction(function (): array {
        PrimaryCommitment::factory()->create();
        $root = PrimaryReservationRecord::query()->sole();
        $wallet = InvestorWallet::query()->where('party_id', $root->party_id)->sole();

        return [new LockedWallet($wallet->id, $wallet->party_id), new PostingSource('primary_reservation', $root->id, $root->origin_operation_id)];
    });
}

it('requires a caller transaction even when a wallet token came from a completed transaction', function (): void {
    [$wallet, $source] = retainedReturnCash();
    expect(fn () => app(PrimaryReturnedCash::class)->requireReturned($wallet, WalletMoney::of('5000'), $source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_TRANSACTION_REQUIRED');
});

it('waits for a concurrent refund before proving that its cash returned', function (): void {
    [$wallet, $source] = retainedReturnCash();
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create returned cash barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($channels[0]);
        fclose($channels[1]);
        throw new RuntimeException('Could not fork returned cash contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        $code = 1;
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                throw new RuntimeException('Returned cash barrier timed out.');
            }
            DB::transaction(function () use ($wallet, $source): void {
                app(PrimaryReturnedCash::class)->requireReturned($wallet, WalletMoney::of('5000'), $source);
            });
            $code = 0;
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getCode()."\n");
        } finally {
            fclose($channels[1]);
            DB::purge();
        }
        exit($code);
    }
    fclose($channels[1]);
    $reaped = false;
    DB::purge();
    try {
        $childBackend = (int) trim((string) fgets($channels[0]));
        if ($childBackend < 1) {
            throw new RuntimeException('Returned cash contender did not report its backend.');
        }
        DB::beginTransaction();
        $parentBackend = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        app(WalletPostings::class)->refund($wallet, WalletMoney::of('5000'), $source);
        fwrite($channels[0], "go\n");
        $blocked = false;
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            if ((bool) DB::selectOne('SELECT ? = ANY(pg_blocking_pids(?)) AS blocked', [$parentBackend, $childBackend])->blocked) {
                $blocked = true;
                break;
            }
            usleep(10000);
        }
        DB::commit();
        $deadline = microtime(true) + 10;
        do {
            $reaped = pcntl_waitpid($pid, $status, WNOHANG) === $pid;
            if (! $reaped) {
                usleep(10000);
            }
        } while (! $reaped && microtime(true) < $deadline);
        if (! $reaped) {
            throw new RuntimeException('Returned cash contender did not finish.');
        }
        expect($blocked)->toBeTrue()->and(pcntl_wifexited($status))->toBeTrue()
            ->and(pcntl_wexitstatus($status))->toBe(0);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        if (! $reaped) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
    }
    expect(LedgerEntry::query()->where('source_id', $source->id)->where('kind', 'primary_refund')->count())->toBe(1);
    expect(DB::transaction(fn () => app(PrimaryReturnedCash::class)->requireReturned($wallet, WalletMoney::of('5000'), $source))->returnKind)
        ->toBe('primary_refund');
});

it('refuses snapshot isolation before counting retained cash', function (string $isolation): void {
    [$wallet, $source] = retainedReturnCash();
    try {
        DB::beginTransaction();
        DB::statement('SET TRANSACTION ISOLATION LEVEL '.$isolation);
        expect(fn () => app(PrimaryReturnedCash::class)->requireReturned($wallet, WalletMoney::of('5000'), $source))
            ->toThrow(WalletViolation::class, 'PRIMARY_CASH_ISOLATION_REQUIRED');
    } finally {
        DB::rollBack();
    }
})->with(['REPEATABLE READ', 'SERIALIZABLE']);
