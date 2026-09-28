<?php

declare(strict_types=1);

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

/** @return array{LockedWallet, PostingSource} */
function retainedFundingCash(): array
{
    return DB::transaction(function (): array {
        PrimaryCommitment::factory()->create();
        $root = PrimaryReservationRecord::query()->sole();
        $wallet = InvestorWallet::query()->where('party_id', $root->party_id)->sole();

        return [new LockedWallet($wallet->id, $wallet->party_id), new PostingSource('primary_reservation', $root->id, $root->origin_operation_id)];
    });
}

it('requires a caller transaction even when a wallet token came from a completed transaction', function (): void {
    [$wallet, $source] = retainedFundingCash();
    expect(fn () => app(PrimaryCommittedCash::class)->requireCommitted($wallet, WalletMoney::of('5000'), $source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_TRANSACTION_REQUIRED');
});

it('serializes funding cash evidence with refund in either arrival order', function (bool $refundFirst): void {
    [$wallet, $source] = retainedFundingCash();
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create funding cash barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork funding cash contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            DB::transaction(function () use ($refundFirst, $wallet, $source): void {
                if ($refundFirst) {
                    app(PrimaryCommittedCash::class)->requireCommitted($wallet, WalletMoney::of('5000'), $source);
                } else {
                    app(WalletPostings::class)->refund($wallet, WalletMoney::of('5000'), $source);
                }
            });
            exit(0);
        } catch (WalletViolation $exception) {
            exit($exception->reason === 'PRIMARY_COMMITTED_CASH_REQUIRED' ? 2 : 1);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getCode()."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    $childBackend = (int) trim((string) fgets($channels[0]));
    DB::purge();
    try {
        DB::beginTransaction();
        $parentBackend = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        if ($refundFirst) {
            app(WalletPostings::class)->refund($wallet, WalletMoney::of('5000'), $source);
        } else {
            app(PrimaryCommittedCash::class)->requireCommitted($wallet, WalletMoney::of('5000'), $source);
        }
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
        pcntl_waitpid($pid, $status);
        expect($blocked)->toBeTrue()->and(pcntl_wifexited($status))->toBeTrue()
            ->and(pcntl_wexitstatus($status))->toBe($refundFirst ? 2 : 0);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
        DB::purge();
    }
    expect(LedgerEntry::query()->where('source_id', $source->id)->where('kind', 'primary_refund')->count())->toBe(1);
    expect(fn () => DB::transaction(fn () => app(PrimaryCommittedCash::class)->requireCommitted($wallet, WalletMoney::of('5000'), $source)))
        ->toThrow(WalletViolation::class, 'PRIMARY_COMMITTED_CASH_REQUIRED');
})->with(['refund first' => true, 'funding read first' => false]);
