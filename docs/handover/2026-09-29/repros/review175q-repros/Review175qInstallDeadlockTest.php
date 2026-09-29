<?php

declare(strict_types=1);

/*
 * Review probes for PR #175 at ea00d9a3. Copy into tests/Concurrency/. Each probe asserts the SAFE outcome:
 * every contender commits (exit 0). Exit 2 = 40P01 deadlock victim, 1 = other error, 3 = barrier failure.
 *
 * A: installing 195022 while a real checkout confirm is in flight (holding primary_reservations) and a real
 *    campaign cancel for ANOTHER Business reaches EloquentCampaignCommitments::anyForCampaign, whose single
 *    statement locks primary_commitments before primary_reservations (the reverse of 195022's LOCK list).
 * B/C: installing 154941 (ACCESS SHARE on business_campaigns) while a real publish / a real cancel is paused
 *    mid-transaction right after its campaign write/lock.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\BusinessCampaign;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

function r175qWait(string $sql, array $bindings = [], float $seconds = 8.0): bool
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

function r175qDeadlocks(): int
{
    DB::statement('SELECT pg_stat_clear_snapshot()');

    return (int) DB::selectOne('SELECT deadlocks FROM pg_stat_database WHERE datname = current_database()')->deadlocks;
}

/** Deadlocks retried away by DB::transaction(..., 3) still count here; the stats collector reports asynchronously. */
function r175qDeadlocksSince(int $before): int
{
    $deadline = microtime(true) + 3.0;
    do {
        $delta = r175qDeadlocks() - $before;
        if ($delta > 0) {
            return $delta;
        }
        usleep(100_000);
    } while (microtime(true) < $deadline);

    return 0;
}

/** @return array{int, resource, int} child pid, parent end of the channel, backend pid */
function r175qFork(Closure $work): array
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
            fwrite(STDERR, 'r175q child '.getmypid().': '.$exception::class.' '.mb_substr($exception->getMessage(), 0, 300)."\n");
            exit(str_contains($exception->getMessage(), '40P01') ? 2 : 1);
        }
    }
    fclose($channels[1]);

    return [$pid, $channels[0], (int) trim((string) fgets($channels[0]))];
}

/** Child side: announce a pause point and wait for the parent's release. */
function r175qPause($channel, string $label): void
{
    fwrite($channel, $label."\n");
    if (fgets($channel) !== "go\n") {
        throw new RuntimeException('barrier lost at '.$label);
    }
}

/** @param list<int> $pids @return list<int> */
function r175qReap(array $pids): array
{
    $results = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
    }

    return $results;
}

function r175qReleased(): array
{
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    app(BusinessCampaignStore::class)->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Verified release.', (string) Str::uuid());

    return $fixture;
}

it('PROBE A: installing 195022 does not deadlock an in-flight checkout confirm and a cancel of another Business', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $this->travel(2)->minutes();
    $other = PrimaryReservationFixture::campaign();
    expect($other->business_id)->not->toBe($campaign->business_id);
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $reservationId = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['data']['reservation_id'];
    $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $reservationId)->sole();
    $migration = require database_path('migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php');
    $migration->down();
    $signatory = User::query()->findOrFail($other->actor_user_id);
    DB::disconnect();

    [$checkoutPid, $checkoutChannel, $checkoutBackend] = r175qFork(function ($channel) use ($checkout, $investor, $campaign, $reservationId, $held): void {
        $result = $checkout->confirm($investor['user']->id, 1, $campaign->id, $reservationId, 1, $held->payload['terms']['disclosure_version'],
            $held->payload['disclosure_sha256'], (string) Str::uuid(), function ($rights, array $input) use ($channel) {
                r175qPause($channel, 'admitted');

                return PrimaryReservationFixture::terms($rights, $input);
            });
        if ($result['code'] !== 'RESERVATION_CONFIRMED') {
            throw new RuntimeException('confirm '.$result['code']);
        }
    });
    [$migrationPid, $migrationChannel, $migrationBackend] = r175qFork(fn () => $migration->up());
    [$cancelPid, $cancelChannel, $cancelBackend] = r175qFork(function () use ($signatory, $other): void {
        $result = app(BusinessCampaignStore::class)->cancel($signatory->id, 1, $other->business_id, $other->id, 1, 'Probe.', (string) Str::uuid());
        if ($result['code'] !== 'CAMPAIGN_CANCELLED') {
            throw new RuntimeException('cancel '.$result['code']);
        }
    });
    DB::purge();
    $deadlocksBefore = r175qDeadlocks();
    try {
        fwrite($checkoutChannel, "go\n");
        $admitted = trim((string) fgets($checkoutChannel)) === 'admitted';
        fwrite($migrationChannel, "go\n");
        $migrationQueued = r175qWait("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_reservations'::regclass
            AND mode = 'AccessExclusiveLock' AND NOT granted) AS ok", [$migrationBackend], getenv('R175Q_LOOSE') === '1' ? 1.0 : 8.0);
        fwrite($cancelChannel, "go\n");
        $cancelQueued = r175qWait("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_commitments'::regclass
            AND mode = 'AccessShareLock' AND granted) AND ? = ANY(pg_blocking_pids(?)) AS ok", [$cancelBackend, $migrationBackend, $cancelBackend], getenv('R175Q_LOOSE') === '1' ? 1.0 : 8.0);
        usleep((int) (getenv('R175Q_HOLD_MS') ?: 600) * 1000); // the checkout is still thinking; the cancel's one deadlock check (deadlock_timeout) passes first
        fwrite($checkoutChannel, "go\n");
        $results = r175qReap([$checkoutPid, $migrationPid, $cancelPid]);
    } finally {
        foreach ([$checkoutChannel, $migrationChannel, $cancelChannel] as $channel) {
            fclose($channel);
        }
        r175qReap([$checkoutPid, $migrationPid, $cancelPid]);
        DB::purge();
        if (DB::selectOne("SELECT count(*) AS n FROM pg_trigger WHERE tgname = 'primary_commitment_receipt_bound'")->n === 0) {
            $migration->up();
        }
    }

    $deadlocks = r175qDeadlocksSince($deadlocksBefore);
    // R175Q_LOOSE=1 validates a candidate fix whose lock list no longer queues on primary_reservations.
    $loose = getenv('R175Q_LOOSE') === '1';
    expect($admitted)->toBeTrue()->and($loose || $migrationQueued)->toBeTrue()->and($loose || $cancelQueued)->toBeTrue()
        ->and(['exits' => $results, 'deadlocks' => $deadlocks])->toBe(['exits' => [0, 0, 0], 'deadlocks' => 0]);
});

it('PROBE B/C: installing 154941 does not deadlock a real publish or cancel paused after its campaign write', function (string $path): void {
    $this->freezeSecond();
    $chain = ['2026_09_28_154941_enforce_primary_ordinal_exclusion', '2026_09_28_161335_bind_primary_reservations_to_wallet_holds',
        '2026_09_28_163057_require_completed_primary_command_outcomes', '2026_09_28_165949_reject_unbound_primary_commitment_sources',
        '2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements', '2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments'];
    $migrations = array_map(fn (string $name) => require database_path('migrations/'.$name.'.php'), $chain);
    $fixture = r175qReleased();
    $this->travel(2)->minutes();
    $existing = $path === 'cancel' ? PrimaryReservationFixture::campaign() : null;
    foreach (array_reverse($migrations) as $migration) {
        $migration->down();
    }
    DB::disconnect();

    $pause = $path === 'publish' ? 'insert into "business_campaigns"' : 'insert into "business_campaign_closures"';
    [$writerPid, $writerChannel, $writerBackend] = r175qFork(function ($channel) use ($fixture, $existing, $pause, $path): void {
        DB::listen(function (QueryExecuted $query) use ($channel, $pause): void {
            if (str_starts_with($query->sql, $pause)) {
                r175qPause($channel, 'written');
            }
        });
        $store = app(BusinessCampaignStore::class);
        $result = $path === 'publish'
            ? $store->publish($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['application']->id,
                $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid())
            : $store->cancel($existing->actor_user_id, 1, $existing->business_id, $existing->id, 1, 'Probe.', (string) Str::uuid());
        if (! in_array($result['code'], ['LISTING_PUBLISHED', 'CAMPAIGN_CANCELLED'], true)) {
            throw new RuntimeException($path.' '.$result['code']);
        }
    });
    [$migrationPid, $migrationChannel, $migrationBackend] = r175qFork(fn () => $migrations[0]->up());
    DB::purge();
    $deadlocksBefore = r175qDeadlocks();
    try {
        fwrite($writerChannel, "go\n");
        $written = trim((string) fgets($writerChannel)) === 'written';
        $writerHolds = (bool) DB::selectOne("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_campaigns'::regclass
            AND mode IN ('RowExclusiveLock', 'RowShareLock') AND granted) AS ok", [$writerBackend])->ok;
        fwrite($migrationChannel, "go\n");
        $migrationQueued = r175qWait("SELECT ? = ANY(pg_blocking_pids(?)) AS ok", [$writerBackend, $migrationBackend]);
        usleep(500_000);
        fwrite($writerChannel, "go\n");
        $results = r175qReap([$writerPid, $migrationPid]);
    } finally {
        fclose($writerChannel);
        fclose($migrationChannel);
        r175qReap([$writerPid, $migrationPid]);
        DB::purge();
        if (! Schema::hasColumn('primary_reservations', 'ordinal_ranges')) {
            $migrations[0]->up();
        }
        foreach (array_slice($migrations, 1) as $migration) {
            $migration->up();
        }
    }

    $deadlocks = r175qDeadlocksSince($deadlocksBefore);
    expect($written)->toBeTrue()->and($writerHolds)->toBeTrue()->and($migrationQueued)->toBeTrue()
        ->and(['exits' => $results, 'deadlocks' => $deadlocks])->toBe(['exits' => [0, 0], 'deadlocks' => 0])
        ->and(BusinessCampaign::query()->count())->toBe($path === 'publish' ? 1 : 1);
})->with(['publish', 'cancel']);
