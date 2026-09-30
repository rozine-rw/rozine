<?php

declare(strict_types=1);

/*
 * Review #175 range 8d500fc7..d701d879. Real forks; waits are proven with pg_blocking_pids, never sleeps.
 * Child exit: 0 = returned normally, 5 = rethrew the injected failure, 1 = other error, 3 = barrier lost.
 */

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/** @return array{int, resource, int} */
function r175yFork(Closure $work): array
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
            $work();
            exit(0);
        } catch (Throwable $exception) {
            if ($exception->getMessage() === 'Injected corrupt hold.') {
                exit(5);
            }
            fwrite(STDERR, 'r175y child: '.$exception::class.' '.mb_substr($exception->getMessage(), 0, 300)."\n");
            exit(1);
        }
    }
    fclose($channels[1]);

    return [$pid, $channels[0], (int) trim((string) fgets($channels[0]))];
}

/** @param int|list<int> $blockers */
function r175yBlockedBy(int $backend, int|array $blockers, float $seconds = 10.0): bool
{
    $deadline = microtime(true) + $seconds;
    $list = '{'.implode(',', (array) $blockers).'}';
    while (microtime(true) < $deadline) {
        if (DB::connection('r175y_probe')->selectOne('SELECT pg_blocking_pids(?) && ?::int[] AS ok', [$backend, $list])->ok) {
            return true;
        }
        usleep(10_000);
    }

    return false;
}

function r175yReap(int $pid): int
{
    pcntl_waitpid($pid, $status);

    return pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
}

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    config(['database.connections.r175y_holder' => config('database.connections.pgsql'), 'database.connections.r175y_probe' => config('database.connections.pgsql')]);
    $this->investor = PrimaryReservationFixture::investor();
    $this->holdOn = function ($campaign): PrimaryReservationRecord {
        $result = app(PrimaryCheckout::class)->reserve($this->investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->state = fn (PrimaryReservationRecord $root): string => PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->value('state');
});

afterEach(function (): void {
    DB::purge('r175y_holder');
    DB::purge('r175y_probe');
});

it('PROBE two concurrent sweeps with one failing and one healthy root: one release, one failure row, both rethrow', function (): void {
    $brokenCampaign = PrimaryReservationFixture::campaign();
    $broken = ($this->holdOn)($brokenCampaign);
    $this->travel(1)->seconds();
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $healthyCampaign = PrimaryReservationFixture::campaign();
    $healthy = ($this->holdOn)($healthyCampaign);
    $this->travelTo($healthy->expires_at);
    DB::listen(function (QueryExecuted $query) use ($broken): void {
        if (str_starts_with($query->sql, 'insert into "primary_reservation_versions"') && in_array($broken->id, $query->bindings, true)) {
            throw new RuntimeException('Injected corrupt hold.');
        }
    });
    DB::disconnect();
    $holder = DB::connection('r175y_holder');
    $holder->beginTransaction();
    $holderPid = $holder->selectOne('SELECT pg_backend_pid() AS pid')->pid;
    $holder->select('SELECT id FROM business_profiles WHERE id = ? FOR UPDATE', [$healthyCampaign->business_id]);
    $children = [r175yFork(fn () => app(PrimaryReservations::class)->expireDue(10)), r175yFork(fn () => app(PrimaryReservations::class)->expireDue(10))];
    foreach ($children as [, $channel]) {
        fwrite($channel, "go\n");
    }
    $blocked = array_map(fn (array $child): bool => r175yBlockedBy($child[2], [$holderPid, ...array_diff(array_column($children, 2), [$child[2]])]), $children);
    $waiting = array_map(fn (array $child): string => (string) DB::connection('r175y_probe')->selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$child[2]])->query, $children);
    $rowsWhileBlocked = (int) DB::connection('r175y_probe')->selectOne('SELECT count(*) AS n FROM primary_expiry_failures')->n;
    $holder->rollBack();
    $exits = array_map(fn (array $child): int => r175yReap($child[0]), $children);
    fwrite(STDERR, 'both blocked='.json_encode($blocked).' waiting='.json_encode(array_map(fn ($q) => mb_substr($q, 0, 60), $waiting))
        .' failure rows while blocked='.$rowsWhileBlocked.' exits='.json_encode($exits)."\n");
    expect($blocked)->toBe([true, true])->and($exits)->toBe([5, 5])
        ->and(($this->state)($healthy))->toBe('expired')->and(($this->state)($broken))->toBe('held')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->sole()->source_id)->toBe($healthy->id)
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $broken->id)->count())->toBe(1)
        ->and(DB::table('primary_expiry_failures')->pluck('primary_reservation_id')->all())->toBe([$broken->id]);
});

it('PROBE the failure bookkeeping waits on a root row lock held elsewhere without deadlocking', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $root = ($this->holdOn)($campaign);
    $this->travelTo($root->expires_at);
    DB::listen(function (QueryExecuted $query) use ($campaign): void {
        if (str_starts_with($query->sql, 'select * from "business_campaigns"') && in_array($campaign->id, $query->bindings, true) && str_contains($query->sql, 'for update')) {
            throw new RuntimeException('Injected corrupt hold.');
        }
    });
    DB::disconnect();
    $holder = DB::connection('r175y_holder');
    $holder->beginTransaction();
    $holderPid = $holder->selectOne('SELECT pg_backend_pid() AS pid')->pid;
    $holder->select('SELECT id FROM primary_reservations WHERE id = ? FOR UPDATE', [$root->id]);
    [$pid, $channel, $backend] = r175yFork(fn () => app(PrimaryReservations::class)->expireDue(10));
    fwrite($channel, "go\n");
    $blocked = r175yBlockedBy($backend, $holderPid);
    $waiting = (string) DB::connection('r175y_probe')->selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$backend])->query;
    $locks = DB::connection('r175y_probe')->select('SELECT locktype, relation::regclass::text AS rel, mode, granted FROM pg_locks WHERE pid = ? AND NOT granted', [$backend]);
    $holder->commit();
    $exit = r175yReap($pid);
    fwrite(STDERR, 'blocked='.json_encode($blocked).' waiting='.json_encode(mb_substr($waiting, 0, 70)).' ungranted='.json_encode(array_map(fn ($l) => $l->locktype.':'.$l->rel.':'.$l->mode, $locks)).' exit='.$exit."\n");
    expect($blocked)->toBeTrue()->and($waiting)->toContain('primary_expiry_failures')->and($exit)->toBe(5)
        ->and(DB::table('primary_expiry_failures')->count())->toBe(1)->and(($this->state)($root))->toBe('held');
});

it('PROBE installing the failure table waits for an in-flight checkout and queues new ones, with no deadlock', function (): void {
    $migration = require database_path('migrations/2026_09_29_112938_create_primary_expiry_failures_table.php');
    $migration->down();
    DB::disconnect();
    $before = (int) DB::connection('r175y_probe')->selectOne('SELECT deadlocks FROM pg_stat_database WHERE datname = current_database()')->deadlocks;
    $holder = DB::connection('r175y_holder');
    $holder->beginTransaction();
    $holderPid = $holder->selectOne('SELECT pg_backend_pid() AS pid')->pid;
    $holder->statement('LOCK TABLE primary_reservations IN ROW EXCLUSIVE MODE');
    [$pid, $channel, $backend] = r175yFork(fn () => DB::transaction(fn () => $migration->up()));
    fwrite($channel, "go\n");
    $installBlocked = r175yBlockedBy($backend, $holderPid);
    $probe = DB::connection('r175y_probe');
    $probe->beginTransaction();
    $probe->statement("SET LOCAL lock_timeout = '300ms'");
    try {
        $probe->statement('LOCK TABLE primary_reservations IN ROW EXCLUSIVE MODE');
        $newCheckout = 'admitted';
    } catch (Throwable $exception) {
        $newCheckout = 'queued behind install ('.mb_substr($exception->getMessage(), 0, 30).')';
    }
    $probe->rollBack();
    $holder->commit();
    $exit = r175yReap($pid);
    DB::statement('SELECT pg_stat_clear_snapshot()');
    $deadlocks = (int) DB::selectOne('SELECT deadlocks FROM pg_stat_database WHERE datname = current_database()')->deadlocks - $before;
    fwrite(STDERR, 'install blocked by in-flight checkout='.json_encode($installBlocked).' new checkout while install waits: '.$newCheckout.' install exit='.$exit.' new deadlocks='.$deadlocks."\n");
    expect($installBlocked)->toBeTrue()->and($exit)->toBe(0)->and($deadlocks)->toBe(0)->and(Schema::hasTable('primary_expiry_failures'))->toBeTrue();
});
