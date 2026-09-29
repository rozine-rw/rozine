<?php

declare(strict_types=1);

/*
 * Review 175u probes for PR #175 at 1cffe3b4 (195022 now locks only primary_commitments IN SHARE ROW EXCLUSIVE MODE).
 * Copy into tests/Concurrency/. Exit codes: 0 ok, 1 error, 2 = 40P01 deadlock victim, 3 = barrier failure.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\CommandOperation;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

function r175uWait(string $sql, array $bindings = [], float $seconds = 8.0): bool
{
    $deadline = microtime(true) + $seconds;
    while (microtime(true) < $deadline) {
        if ((bool) DB::selectOne($sql, $bindings)->ok) {
            return true;
        }
        usleep(10000);
    }

    return false;
}

function r175uDeadlocks(): int
{
    DB::statement('SELECT pg_stat_clear_snapshot()');

    return (int) DB::selectOne('SELECT deadlocks FROM pg_stat_database WHERE datname = current_database()')->deadlocks;
}

function r175uDeadlocksSince(int $before): int
{
    $deadline = microtime(true) + 2.0;
    do {
        $delta = r175uDeadlocks() - $before;
        if ($delta > 0) {
            return $delta;
        }
        usleep(100_000);
    } while (microtime(true) < $deadline);

    return 0;
}

/** @return array{int, resource, int} */
function r175uFork(Closure $work): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 20);
    stream_set_timeout($channels[1], 20);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '10s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $work($channels[1]);
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, 'r175u child '.getmypid().': '.$exception::class.' '.mb_substr($exception->getMessage(), 0, 240)."\n");
            exit(str_contains($exception->getMessage(), '40P01') ? 2 : 1);
        }
    }
    fclose($channels[1]);

    return [$pid, $channels[0], (int) trim((string) fgets($channels[0]))];
}

function r175uPause($channel, string $label): void
{
    fwrite($channel, $label."\n");
    if (fgets($channel) !== "go\n") {
        throw new RuntimeException('barrier lost at '.$label);
    }
}

/** @param list<int> $pids @return list<int> */
function r175uReap(array $pids): array
{
    $results = [];
    foreach ($pids as $pid) {
        $status = 0;
        $got = pcntl_waitpid($pid, $status);
        $results[] = $got > 0 && pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
    }

    return $results;
}

function r175uExited(int $pid): ?int
{
    $status = 0;
    $got = pcntl_waitpid($pid, $status, WNOHANG);

    return $got > 0 ? (pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1) : null;
}

function r175uTriggers(): int
{
    return (int) DB::selectOne("SELECT count(*) AS n FROM pg_trigger WHERE tgname = 'primary_commitment_receipt_bound'")->n;
}

function r175uMigration(): object
{
    return require database_path('migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php');
}

/** A real confirm in a child; optionally pauses right after its commitment INSERT (holding ROW EXCLUSIVE on primary_commitments). */
function r175uConfirm(array $investor, string $campaignId, string $reservationId, bool $pauseAfterInsert): Closure
{
    $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $reservationId)->sole();

    return function ($channel) use ($investor, $campaignId, $reservationId, $held, $pauseAfterInsert): void {
        if ($pauseAfterInsert) {
            DB::listen(function (QueryExecuted $query) use ($channel): void {
                if (str_starts_with($query->sql, 'insert into "primary_commitments"')) {
                    r175uPause($channel, 'inserted');
                }
            });
        }
        $result = app(PrimaryCheckout::class)->confirm($investor['user']->id, 1, $campaignId, $reservationId, 1, $held->payload['terms']['disclosure_version'],
            $held->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        if ($result['code'] !== 'RESERVATION_CONFIRMED') {
            throw new RuntimeException('confirm '.$result['code']);
        }
    };
}

function r175uCancel($campaign): Closure
{
    $signatory = User::query()->findOrFail($campaign->actor_user_id);

    return function () use ($signatory, $campaign): void {
        $result = app(BusinessCampaignStore::class)->cancel($signatory->id, 1, $campaign->business_id, $campaign->id, 1, 'Probe.', (string) Str::uuid());
        if ($result['code'] !== 'CAMPAIGN_CANCELLED') {
            throw new RuntimeException('cancel '.$result['code']);
        }
    };
}

/** @return array{0: array, 1: array<int, object>, 2: array<int, string>, 3: array} investors, campaigns, reservation ids */
function r175uWorld(int $campaigns, int $reservations): array
{
    test()->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $made = [];
    for ($i = 0; $i < $campaigns; $i++) {
        $made[] = PrimaryReservationFixture::campaign();
        test()->travel(2)->minutes();
    }
    $investors = [];
    $ids = [];
    for ($i = 0; $i < $reservations; $i++) {
        $investors[$i] = PrimaryReservationFixture::investor();
        $ids[$i] = app(PrimaryCheckout::class)->reserve($investors[$i]['user']->id, 1, $made[$i]->id, '3', (string) Str::uuid(),
            PrimaryReservationFixture::terms(...))['data']['reservation_id'];
    }

    return [$investors, $made, $ids];
}

it('U-A (175q probe A, new lock): a paused real confirm + install + real cancel of another Business all commit; the install does not wait for the confirm', function (): void {
    [$investors, $campaigns, $ids] = r175uWorld(2, 1);
    $migration = r175uMigration();
    $migration->down();
    DB::disconnect();
    [$confirmPid, $confirmChannel] = r175uFork(function ($channel) use ($investors, $campaigns, $ids): void {
        $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $ids[0])->sole();
        $result = app(PrimaryCheckout::class)->confirm($investors[0]['user']->id, 1, $campaigns[0]->id, $ids[0], 1, $held->payload['terms']['disclosure_version'],
            $held->payload['disclosure_sha256'], (string) Str::uuid(), function ($rights, array $input) use ($channel) {
                r175uPause($channel, 'admitted');

                return PrimaryReservationFixture::terms($rights, $input);
            });
        if ($result['code'] !== 'RESERVATION_CONFIRMED') {
            throw new RuntimeException('confirm '.$result['code']);
        }
    });
    [$migrationPid, $migrationChannel] = r175uFork(fn () => $migration->up());
    [$cancelPid, $cancelChannel] = r175uFork(r175uCancel($campaigns[1]));
    DB::purge();
    $before = r175uDeadlocks();
    try {
        fwrite($confirmChannel, "go\n");
        $admitted = trim((string) fgets($confirmChannel)) === 'admitted';
        fwrite($migrationChannel, "go\n");
        fwrite($cancelChannel, "go\n");
        $deadline = microtime(true) + 8;
        $migrationExit = null;
        $cancelExit = null;
        while (microtime(true) < $deadline && ($migrationExit === null || $cancelExit === null)) {
            $migrationExit ??= r175uExited($migrationPid);
            $cancelExit ??= r175uExited($cancelPid);
            usleep(20_000);
        }
        fwrite($confirmChannel, "go\n");
        $confirmExit = r175uReap([$confirmPid])[0];
    } finally {
        foreach ([$confirmChannel, $migrationChannel, $cancelChannel] as $channel) {
            fclose($channel);
        }
        r175uReap([$confirmPid, $migrationPid, $cancelPid]);
        DB::purge();
        if (r175uTriggers() === 0) {
            $migration->up();
        }
    }
    expect($admitted)->toBeTrue()
        ->and(['confirm' => $confirmExit, 'migration_while_confirm_paused' => $migrationExit, 'cancel_while_confirm_paused' => $cancelExit, 'deadlocks' => r175uDeadlocksSince($before)])
        ->toBe(['confirm' => 0, 'migration_while_confirm_paused' => 0, 'cancel_while_confirm_paused' => 0, 'deadlocks' => 0])
        ->and(r175uTriggers())->toBe(1)->and(PrimaryCommitment::query()->count())->toBe(1);
});

it('U-B three-way: confirm paused after its commitment INSERT, install queued on SRE, a real cancel passes, a second real confirm queues, then all commit', function (): void {
    [$investors, $campaigns, $ids] = r175uWorld(3, 2);
    // campaigns[0]: paused confirm; campaigns[1]: second confirm; campaigns[2]: cancel (no commitments).
    $migration = r175uMigration();
    $migration->down();
    DB::disconnect();
    [$confirmPid, $confirmChannel, $confirmBackend] = r175uFork(r175uConfirm($investors[0], $campaigns[0]->id, $ids[0], true));
    [$migrationPid, $migrationChannel, $migrationBackend] = r175uFork(fn () => $migration->up());
    [$cancelPid, $cancelChannel] = r175uFork(r175uCancel($campaigns[2]));
    [$secondPid, $secondChannel, $secondBackend] = r175uFork(r175uConfirm($investors[1], $campaigns[1]->id, $ids[1], false));
    DB::purge();
    $before = r175uDeadlocks();
    try {
        fwrite($confirmChannel, "go\n");
        $inserted = trim((string) fgets($confirmChannel)) === 'inserted';
        fwrite($migrationChannel, "go\n");
        $migrationQueued = r175uWait("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_commitments'::regclass
            AND mode = 'ShareRowExclusiveLock' AND NOT granted) AND ? = ANY(pg_blocking_pids(?)) AS ok", [$migrationBackend, $confirmBackend, $migrationBackend]);
        fwrite($cancelChannel, "go\n");
        $deadline = microtime(true) + 8;
        $cancelExit = null;
        while (microtime(true) < $deadline && $cancelExit === null) {
            $cancelExit = r175uExited($cancelPid);
            usleep(20_000);
        }
        fwrite($secondChannel, "go\n");
        $secondQueued = r175uWait('SELECT ? = ANY(pg_blocking_pids(?)) AS ok', [$migrationBackend, $secondBackend]);
        usleep(600_000); // let deadlock_timeout pass with every edge in place
        fwrite($confirmChannel, "go\n");
        $results = r175uReap([$confirmPid, $migrationPid, $secondPid]);
    } finally {
        foreach ([$confirmChannel, $migrationChannel, $cancelChannel, $secondChannel] as $channel) {
            fclose($channel);
        }
        r175uReap([$confirmPid, $migrationPid, $cancelPid, $secondPid]);
        DB::purge();
        if (r175uTriggers() === 0) {
            $migration->up();
        }
    }
    expect($inserted)->toBeTrue()->and($migrationQueued)->toBeTrue()->and($secondQueued)->toBeTrue()
        ->and(['cancel_while_writer_open' => $cancelExit, 'exits' => $results, 'deadlocks' => r175uDeadlocksSince($before)])
        ->toBe(['cancel_while_writer_open' => 0, 'exits' => [0, 0, 0], 'deadlocks' => 0])
        ->and(r175uTriggers())->toBe(1)->and(PrimaryCommitment::query()->count())->toBe(2);
});

it('U-C the install audit sees a commitment committed by an in-flight writer (a mismatched receipt aborts the install)', function (): void {
    $migration = r175uMigration();
    $migration->down();
    DB::disconnect();
    [$migrationPid, $migrationChannel, $migrationBackend] = r175uFork(fn () => $migration->up());
    DB::purge();
    try {
        DB::beginTransaction();
        $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
        $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
        $operationId = strtolower((string) Str::ulid());
        $commitmentId = strtolower((string) Str::ulid());
        $version = PrimaryReservationVersion::factory()->confirmed()->withCashMovement()->create(['primary_reservation_id' => $root->id, 'operation_id' => $operationId]);
        PrimaryCommitment::factory()->create(['id' => $commitmentId, 'primary_reservation_version_id' => $version->id]);
        CommandOperation::factory()->create(['id' => $operationId, 'actor_key' => $origin->actor_key, 'actor_user_id' => $origin->actor_user_id,
            'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id,
            'result' => ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'operation_id' => $operationId, 'revision' => 2,
                'data' => ['reservation_id' => $root->id, 'commitment_id' => $commitmentId, 'amount' => '999999']]]);
        fwrite($migrationChannel, "go\n");
        $queued = r175uWait("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_commitments'::regclass AND NOT granted) AS ok", [$migrationBackend], 3.0);
        DB::commit();
        $exit = r175uReap([$migrationPid])[0];
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($migrationChannel);
        r175uReap([$migrationPid]);
        DB::purge();
        $triggersAfter = r175uTriggers();
        if ($triggersAfter === 0) {
            DB::statement('TRUNCATE primary_commitments CASCADE');
            $migration->up();
        }
    }
    expect($queued)->toBeTrue()->and($exit)->toBe(1)->and($triggersAfter)->toBe(0);
});

it('U-D rollback down() waits for an in-flight confirm and then refuses, instead of dropping the guard under a new commitment', function (): void {
    [$investors, $campaigns, $ids] = r175uWorld(1, 1);
    DB::disconnect();
    $migration = r175uMigration();
    [$confirmPid, $confirmChannel, $confirmBackend] = r175uFork(r175uConfirm($investors[0], $campaigns[0]->id, $ids[0], true));
    [$downPid, $downChannel, $downBackend] = r175uFork(fn () => $migration->down());
    DB::purge();
    $before = r175uDeadlocks();
    try {
        fwrite($confirmChannel, "go\n");
        $inserted = trim((string) fgets($confirmChannel)) === 'inserted';
        fwrite($downChannel, "go\n");
        $queued = r175uWait('SELECT ? = ANY(pg_blocking_pids(?)) AS ok', [$confirmBackend, $downBackend]);
        fwrite($confirmChannel, "go\n");
        $results = r175uReap([$confirmPid, $downPid]);
    } finally {
        fclose($confirmChannel);
        fclose($downChannel);
        r175uReap([$confirmPid, $downPid]);
        DB::purge();
        $triggersAfter = r175uTriggers();
        if ($triggersAfter === 0) {
            $migration->up();
        }
    }
    expect($inserted)->toBeTrue()->and($queued)->toBeTrue()
        ->and(['exits' => $results, 'triggers' => $triggersAfter, 'deadlocks' => r175uDeadlocksSince($before)])
        ->toBe(['exits' => [0, 1], 'triggers' => 1, 'deadlocks' => 0])
        ->and(PrimaryCommitment::query()->count())->toBe(1);
});

it('U-E two concurrent installers serialise on SHARE ROW EXCLUSIVE: one installs, the other fails cleanly, no deadlock', function (): void {
    $migration = r175uMigration();
    $migration->down();
    DB::disconnect();
    [$firstPid, $firstChannel] = r175uFork(fn () => $migration->up());
    [$secondPid, $secondChannel] = r175uFork(fn () => $migration->up());
    DB::purge();
    $before = r175uDeadlocks();
    try {
        DB::beginTransaction();
        DB::statement('LOCK TABLE primary_commitments IN ROW EXCLUSIVE MODE'); // hold both installers at the gate
        fwrite($firstChannel, "go\n");
        fwrite($secondChannel, "go\n");
        usleep(400_000);
        DB::commit();
        $results = r175uReap([$firstPid, $secondPid]);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($firstChannel);
        fclose($secondChannel);
        r175uReap([$firstPid, $secondPid]);
        DB::purge();
        if (r175uTriggers() === 0) {
            $migration->up();
        }
    }
    sort($results);
    expect(['exits' => $results, 'triggers' => r175uTriggers(), 'deadlocks' => r175uDeadlocksSince($before)])
        ->toBe(['exits' => [0, 1], 'triggers' => 1, 'deadlocks' => 0]);
});

it('U-F rollback down() with a cancel reader open and a confirm arriving: stalls, but no deadlock', function (): void {
    [$investors, $campaigns, $ids] = r175uWorld(2, 1);
    DB::disconnect();
    $migration = r175uMigration();
    [$cancelPid, $cancelChannel, $cancelBackend] = r175uFork(function ($channel) use ($campaigns): void {
        $paused = false;
        DB::listen(function (QueryExecuted $query) use ($channel, &$paused): void {
            if (! $paused && str_contains($query->sql, 'from "primary_commitments"')) {
                $paused = true;
                r175uPause($channel, 'read');
            }
        });
        r175uCancel($campaigns[1])();
    });
    [$downPid, $downChannel, $downBackend] = r175uFork(fn () => $migration->down());
    [$confirmPid, $confirmChannel, $confirmBackend] = r175uFork(r175uConfirm($investors[0], $campaigns[0]->id, $ids[0], false));
    DB::purge();
    $before = r175uDeadlocks();
    try {
        fwrite($cancelChannel, "go\n");
        $read = trim((string) fgets($cancelChannel)) === 'read';
        fwrite($downChannel, "go\n");
        $downWaitsOnReader = r175uWait("SELECT ? = ANY(pg_blocking_pids(?)) AND EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_commitments'::regclass
            AND mode = 'AccessExclusiveLock' AND NOT granted) AS ok", [$cancelBackend, $downBackend, $downBackend]);
        fwrite($confirmChannel, "go\n");
        $confirmQueued = r175uWait('SELECT ? = ANY(pg_blocking_pids(?)) AS ok', [$downBackend, $confirmBackend]);
        usleep(600_000);
        fwrite($cancelChannel, "go\n");
        $results = r175uReap([$cancelPid, $downPid, $confirmPid]);
    } finally {
        foreach ([$cancelChannel, $downChannel, $confirmChannel] as $channel) {
            fclose($channel);
        }
        r175uReap([$cancelPid, $downPid, $confirmPid]);
        DB::purge();
        if (r175uTriggers() === 0) {
            $migration->up();
        }
    }
    expect($read)->toBeTrue()->and($downWaitsOnReader)->toBeTrue()->and($confirmQueued)->toBeTrue()
        ->and(['exits' => $results, 'deadlocks' => r175uDeadlocksSince($before)])->toBe(['exits' => [0, 0, 0], 'deadlocks' => 0]);
});

it('U-G a writer queued behind the install sees the new trigger: its mismatched receipt is refused at commit', function (): void {
    $migration = r175uMigration();
    $migration->down();
    DB::disconnect();
    [$migrationPid, $migrationChannel, $migrationBackend] = r175uFork(fn () => $migration->up());
    [$writerPid, $writerChannel, $writerBackend] = r175uFork(function (): void {
        DB::transaction(function (): void {
            $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
            $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
            $operationId = strtolower((string) Str::ulid());
            $commitmentId = strtolower((string) Str::ulid());
            $version = PrimaryReservationVersion::factory()->confirmed()->withCashMovement()->create(['primary_reservation_id' => $root->id, 'operation_id' => $operationId]);
            CommandOperation::factory()->create(['id' => $operationId, 'actor_key' => $origin->actor_key, 'actor_user_id' => $origin->actor_user_id,
                'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id,
                'result' => ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'operation_id' => $operationId, 'revision' => 2,
                    'data' => ['reservation_id' => $root->id, 'commitment_id' => $commitmentId, 'amount' => '999999']]]);
            PrimaryCommitment::factory()->create(['id' => $commitmentId, 'primary_reservation_version_id' => $version->id]); // queues here
        }, 1);
    });
    DB::purge();
    try {
        DB::beginTransaction();
        DB::statement('LOCK TABLE primary_commitments IN ROW EXCLUSIVE MODE'); // an in-flight commitment writer
        fwrite($migrationChannel, "go\n");
        $migrationQueued = r175uWait("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_commitments'::regclass
            AND mode = 'ShareRowExclusiveLock' AND NOT granted) AS ok", [$migrationBackend]);
        fwrite($writerChannel, "go\n");
        $writerQueued = r175uWait('SELECT ? = ANY(pg_blocking_pids(?)) AS ok', [$migrationBackend, $writerBackend]);
        DB::commit();
        $results = r175uReap([$migrationPid, $writerPid]);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($migrationChannel);
        fclose($writerChannel);
        r175uReap([$migrationPid, $writerPid]);
        DB::purge();
        if (r175uTriggers() === 0) {
            $migration->up();
        }
    }
    expect($migrationQueued)->toBeTrue()->and($writerQueued)->toBeTrue()
        ->and(['exits' => $results, 'commitments' => PrimaryCommitment::query()->count(), 'triggers' => r175uTriggers()])
        ->toBe(['exits' => [0, 1], 'commitments' => 0, 'triggers' => 1]);
});

it('U-H latent: down() upgrades SHARE ROW EXCLUSIVE to ACCESS EXCLUSIVE at DROP TRIGGER, so a read-then-insert transaction deadlocks it', function (): void {
    $migration = r175uMigration();
    DB::disconnect();
    [$downPid, $downChannel, $downBackend] = r175uFork(fn () => $migration->down());
    DB::purge();
    $before = r175uDeadlocks();
    $outcome = 'committed';
    try {
        DB::beginTransaction();
        DB::selectOne('SELECT count(*) AS n FROM primary_commitments'); // AccessShare, as a summary read inside confirm would take
        fwrite($downChannel, "go\n");
        $downUpgrading = r175uWait("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_commitments'::regclass AND mode = 'ShareRowExclusiveLock' AND granted)
            AND EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_commitments'::regclass AND mode = 'AccessExclusiveLock' AND NOT granted) AS ok", [$downBackend, $downBackend]);
        try {
            DB::statement('LOCK TABLE primary_commitments IN ROW EXCLUSIVE MODE'); // the lock an INSERT takes
            DB::commit();
        } catch (QueryException $exception) {
            $outcome = $exception->errorInfo[0];
            DB::rollBack();
        }
        $downExit = r175uReap([$downPid])[0];
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($downChannel);
        r175uReap([$downPid]);
        DB::purge();
        if (r175uTriggers() === 0) {
            $migration->up();
        }
    }
    fwrite(STDERR, "U-H: app={$outcome} down_exit={$downExit}\n");
    expect($downUpgrading)->toBeTrue()->and(r175uDeadlocksSince($before))->toBe(1)
        ->and([$outcome, $downExit])->toBeIn([['40P01', 0], ['committed', 2]]);
});
