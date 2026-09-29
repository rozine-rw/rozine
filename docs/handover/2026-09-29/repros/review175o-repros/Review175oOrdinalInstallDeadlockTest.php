<?php

declare(strict_types=1);

/*
 * Review probe for PR #175 at 66e284d5 (outside the reviewed delta). Copy into tests/Concurrency/.
 *
 * 66e284d5 removed business_campaigns from the 161335/175455 LOCK TABLE lists because a campaign
 * reader that then locks its Business (EloquentPrimaryCampaignSource::lockBusiness) deadlocks with
 * "LOCK business_profiles, business_campaigns". 2026_09_28_154941 up() still takes exactly that
 * list (it does read business_campaigns in backfill() and in its audit), so the same scenario is
 * replayed against it here. Asserts the SAFE outcome: both sides commit.
 */

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Models\BusinessCampaign;
use Illuminate\Support\Facades\DB;

function r175oWaitFor(string $sql, array $bindings = [], float $seconds = 8.0): bool
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

function r175oFork(Closure $work): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 15);
    stream_set_timeout($channels[1], 15);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $work();
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, 'child '.getmypid().': '.$exception->getMessage()."\n");
            exit(str_contains($exception->getMessage(), '40P01') ? 2 : 1);
        }
    }
    fclose($channels[1]);

    return [$pid, $channels[0], (int) trim((string) fgets($channels[0]))];
}

it('PROBE: installing 154941 does not deadlock with a checkout that read its campaign before locking the Business', function (): void {
    $chain = ['2026_09_28_154941_enforce_primary_ordinal_exclusion', '2026_09_28_161335_bind_primary_reservations_to_wallet_holds',
        '2026_09_28_163057_require_completed_primary_command_outcomes', '2026_09_28_165949_reject_unbound_primary_commitment_sources',
        '2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements'];
    $migrations = array_map(fn (string $name) => require database_path('migrations/'.$name.'.php'), $chain);
    $campaign = BusinessCampaign::factory()->create();
    foreach (array_reverse($migrations) as $migration) {
        $migration->down();
    }
    DB::disconnect();

    [$migrationPid, $migrationChannel, $migrationBackend] = r175oFork(fn () => $migrations[0]->up());
    [$checkoutPid, $checkoutChannel, $checkoutBackend] = r175oFork(function () use ($campaign): void {
        DB::transaction(fn () => app(PrimaryCampaignSource::class)->lockBusiness($campaign->id), 1);
    });
    DB::purge();
    $results = [];
    try {
        DB::beginTransaction();
        DB::table('business_profiles')->where('id', $campaign->business_id)->lockForUpdate()->first();
        fwrite($migrationChannel, "go\n");
        $migrationQueued = r175oWaitFor("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_profiles'::regclass
            AND mode = 'AccessExclusiveLock' AND NOT granted) AS ok", [$migrationBackend]);
        fwrite($checkoutChannel, "go\n");
        $checkoutQueued = r175oWaitFor("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_campaigns'::regclass
            AND mode = 'AccessShareLock' AND granted) AND ? = ANY(pg_blocking_pids(?)) AS ok", [$checkoutBackend, $migrationBackend, $checkoutBackend]);
        usleep(1_500_000);
        DB::commit();
        foreach ([$migrationPid, $checkoutPid] as $pid) {
            pcntl_waitpid($pid, $status);
            $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($migrationChannel);
        fclose($checkoutChannel);
        DB::purge();
        if (! Illuminate\Support\Facades\Schema::hasColumn('primary_reservations', 'ordinal_ranges')) {
            $migrations[0]->up();
        }
        foreach (array_slice($migrations, 1) as $migration) {
            $migration->up();
        }
    }

    expect($migrationQueued)->toBeTrue()->and($checkoutQueued)->toBeTrue()
        ->and($results)->toBe([0, 0]);
});
