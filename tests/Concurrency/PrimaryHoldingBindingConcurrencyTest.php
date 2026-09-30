<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryFunding;
use App\Domain\Wallet\WalletViolation;
use App\Models\PrimaryCampaignFunding;
use App\Models\PrimaryCommitment;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;

/*
 * The Holding binding adds no row lock of its own. Its foreign keys take FOR KEY SHARE on the
 * retained reservation, revision, commitment and funding member, which conflicts only with the
 * FOR UPDATE the funding lock takes on reservations and commitments. Either side waits for the
 * other and neither deadlocks. A funding-lock retry that waited behind a committed issue is then
 * refused by #175 itself (`PRIMARY_COMMITTED_CASH_REQUIRED`, exit 4): the cash is no longer committed.
 */
it('waits for, and is waited on by, the funding lock without deadlock', function (bool $fundingFirst): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    [$first] = $commitments;
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $rows = array_map(fn (PrimaryCommitment $commitment): array => PrimaryHoldingFixture::row($commitment->id, $closing), $commitments);
    $funding = PrimaryCampaignFunding::query()->sole()->id;
    // The whole issue as the adapter must order it: every Holding first, then the Party-locked issue postings.
    $issue = function () use ($rows, $commitments, $closing): void {
        DB::table('primary_holdings')->insert($rows);
        foreach ($commitments as $commitment) {
            PrimaryHoldingFixture::issue($commitment->id, $closing);
        }
    };
    $lock = fn (): array => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => PrimaryHoldingFixture::admission($campaign));
    [$held, $contended] = $fundingFirst ? [$lock, $issue] : [$issue, $lock];
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create the Holding barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork the Holding contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            DB::transaction(fn () => $contended());
            exit(0);
        } catch (Throwable $exception) {
            if ($exception instanceof WalletViolation && $exception->getMessage() === 'PRIMARY_COMMITTED_CASH_REQUIRED') {
                exit(4);
            }
            fwrite(STDERR, $exception::class.' '.$exception->getMessage()."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    $waiter = (int) trim((string) fgets($channels[0]));
    DB::purge();
    try {
        DB::beginTransaction();
        $holder = DB::scalar('SELECT pg_backend_pid()');
        $held();
        fwrite($channels[0], "go\n");
        $waitingQuery = '';
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            if ((bool) DB::scalar('SELECT ?::int = ANY(pg_blocking_pids(?::int))', [$holder, $waiter])) {
                $waitingQuery = (string) DB::scalar('SELECT query FROM pg_stat_activity WHERE pid = ?', [$waiter]);
                break;
            }
            usleep(10_000);
        }
        expect(DB::scalar('SELECT count(*) FROM pg_locks WHERE pid = ? AND NOT granted', [$holder]))->toBe(0);
        DB::commit();
        pcntl_waitpid($pid, $status);
        expect($waitingQuery)->toContain($fundingFirst ? 'insert into "primary_holdings"' : '"primary_reservations"')
            ->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe($fundingFirst ? 0 : 4)
            ->and(DB::table('primary_holdings')->where('commitment_id', $first->id)->count())->toBe(1)
            ->and(PrimaryCampaignFunding::query()->sole()->id)->toBe($funding);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
        DB::purge();
    }
})->with(['the funding lock is held first' => true, 'the Holding insert is held first' => false]);
