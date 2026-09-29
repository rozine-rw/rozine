<?php

declare(strict_types=1);

/*
 * Review probes for PR #175 at f1e99b16 (2026_09_28_212446 install lock). Copy into tests/Concurrency/.
 * Each probe asserts the SAFE outcome: every contender commits (exit 0) and no 40P01 is recorded.
 * Exit 2 = deadlock victim, 1 = other error, 3 = barrier failure.
 *
 * A (three-way): T1 is a real confirm of an EXPIRED hold. Its journal row is written first (RowExclusive on
 *   command_operations) and only then expireRejected() locks the Party wallet. T2 is a real reserve by the same
 *   Party in another Business, paused holding that wallet row before its own journal insert. M is the install:
 *   M waits for T1 (hard), T1 waits for T2's wallet row (hard), T2's journal INSERT queues behind M (soft).
 * B: a real confirm paused inside admission (holding Business, campaign, reservation and wallet-free state plus an
 *   AccessShare journal lookup) must not block the install; after the install commits the confirm's own
 *   RESERVATION_CONFIRMED receipt is checked by the new trigger and commits.
 */

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\CommandOperation;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

function r175vWait(string $sql, array $bindings = [], float $seconds = 8.0): bool
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

function r175vDeadlocks(): int
{
    DB::statement('SELECT pg_stat_clear_snapshot()');

    return (int) DB::selectOne('SELECT deadlocks FROM pg_stat_database WHERE datname = current_database()')->deadlocks;
}

function r175vDeadlocksSince(int $before): int
{
    $deadline = microtime(true) + 3.0;
    do {
        $delta = r175vDeadlocks() - $before;
        if ($delta > 0) {
            return $delta;
        }
        usleep(100_000);
    } while (microtime(true) < $deadline);

    return 0;
}

/** @return array{int, resource, int} */
function r175vFork(Closure $work): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 25);
    stream_set_timeout($channels[1], 25);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '15s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $work($channels[1]);
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, 'r175v child '.getmypid().': '.$exception::class.' '.mb_substr($exception->getMessage(), 0, 400)."\n");
            exit(str_contains($exception->getMessage(), '40P01') ? 2 : 1);
        }
    }
    fclose($channels[1]);

    return [$pid, $channels[0], (int) trim((string) fgets($channels[0]))];
}

function r175vPause($channel, string $label): void
{
    fwrite($channel, $label."\n");
    if (fgets($channel) !== "go\n") {
        throw new RuntimeException('barrier lost at '.$label);
    }
}

/** @param list<int> $pids @return list<int> */
function r175vReap(array $pids): array
{
    $results = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
    }

    return $results;
}

function r175vMigration(): object
{
    return require database_path('migrations/2026_09_28_212446_bind_primary_confirmation_operations_to_purchases.php');
}

function r175vRestore(): void
{
    DB::purge();
    if ((int) DB::selectOne("SELECT count(*) AS n FROM pg_trigger WHERE tgname = 'primary_confirmation_operation_bound'")->n === 0) {
        r175vMigration()->up();
    }
}

it('PROBE A: install vs an expiring confirm (journal then wallet) and a wallet-then-journal writer', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $first = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $expiringId = $checkout->reserve($investor['user']->id, 1, $first->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['data']['reservation_id'];
    $expiring = PrimaryReservationRecord::query()->findOrFail($expiringId);
    $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $expiringId)->sole();
    $this->travelTo($expiring->expires_at);
    $second = PrimaryReservationFixture::campaign();
    expect($second->business_id)->not->toBe($first->business_id);
    r175vMigration()->down();
    DB::disconnect();

    [$expiryPid, $expiryChannel, $expiryBackend] = r175vFork(function ($channel) use ($checkout, $investor, $first, $expiringId, $held): void {
        $paused = false;
        DB::listen(function (QueryExecuted $query) use ($channel, &$paused): void {
            if (! $paused && str_starts_with($query->sql, 'insert into "command_operations"')) {
                $paused = true;
                r175vPause($channel, 'journaled');
            }
        });
        $result = $checkout->confirm($investor['user']->id, 1, $first->id, $expiringId, 1, $held->payload['terms']['disclosure_version'],
            $held->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        if ($result['code'] !== 'RESERVATION_EXPIRED') {
            throw new RuntimeException('confirm '.$result['code']);
        }
    });
    /*
     * A real same-Party reserve cannot be T2: every Investor command locks the users row first (observed: the reserve
     * waits on "users ... for update" held by T1), so it serialises behind T1 before touching the wallet. T2 is therefore
     * a synthetic writer with the same shape as any command: row lock on the Party wallet, then a journal INSERT.
     */
    [$reservePid, $reserveChannel, $reserveBackend] = r175vFork(function ($channel) use ($investor): void {
        DB::transaction(function () use ($channel, $investor): void {
            DB::selectOne('SELECT id FROM investor_wallets WHERE party_id = ? FOR UPDATE', [$investor['party']->id]);
            r175vPause($channel, 'walleted');
            CommandOperation::factory()->create();
        });
    });
    [$migrationPid, $migrationChannel, $migrationBackend] = r175vFork(fn () => r175vMigration()->up());
    DB::purge();
    $deadlocksBefore = r175vDeadlocks();
    $started = microtime(true);
    try {
        fwrite($expiryChannel, "go\n");
        $journaled = trim((string) fgets($expiryChannel)) === 'journaled';
        fwrite($reserveChannel, "go\n");
        $walleted = trim((string) fgets($reserveChannel)) === 'walleted';
        fwrite($migrationChannel, "go\n");
        $migrationQueued = r175vWait('SELECT ? = ANY(pg_blocking_pids(?)) AS ok', [$expiryBackend, $migrationBackend]);
        fwrite($reserveChannel, "go\n");
        $reserveQueued = r175vWait("SELECT ? = ANY(pg_blocking_pids(?)) AND EXISTS (SELECT 1 FROM pg_locks WHERE pid = ?
            AND relation = 'command_operations'::regclass AND mode = 'RowExclusiveLock' AND NOT granted) AS ok",
            [$migrationBackend, $reserveBackend, $reserveBackend]);
        fwrite($expiryChannel, "go\n");
        $expiryWaitsForReserve = r175vWait('SELECT ? = ANY(pg_blocking_pids(?)) AS ok', [$reserveBackend, $expiryBackend], 0.9);
        $results = r175vReap([$expiryPid, $reservePid, $migrationPid]);
    } finally {
        foreach ([$expiryChannel, $reserveChannel, $migrationChannel] as $channel) {
            fclose($channel);
        }
        r175vReap([$expiryPid, $reservePid, $migrationPid]);
        r175vRestore();
    }
    $elapsed = microtime(true) - $started;
    $deadlocks = r175vDeadlocksSince($deadlocksBefore);
    fwrite(STDERR, sprintf("PROBE A: exits=%s deadlocks=%d elapsed=%.2fs expiryWaitsForReserve=%s\n", json_encode($results), $deadlocks, $elapsed, var_export($expiryWaitsForReserve, true)));
    expect($journaled)->toBeTrue()->and($walleted)->toBeTrue()->and($migrationQueued)->toBeTrue()->and($reserveQueued)->toBeTrue()
        ->and(['exits' => $results, 'deadlocks' => $deadlocks])->toBe(['exits' => [0, 0, 0], 'deadlocks' => 0])
        ->and(PrimaryReservationVersion::query()->where('state', 'expired')->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'fixture.save')->count())->toBe(1);
});

it('PROBE B: install does not wait for a confirm paused in admission, and the confirm\'s receipt is then checked and commits', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $reservationId = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['data']['reservation_id'];
    $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $reservationId)->sole();
    $this->travel(2)->minutes();
    $other = PrimaryReservationFixture::campaign();
    $stranger = PrimaryReservationFixture::investor();
    r175vMigration()->down();
    DB::disconnect();

    [$confirmPid, $confirmChannel, $confirmBackend] = r175vFork(function ($channel) use ($checkout, $investor, $campaign, $reservationId, $held): void {
        $result = $checkout->confirm($investor['user']->id, 1, $campaign->id, $reservationId, 1, $held->payload['terms']['disclosure_version'],
            $held->payload['disclosure_sha256'], (string) Str::uuid(), function ($rights, array $input) use ($channel) {
                r175vPause($channel, 'admitted');

                return PrimaryReservationFixture::terms($rights, $input);
            });
        if ($result['code'] !== 'RESERVATION_CONFIRMED') {
            throw new RuntimeException('confirm '.$result['code']);
        }
    });
    [$migrationPid, $migrationChannel] = r175vFork(fn () => r175vMigration()->up());
    [$reservePid, $reserveChannel] = r175vFork(function () use ($checkout, $stranger, $other): void {
        if ($checkout->reserve($stranger['user']->id, 1, $other->id, '2', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'] !== 'RESERVATION_HELD') {
            throw new RuntimeException('reserve');
        }
    });
    DB::purge();
    $deadlocksBefore = r175vDeadlocks();
    try {
        fwrite($confirmChannel, "go\n");
        $admitted = trim((string) fgets($confirmChannel)) === 'admitted';
        $confirmHoldsRows = (bool) DB::selectOne("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_reservations'::regclass
            AND mode = 'RowShareLock' AND granted) AS ok", [$confirmBackend])->ok;
        fwrite($migrationChannel, "go\n");
        [$migrationExit] = r175vReap([$migrationPid]);
        $installedWhilePaused = (int) DB::selectOne("SELECT count(*) AS n FROM pg_trigger WHERE tgname = 'primary_confirmation_operation_bound'")->n === 1;
        fwrite($reserveChannel, "go\n");
        [$reserveExit] = r175vReap([$reservePid]);
        fwrite($confirmChannel, "go\n");
        [$confirmExit] = r175vReap([$confirmPid]);
    } finally {
        foreach ([$confirmChannel, $migrationChannel, $reserveChannel] as $channel) {
            fclose($channel);
        }
        r175vReap([$confirmPid, $migrationPid, $reservePid]);
        r175vRestore();
    }
    expect($admitted)->toBeTrue()->and($confirmHoldsRows)->toBeTrue()->and($installedWhilePaused)->toBeTrue()
        ->and(['migration' => $migrationExit, 'reserve' => $reserveExit, 'confirm' => $confirmExit, 'deadlocks' => r175vDeadlocksSince($deadlocksBefore)])
        ->toBe(['migration' => 0, 'reserve' => 0, 'confirm' => 0, 'deadlocks' => 0])
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->sole()->result['code'])->toBe('RESERVATION_CONFIRMED');
});

/*
 * C: the up-front LOCK is what makes the audit cover a receipt committed while the install runs. A writer holds an
 * uncommitted orphan RESERVATION_CONFIRMED receipt (no purchase) when the install starts. With the lock the install
 * waits, then audits the committed orphan and aborts. Without it (mutation W24) the audit misses the row, CREATE
 * TRIGGER waits for the writer, and the install commits over unaudited history. The PR's lock test cannot tell
 * these apart because CREATE TRIGGER also queues for SHARE ROW EXCLUSIVE.
 */
it('PROBE C: a receipt committed while the install waits is audited, so the install refuses an orphan', function (): void {
    $this->freezeSecond();
    $root = DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create());
    $origin = CommandOperation::query()->whereKey($root->origin_operation_id)->sole();
    r175vMigration()->down();
    DB::disconnect();
    [$writerPid, $writerChannel, $writerBackend] = r175vFork(function ($channel) use ($root, $origin): void {
        DB::transaction(function () use ($channel, $root, $origin): void {
            $id = strtolower((string) Str::ulid());
            CommandOperation::factory()->create(['id' => $id, 'actor_key' => $origin->actor_key, 'actor_user_id' => $origin->actor_user_id,
                'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id,
                'result' => ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'operation_id' => $id, 'revision' => 2,
                    'data' => ['reservation_id' => $root->id, 'commitment_id' => strtolower((string) Str::ulid()), 'amount' => $root->principal]]]);
            r175vPause($channel, 'written');
        });
    });
    [$migrationPid, $migrationChannel, $migrationBackend] = r175vFork(fn () => r175vMigration()->up());
    DB::purge();
    try {
        fwrite($writerChannel, "go\n");
        $written = trim((string) fgets($writerChannel)) === 'written';
        fwrite($migrationChannel, "go\n");
        $migrationWaits = r175vWait('SELECT ? = ANY(pg_blocking_pids(?)) AS ok', [$writerBackend, $migrationBackend]);
        usleep(300_000);
        fwrite($writerChannel, "go\n");
        [$writerExit, $migrationExit] = r175vReap([$writerPid, $migrationPid]);
    } finally {
        fclose($writerChannel);
        fclose($migrationChannel);
        r175vReap([$writerPid, $migrationPid]);
        DB::purge();
    }
    $installed = (int) DB::selectOne("SELECT count(*) AS n FROM pg_trigger WHERE tgname = 'primary_confirmation_operation_bound'")->n;
    expect($written)->toBeTrue()->and($migrationWaits)->toBeTrue()
        ->and(['writer' => $writerExit, 'migration' => $migrationExit, 'installed' => $installed])->toBe(['writer' => 0, 'migration' => 1, 'installed' => 0]);
    // DatabaseTruncation removes the committed orphan afterwards; reinstall for the next test.
    DB::table('command_operations')->where('command', 'primary.confirm')->count() === 1
        ? DB::unprepared('ALTER TABLE command_operations DISABLE TRIGGER command_operations_immutable; DELETE FROM command_operations WHERE command = \'primary.confirm\'; ALTER TABLE command_operations ENABLE TRIGGER command_operations_immutable;')
        : null;
    r175vRestore();
});
