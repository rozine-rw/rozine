<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table modes reproduce the deposit writers that touch intents: recording an intent, and applying a provider
 * event under the intent row lock. The installer must wait behind them while holding nothing they still write.
 */
it('installs the provider reference registry behind in-flight deposit writers without blocking their remaining writes', function (array $written, array $remaining): void {
    $migration = require database_path('migrations/2026_10_03_110000_create_provider_reference_registry.php');
    $shape = fn (): array => [
        DB::select("SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conrelid IN ('wallet_deposit_intents'::regclass,
            'provider_references'::regclass) ORDER BY conname"),
        DB::select("SELECT tgname, pg_get_triggerdef(oid) AS definition FROM pg_trigger WHERE NOT tgisinternal
            AND tgrelid IN ('wallet_deposit_intents'::regclass, 'provider_references'::regclass) ORDER BY tgname"),
    ];
    $installed = $shape();
    $businessDeposits = require database_path('migrations/2026_10_03_120000_create_business_deposit_records.php');
    $businessRepayments = require database_path('migrations/2026_10_04_100000_create_business_repayment_records.php');
    $restore = function () use ($migration, $businessDeposits, $businessRepayments): void {
        if (! Schema::hasTable('provider_references')) {
            $migration->up();
        }
        $businessDeposits->up();
        $businessRepayments->up();
    };
    $businessRepayments->down();
    $businessDeposits->down();
    $migration->down();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        $restore();
        throw new RuntimeException('Could not create migration barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($channels[0]);
        fclose($channels[1]);
        $restore();
        throw new RuntimeException('Could not fork migration installer.');
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
            $migration->up();
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getMessage()."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    try {
        $installer = (int) trim((string) fgets($channels[0]));
        DB::beginTransaction();
        foreach ($written as $table => $mode) {
            DB::statement('LOCK TABLE '.$table.' IN '.$mode.' MODE');
        }
        fwrite($channels[0], "go\n");
        $blocked = false;
        $deadline = hrtime(true) + 4_000_000_000;
        while (hrtime(true) < $deadline && ! $blocked) {
            $blocked = (bool) DB::scalar('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?))', [$installer]);
            if (! $blocked) {
                usleep(10_000);
            }
        }
        expect($blocked)->toBeTrue('Installer must wait for the in-flight posting.');
        foreach ($remaining as $table) {
            DB::statement('LOCK TABLE '.$table.' IN ROW EXCLUSIVE MODE NOWAIT');
        }
        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
        DB::purge();
        $restore();
    }
    expect(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0)
        ->and(Schema::hasTable('provider_references'))->toBeTrue()
        ->and($shape())->toEqual($installed);
})->with([
    'deposit after intent' => [['investor_wallets' => 'ROW SHARE', 'wallet_deposit_intents' => 'ROW EXCLUSIVE'],
        ['command_operations', 'wallet_deposit_dispatches']],
    'apply under intent lock' => [['investor_wallets' => 'ROW SHARE', 'wallet_deposit_intents' => 'ROW SHARE'],
        ['wallet_provider_events', 'ledger_entries', 'ledger_accounts', 'ledger_lines', 'wallet_deposit_credits']],
]);
