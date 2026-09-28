<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Models\BusinessCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** @param list<int> $bindings */
function waitForPrimaryOrdinalMigrationLock(string $sql, array $bindings = [], float $seconds = 8.0): bool
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

/** @return array{int, resource, int} */
function forkPrimaryOrdinalMigrationContender(Closure $work): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create the Primary migration barrier.');
    }
    stream_set_timeout($channels[0], 15);
    stream_set_timeout($channels[1], 15);
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork the Primary migration contender.');
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
            $work();
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, 'Primary migration contender: '.$exception::class.' '.$exception->getCode()."\n");
            exit(str_contains($exception->getMessage(), '40P01') ? 2 : 1);
        }
    }
    fclose($channels[1]);

    return [$pid, $channels[0], (int) trim((string) fgets($channels[0]))];
}

it('installs ordinal guards without deadlocking campaign readers or writers waiting for their Business', function (bool $writer): void {
    $chain = ['2026_09_28_154941_enforce_primary_ordinal_exclusion', '2026_09_28_161335_bind_primary_reservations_to_wallet_holds',
        '2026_09_28_163057_require_completed_primary_command_outcomes', '2026_09_28_165949_reject_unbound_primary_commitment_sources',
        '2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements', '2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments',
        '2026_09_28_212446_bind_primary_confirmation_operations_to_purchases'];
    $migrations = array_map(fn (string $name) => require database_path('migrations/'.$name.'.php'), $chain);
    $campaign = BusinessCampaign::factory()->create();
    foreach (array_reverse($migrations) as $migration) {
        $migration->down();
    }
    DB::disconnect();

    [$migrationPid, $migrationChannel, $migrationBackend] = forkPrimaryOrdinalMigrationContender(fn () => $migrations[0]->up());
    [$checkoutPid, $checkoutChannel, $checkoutBackend] = forkPrimaryOrdinalMigrationContender(function () use ($campaign, $writer): void {
        DB::transaction(function () use ($campaign, $writer): void {
            if ($writer) {
                DB::statement('LOCK TABLE business_campaigns IN ROW EXCLUSIVE MODE');
            }
            app(PrimaryCampaignSource::class)->lockBusiness($campaign->id);
        }, 1);
    });
    DB::purge();
    $results = [];
    try {
        DB::beginTransaction();
        DB::table('business_profiles')->where('id', $campaign->business_id)->lockForUpdate()->first();
        fwrite($migrationChannel, "go\n");
        $migrationQueued = waitForPrimaryOrdinalMigrationLock("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_profiles'::regclass
            AND mode = 'AccessExclusiveLock' AND NOT granted) AS ok", [$migrationBackend]);
        fwrite($checkoutChannel, "go\n");
        $checkoutQueued = waitForPrimaryOrdinalMigrationLock("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_campaigns'::regclass
            AND mode = 'AccessShareLock' AND granted) AND ? = ANY(pg_blocking_pids(?)) AS ok", [$checkoutBackend, $migrationBackend, $checkoutBackend]);
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
        pcntl_waitpid($migrationPid, $status);
        pcntl_waitpid($checkoutPid, $status);
        DB::purge();
        if (! Schema::hasColumn('primary_reservations', 'ordinal_ranges')) {
            $migrations[0]->up();
        }
        foreach (array_slice($migrations, 1) as $migration) {
            $migration->up();
        }
    }

    expect($migrationQueued)->toBeTrue()->and($checkoutQueued)->toBeTrue()
        ->and($results)->toBe([0, 0]);
})->with(['reader' => false, 'writer table admission' => true]);
