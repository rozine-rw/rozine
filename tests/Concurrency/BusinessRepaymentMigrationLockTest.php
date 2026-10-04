<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table modes reproduce each prefix of an in-flight Investor posting after its wallet lock: entry, account, line.
 * The installer must wait behind it while holding nothing the posting still writes.
 */
it('installs Business repayment records behind an in-flight Investor posting without blocking its remaining writes', function (array $written, array $remaining): void {
    $migration = require database_path('migrations/2026_10_04_100000_create_business_repayment_records.php');
    $shape = fn (): array => [
        DB::select("SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conrelid IN ('ledger_entries'::regclass,
            'ledger_accounts'::regclass) ORDER BY conname"),
        DB::select("SELECT tgname, pg_get_triggerdef(oid) AS definition FROM pg_trigger WHERE NOT tgisinternal
            AND tgrelid IN ('ledger_entries'::regclass, 'ledger_lines'::regclass) ORDER BY tgname"),
        DB::select("SELECT pg_get_functiondef('ledger_entry_wallet_owner'::regproc) AS definition"),
    ];
    $installed = $shape();
    $restore = function () use ($migration): void {
        if (! Schema::hasTable('business_repayments')) {
            $migration->up();
        }
    };
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
        ->and(Schema::hasTable('business_repayments'))->toBeTrue()
        ->and($shape())->toEqual($installed);
})->with([
    'after entry' => [['investor_wallets' => 'ROW EXCLUSIVE', 'ledger_entries' => 'ROW EXCLUSIVE'], ['ledger_accounts', 'ledger_lines', 'wallet_deposit_credits']],
    'after line' => [['investor_wallets' => 'ROW EXCLUSIVE', 'ledger_entries' => 'ROW EXCLUSIVE', 'ledger_accounts' => 'ROW EXCLUSIVE',
        'ledger_lines' => 'ROW EXCLUSIVE'], ['wallet_deposit_credits', 'wallet_provider_events']],
]);

/**
 * Table modes reproduce each prefix of an in-flight repayment writer: Party and wallet reads, its operation, its repayment root.
 * The downgrade must wait behind it while holding nothing the writer still needs, then refuse or roll back atomically.
 */
it('waits to downgrade behind an in-flight repayment writer without blocking its remaining writes', function (array $written, array $remaining): void {
    $migration = require database_path('migrations/2026_10_04_100000_create_business_repayment_records.php');
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create migration barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($channels[0]);
        fclose($channels[1]);
        throw new RuntimeException('Could not fork migration downgrade.');
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
            $migration->down();
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getMessage()."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    try {
        $downgrade = (int) trim((string) fgets($channels[0]));
        DB::beginTransaction();
        foreach ($written as $table => $mode) {
            DB::statement('LOCK TABLE '.$table.' IN '.$mode.' MODE');
        }
        fwrite($channels[0], "go\n");
        $blocked = false;
        $deadline = hrtime(true) + 4_000_000_000;
        while (hrtime(true) < $deadline && ! $blocked) {
            $blocked = (bool) DB::scalar('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?))', [$downgrade]);
            if (! $blocked) {
                usleep(10_000);
            }
        }
        expect($blocked)->toBeTrue('Downgrade must wait for the in-flight repayment writer.');
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
        if (! Schema::hasTable('business_repayments')) {
            $migration->up();
        }
    }
    expect(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0)
        ->and(Schema::hasTable('business_repayments'))->toBeTrue();
})->with([
    'after operation' => [['parties' => 'ACCESS SHARE', 'business_wallets' => 'ROW SHARE', 'command_operations' => 'ROW EXCLUSIVE'],
        ['business_repayments', 'ledger_entries', 'ledger_accounts', 'ledger_lines']],
    'after repayment root' => [['parties' => 'ACCESS SHARE', 'business_wallets' => 'ROW SHARE', 'command_operations' => 'ROW EXCLUSIVE',
        'business_repayments' => 'ROW EXCLUSIVE'], ['ledger_entries', 'ledger_accounts', 'ledger_lines']],
]);
