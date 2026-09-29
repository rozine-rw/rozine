<?php

declare(strict_types=1);

/* Review 175r concurrency repros for PrimaryCommittedCash (e12a6ea9). Copy into tests/Concurrency to run. */

use App\Application\Wallet\Contracts\PrimaryCommittedCash;
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

/** @return array{LockedWallet, PostingSource, LockedWallet} */
function review175rCash(): array
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

/**
 * @param  Closure(resource): int  $child  runs in a forked process with its own connection
 * @return array{int, resource}
 */
function review175rFork(Closure $child): array
{
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 15);
    stream_set_timeout($channels[1], 15);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '10s'");
            exit($child($channels[1]));
        } catch (Throwable $exception) {
            fwrite(STDERR, 'child '.$exception::class.' '.$exception->getMessage()."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    DB::purge();

    return [$pid, $channels[0]];
}

it('C1 REPEATABLE READ caller: a refund committed after the snapshot is invisible after the wallet lock', function (): void {
    [$wallet, $source] = review175rCash();
    [$pid, $channel] = review175rFork(function ($channel) use ($wallet, $source): int {
        if (fgets($channel) !== "go\n") {
            return 3;
        }
        DB::transaction(fn () => app(WalletPostings::class)->refund($wallet, WalletMoney::of('5000'), $source));
        fwrite($channel, "refunded\n");

        return 0;
    });
    $outcome = 'none';
    try {
        DB::beginTransaction();
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::selectOne('SELECT count(*) FROM ledger_entries'); // snapshot taken here, as any earlier caller read would
        fwrite($channel, "go\n");
        expect(fgets($channel))->toBe("refunded\n");
        try {
            $evidence = app(PrimaryCommittedCash::class)->requireCommitted($wallet, WalletMoney::of('5000'), $source);
            $outcome = 'evidence:'.$evidence->reservationId;
        } catch (Throwable $exception) {
            $outcome = $exception::class.':'.($exception instanceof WalletViolation ? $exception->reason : $exception->getCode());
        }
        DB::rollBack();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        pcntl_waitpid($pid, $status);
        fclose($channel);
        DB::purge();
    }
    fwrite(STDERR, "C1 outcome: {$outcome}\n");
    expect(LedgerEntry::query()->where('source_id', $source->id)->where('kind', 'primary_refund')->count())->toBe(1)
        ->and($outcome)->toBe('evidence:'.$source->id); // stale: refunded cash counted
});

it('C2 locks the supplied wallet before checking it owns the source (blocks on an unrelated wallet)', function (): void {
    [$wallet, $source, $unrelated] = review175rCash();
    [$pid, $channel] = review175rFork(function ($channel) use ($unrelated): int {
        DB::beginTransaction();
        InvestorWallet::query()->whereKey($unrelated->walletId)->lockForUpdate()->first();
        fwrite($channel, DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
        fgets($channel); // hold until the parent says release
        DB::commit();

        return 0;
    });
    $childBackend = (int) trim((string) fgets($channel));
    $blocked = false;
    $reason = null;
    try {
        $watcher = pcntl_fork();
        if ($watcher === 0) {
            DB::purge();
            // Separate observer: wait until the parent is blocked by the child, then release the child.
            $deadline = microtime(true) + 5;
            $seen = false;
            while (microtime(true) < $deadline) {
                $row = DB::selectOne("SELECT count(*) AS n FROM pg_stat_activity WHERE ? = ANY(pg_blocking_pids(pid))", [$childBackend]);
                if ((int) $row->n > 0) {
                    $seen = true;
                    break;
                }
                usleep(10000);
            }
            fwrite($channel, "release\n");
            exit($seen ? 0 : 4);
        }
        DB::transaction(function () use ($wallet, $source, $unrelated, &$reason): void {
            app(WalletPostings::class)->lockForParty($wallet->partyId);
            try {
                app(PrimaryCommittedCash::class)->requireCommitted($unrelated, WalletMoney::of('5000'), $source);
            } catch (WalletViolation $exception) {
                $reason = $exception->reason;
            }
        });
        pcntl_waitpid($watcher, $watchStatus);
        $blocked = pcntl_wexitstatus($watchStatus) === 0;
    } finally {
        pcntl_waitpid($pid, $status);
        fclose($channel);
        DB::purge();
    }
    expect($blocked)->toBeTrue()->and($reason)->toBe('WALLET_POSTING_CONFLICT');
});
