<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * Install probes for the two Holdings migrations (084737 then 114217, and their down() in reverse)
 * against REAL commands in flight. One process is paused mid-transaction holding its locks, the
 * other starts, and pg_blocking_pids shows who waits on whom. Safe means: both exit 0 and
 * PostgreSQL counted no deadlock. Both orders are probed: a command paused first, and the install
 * paused first with everything it locks.
 */

const HOLDING_MIGRATIONS = ['2026_09_30_084737_bind_primary_holdings_to_retained_commitments', '2026_09_30_114217_require_issue_evidence_for_primary_holdings'];

function holdingInstall(string $direction): void
{
    foreach ($direction === 'up' ? HOLDING_MIGRATIONS : array_reverse(HOLDING_MIGRATIONS) as $migration) {
        (require database_path('migrations/'.$migration.'.php'))->{$direction}();
    }
}

/** Brings the schema back to fully installed whatever a failed probe left behind. */
function holdingRestore(): void
{
    $markers = ["SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'primary_holdings' AND column_name = 'primary_reservation_id')",
        "SELECT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'primary_holding_issue_bound')"];
    foreach (HOLDING_MIGRATIONS as $index => $migration) {
        if (! (bool) DB::scalar($markers[$index])) {
            (require database_path('migrations/'.$migration.'.php'))->up();
        }
    }
}

function holdingDeadlocks(): int
{
    DB::statement('SELECT pg_stat_clear_snapshot()');

    return (int) DB::scalar('SELECT deadlocks FROM pg_stat_database WHERE datname = current_database()');
}

/**
 * Forks a worker that reports its backend pid, waits for "go", runs, and exits 0, or 2 when it was a deadlock victim.
 *
 * @return array{int, resource, int}
 */
function holdingFork(Closure $work): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create the probe barrier.');
    }
    stream_set_timeout($channels[0], 30);
    stream_set_timeout($channels[1], 30);
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork the probe worker.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '20s'");
            fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $work($channels[1]);
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, 'holding probe worker: '.$exception::class.' '.mb_substr((string) preg_replace('/\s+/', ' ', $exception->getMessage()), 0, 300)."\n");
            exit(str_contains($exception->getMessage(), '40P01') ? 2 : 1);
        }
    }
    fclose($channels[1]);

    return [$pid, $channels[0], (int) trim((string) fgets($channels[0]))];
}

/** Pauses the worker once, right after its first statement that starts with $pauseAfter, until the parent says "go". */
function holdingPauseAfter(mixed $channel, string $pauseAfter): void
{
    $paused = false;
    DB::listen(function (QueryExecuted $query) use ($channel, &$paused, $pauseAfter): void {
        if (! $paused && str_starts_with($query->sql, $pauseAfter)) {
            $paused = true;
            fwrite($channel, "paused\n");
            if (fgets($channel) !== "go\n") {
                throw new RuntimeException('The probe barrier was lost.');
            }
        }
    });
}

/**
 * Prepares the state a command needs and returns the command itself.
 *
 * @return Closure(): string
 */
function holdingCommand(string $command, mixed $test): Closure
{
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    $buy = function (string $units, bool $confirm) use ($campaign, $checkout): array {
        $investor = PrimaryReservationFixture::investor();
        $id = $checkout->reserve($investor['user']->id, 1, $campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...))['data']['reservation_id'];
        $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $id)->sole();
        $confirmation = fn (): string => $checkout->confirm($investor['user']->id, 1, $campaign->id, $id, 1, $held->payload['terms']['disclosure_version'],
            $held->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
        if ($confirm) {
            $confirmation();
        }

        return ['investor' => $investor, 'id' => $id, 'confirm' => $confirmation];
    };
    switch ($command) {
        case 'reserve':
            $investor = PrimaryReservationFixture::investor();

            return fn (): string => $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
        case 'confirm':
            return $buy('3', false)['confirm'];
        case 'release':
            $held = $buy('3', false);

            return fn (): string => $checkout->release($held['investor']['user']->id, 1, $campaign->id, $held['id'], 1, (string) Str::uuid())['code'];
        case 'cancel':
            return fn (): string => app(BusinessCampaignStore::class)->cancel($campaign->actor_user_id, 1, $campaign->business_id, $campaign->id, 1, null, (string) Str::uuid())['code'];
        case 'refund':
            $root = PrimaryReservationRecord::query()->whereKey($buy('3', true)['id'])->sole();

            return function () use ($root): string {
                $wallets = app(WalletPostings::class);
                DB::transaction(fn () => $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
                    new PostingSource('primary_reservation', $root->id, $root->origin_operation_id)));

                return 'REFUNDED';
            };
        case 'fund':
            $buy('1080', true);
            $buy('1080', true);

            return fn (): string => DB::transaction(fn (): array => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => PrimaryHoldingFixture::admission($campaign)))['scope'];
        case 'sweep reservations':
            $root = PrimaryReservationRecord::query()->whereKey($buy('3', false)['id'])->sole();
            $test->travelTo($root->expires_at);

            return fn (): string => 'SWEPT '.app(PrimaryReservations::class)->expireDue(10);
        default:
            $test->travelTo($campaign->expires_at);

            return fn (): string => 'SWEPT '.app(BusinessCampaignStore::class)->expireDue(10);
    }
}

const HOLDING_COMMAND_OUTCOMES = ['RESERVATION_HELD', 'RESERVATION_CONFIRMED', 'RESERVATION_RELEASED', 'CAMPAIGN_CANCELLED', 'REFUNDED', 'primary-funding-v1', 'SWEPT 1'];

it('queues the Holdings install behind a command paused mid-write and never deadlocks', function (string $command, string $pauseAfter, string $direction): void {
    $this->freezeSecond();
    $run = holdingCommand($command, $this);
    if ($direction === 'up') {
        holdingInstall('down');
    }
    DB::disconnect();
    [$commandPid, $commandChannel, $commandBackend] = holdingFork(function ($channel) use ($run, $pauseAfter): void {
        holdingPauseAfter($channel, $pauseAfter);
        $outcome = $run();
        if (! in_array($outcome, HOLDING_COMMAND_OUTCOMES, true)) {
            throw new RuntimeException('command '.$outcome);
        }
    });
    [$installPid, $installChannel, $installBackend] = holdingFork(fn () => holdingInstall($direction));
    DB::purge();
    $before = holdingDeadlocks();
    $exits = [];
    $installWaited = false;
    try {
        fwrite($commandChannel, "go\n");
        $paused = trim((string) fgets($commandChannel)) === 'paused';
        fwrite($installChannel, "go\n");
        $deadline = microtime(true) + 2.0;
        while (microtime(true) < $deadline && ! $installWaited) {
            $installWaited = (bool) DB::scalar('SELECT ?::int = ANY(pg_blocking_pids(?::int))', [$commandBackend, $installBackend]);
            usleep(10_000);
        }
        $cycle = (bool) DB::scalar('SELECT ?::int = ANY(pg_blocking_pids(?::int))', [$installBackend, $commandBackend]);
        @fwrite($commandChannel, "go\n");
        foreach ([$commandPid, $installPid] as $pid) {
            pcntl_waitpid($pid, $status);
            $exits[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
        }
    } finally {
        fclose($commandChannel);
        fclose($installChannel);
        foreach ([$commandPid, $installPid] as $pid) {
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        holdingRestore();
    }
    fwrite(STDERR, sprintf("\n[%s install vs %s paused after %s] install waited on the command: %s | exits(command, install)=%s\n",
        $direction, $command, $pauseAfter, var_export($installWaited, true), json_encode($exits)));
    expect($paused)->toBeTrue()->and($cycle)->toBeFalse()
        ->and(['exits' => $exits, 'deadlocks' => holdingDeadlocks() - $before])->toBe(['exits' => [0, 0], 'deadlocks' => 0]);
})->with([
    ['reserve', 'insert into "command_operations"'],
    ['reserve', 'insert into "primary_reservations"'],
    ['confirm', 'insert into "command_operations"'],
    ['confirm', 'insert into "primary_reservation_versions"'],
    ['confirm', 'insert into "primary_commitments"'],
    ['release', 'insert into "command_operations"'],
    ['release', 'insert into "primary_reservation_versions"'],
    ['cancel', 'insert into "command_operations"'],
    ['cancel', 'insert into "business_campaign_closures"'],
    ['refund', 'insert into "ledger_entries"'],
    ['fund', 'insert into "primary_campaign_fundings"'],
    ['fund', 'insert into "primary_funding_commitments"'],
    ['sweep reservations', 'insert into "primary_reservation_versions"'],
    ['sweep campaigns', 'insert into "business_campaign_closures"'],
])->with(['up', 'down']);

it('queues a command behind the Holdings install holding every lock it takes and never deadlocks', function (string $command, string $direction): void {
    $this->freezeSecond();
    $run = holdingCommand($command, $this);
    if ($direction === 'up') {
        holdingInstall('down');
    }
    DB::disconnect();
    [$installPid, $installChannel, $installBackend] = holdingFork(function ($channel) use ($direction): void {
        DB::transaction(function () use ($channel, $direction): void {
            holdingInstall($direction);
            fwrite($channel, "paused\n");
            if (fgets($channel) !== "go\n") {
                throw new RuntimeException('The probe barrier was lost.');
            }
        });
    });
    [$commandPid, $commandChannel, $commandBackend] = holdingFork(function () use ($run): void {
        $outcome = $run();
        if (! in_array($outcome, HOLDING_COMMAND_OUTCOMES, true)) {
            throw new RuntimeException('command '.$outcome);
        }
    });
    DB::purge();
    $before = holdingDeadlocks();
    $exits = [];
    $commandWaited = false;
    try {
        fwrite($installChannel, "go\n");
        $paused = trim((string) fgets($installChannel)) === 'paused';
        fwrite($commandChannel, "go\n");
        $deadline = microtime(true) + 2.0;
        while (microtime(true) < $deadline && ! $commandWaited) {
            $commandWaited = (bool) DB::scalar('SELECT ?::int = ANY(pg_blocking_pids(?::int))', [$installBackend, $commandBackend]);
            usleep(10_000);
        }
        $held = DB::select("SELECT relation::regclass::text AS relation FROM pg_locks WHERE pid = ? AND granted AND locktype = 'relation' AND mode IN ('ShareRowExclusiveLock', 'AccessExclusiveLock') ORDER BY 1", [$installBackend]);
        @fwrite($installChannel, "go\n");
        foreach ([$commandPid, $installPid] as $pid) {
            pcntl_waitpid($pid, $status);
            $exits[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
        }
    } finally {
        fclose($commandChannel);
        fclose($installChannel);
        foreach ([$commandPid, $installPid] as $pid) {
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        holdingRestore();
    }
    fwrite(STDERR, sprintf("\n[%s install held first vs %s] command waited on the install: %s | install held: %s | exits(command, install)=%s\n",
        $direction, $command, var_export($commandWaited, true), implode(',', array_unique(array_column($held, 'relation'))), json_encode($exits)));
    expect($paused)->toBeTrue()->and($commandWaited)->toBeTrue()
        ->and(['exits' => $exits, 'deadlocks' => holdingDeadlocks() - $before])->toBe(['exits' => [0, 0], 'deadlocks' => 0]);
})->with(['reserve', 'confirm', 'release', 'refund', 'fund', 'sweep reservations'])->with(['up', 'down']);
