<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\CampaignCommitments;
use App\Models\BusinessCampaign;
use App\Models\PrimaryCommitment;
use Illuminate\Support\Facades\DB;

/** @param list<int> $bindings */
function waitForPrimaryMigrationLock(string $sql, array $bindings = [], float $seconds = 8.0): bool
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
function forkPrimaryMigrationContender(Closure $work): array
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

it('installs Primary cash guards without deadlocking a campaign read waiting for its Business', function (string $migration): void {
    $path = database_path('migrations/'.$migration);
    $terminal = require database_path('migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php');
    $terminal->down();
    $campaign = BusinessCampaign::factory()->create();
    if (! str_contains($migration, '175455')) {
        (require $path)->down();
    }
    DB::disconnect();

    [$migrationPid, $migrationChannel, $migrationBackend] = forkPrimaryMigrationContender(fn () => (require $path)->up());
    [$checkoutPid, $checkoutChannel, $checkoutBackend] = forkPrimaryMigrationContender(function () use ($campaign): void {
        DB::transaction(fn () => app(PrimaryCampaignSource::class)->lockBusiness($campaign->id), 1);
    });
    DB::purge();
    $results = [];
    try {
        DB::beginTransaction();
        DB::table('business_profiles')->where('id', $campaign->business_id)->lockForUpdate()->first();
        fwrite($migrationChannel, "go\n");
        $migrationQueued = waitForPrimaryMigrationLock("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_profiles'::regclass
            AND mode = 'AccessExclusiveLock' AND NOT granted) AS ok", [$migrationBackend]);
        fwrite($checkoutChannel, "go\n");
        $checkoutQueued = waitForPrimaryMigrationLock("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_campaigns'::regclass
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
        if ((int) DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname = 'primary_reservation_wallet_bound'")->total === 0) {
            (require database_path('migrations/2026_09_28_161335_bind_primary_reservations_to_wallet_holds.php'))->up();
        }
        if ((int) DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname = 'ledger_primary_terminal_bound'")->total === 0) {
            $terminal->up();
        }
    }

    expect($migrationQueued)->toBeTrue()->and($checkoutQueued)->toBeTrue()
        ->and($results)->toBe([0, 0]);
})->with(['2026_09_28_161335_bind_primary_reservations_to_wallet_holds.php',
    '2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php']);

it('installs confirmation receipt guards while commitment readers pass an in-flight writer', function (): void {
    $migration = require database_path('migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php');
    $migration->down();
    $campaign = BusinessCampaign::factory()->create();
    DB::disconnect();
    [$migrationPid, $migrationChannel, $migrationBackend] = forkPrimaryMigrationContender(fn () => $migration->up());
    [$readerPid, $readerChannel] = forkPrimaryMigrationContender(function () use ($campaign): void {
        if (app(CampaignCommitments::class)->anyForCampaign($campaign->id)) {
            throw new RuntimeException('Unexpected campaign commitment.');
        }
    });
    DB::purge();
    try {
        DB::beginTransaction();
        PrimaryCommitment::factory()->create();
        fwrite($migrationChannel, "go\n");
        $queued = waitForPrimaryMigrationLock("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'primary_commitments'::regclass
            AND mode = 'ShareRowExclusiveLock' AND NOT granted) AS ok", [$migrationBackend], 1.0);
        fwrite($readerChannel, "go\n");
        pcntl_waitpid($readerPid, $readerStatus);
        DB::commit();
        pcntl_waitpid($migrationPid, $migrationStatus);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($migrationChannel);
        fclose($readerChannel);
        pcntl_waitpid($readerPid, $status);
        pcntl_waitpid($migrationPid, $status);
        DB::purge();
        if ((int) DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname = 'primary_commitment_receipt_bound'")->total === 0) {
            $migration->up();
        }
    }
    expect($queued)->toBeTrue()->and(pcntl_wifexited($readerStatus))->toBeTrue()
        ->and(pcntl_wexitstatus($readerStatus))->toBe(0)->and(pcntl_wifexited($migrationStatus))->toBeTrue()
        ->and(pcntl_wexitstatus($migrationStatus))->toBe(0);
});
