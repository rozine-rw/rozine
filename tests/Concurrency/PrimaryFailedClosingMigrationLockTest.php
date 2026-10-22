<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('installs terminal failure behind caller gates without occupying an in-flight writers remaining tables', function (): void {
    $migration = require database_path('migrations/2026_10_12_054843_retain_primary_terminal_failed_closings.php');
    // The platform fee and automatic disbursement migrations rebind the failed closing authority, so they come off first and go back last.
    $later = [
        require database_path('migrations/2026_10_16_180415_disburse_funded_campaigns_automatically.php'),
        require database_path('migrations/2026_10_16_161905_add_platform_fee_to_disbursements.php'),
    ];
    DB::transaction(function () use ($later, $migration): void {
        foreach ($later as $dependant) {
            $dependant->down();
        }
        $migration->down();
    });
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create terminal migration barrier.');
    }
    foreach ($channels as $channel) {
        stream_set_timeout($channel, 10);
    }
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork terminal migration installer.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        DB::statement("SET lock_timeout = '6s'");
        fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
        if (fgets($channels[1]) !== "go\n") {
            exit(3);
        }
        try {
            $migration->up();
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception->getMessage());
            exit(1);
        }
    }
    fclose($channels[1]);
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        DB::beginTransaction();
        DB::statement('LOCK TABLE business_profiles, business_campaigns IN ROW SHARE MODE');
        DB::statement('LOCK TABLE primary_reservations IN ROW EXCLUSIVE MODE');
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 4_000_000_000;
        $blocked = false;
        while (hrtime(true) < $deadline && ! $blocked) {
            $blocked = (bool) DB::scalar('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?))', [$backend]);
            if (! $blocked) {
                usleep(10_000);
            }
        }
        expect($blocked)->toBeTrue();
        DB::statement('LOCK TABLE primary_campaign_fundings, primary_commitments, investor_wallets, ledger_entries, business_campaign_closures IN ROW EXCLUSIVE MODE NOWAIT');
        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
        DB::purge();
        DB::transaction(function () use ($later, $migration): void {
            if (! Schema::hasTable('primary_failed_closings')) {
                $migration->up();
            }
            foreach (array_reverse($later) as $dependant) {
                $dependant->up();
            }
        });
    }
    expect(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0)
        ->and(Schema::hasTable('primary_failed_closings'))->toBeTrue();
});
