<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table modes reproduce each prefix of an in-flight Investor posting after its wallet lock: entry, account, line.
 * The installer must wait behind it while holding nothing the posting still writes.
 */
it('installs Business deposit records behind an in-flight Investor posting without blocking its remaining writes', function (array $written, array $remaining): void {
    $migration = require database_path('migrations/2026_10_03_120000_create_business_deposit_records.php');
    $shape = fn (): array => [
        DB::select("SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conrelid IN ('ledger_entries'::regclass,
            'business_wallets'::regclass) ORDER BY conname"),
        DB::select("SELECT tgname, pg_get_triggerdef(oid) AS definition FROM pg_trigger WHERE NOT tgisinternal
            AND tgrelid IN ('ledger_entries'::regclass, 'ledger_lines'::regclass) ORDER BY tgname"),
    ];
    $installed = $shape();
    $businessRepayments = require database_path('migrations/2026_10_04_100000_create_business_repayment_records.php');
    $restore = function () use ($migration, $businessRepayments): void {
        if (! Schema::hasTable('business_deposit_intents')) {
            $migration->up();
        }
        $businessRepayments->up();
    };
    $businessRepayments->down();
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
        ->and(Schema::hasTable('business_deposit_intents'))->toBeTrue()
        ->and($shape())->toEqual($installed);
})->with([
    'after entry' => [['investor_wallets' => 'ROW EXCLUSIVE', 'ledger_entries' => 'ROW EXCLUSIVE'], ['ledger_accounts', 'ledger_lines', 'wallet_deposit_credits']],
    'after line' => [['investor_wallets' => 'ROW EXCLUSIVE', 'ledger_entries' => 'ROW EXCLUSIVE', 'ledger_accounts' => 'ROW EXCLUSIVE',
        'ledger_lines' => 'ROW EXCLUSIVE'], ['wallet_deposit_credits', 'wallet_provider_events']],
]);
