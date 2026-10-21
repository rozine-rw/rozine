<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/** @return array<string, array<int, stdClass>> */
function closureMigrationSchemaFingerprint(): array
{
    return [
        'columns' => DB::select("SELECT c.relname, a.attname, format_type(a.atttypid, a.atttypmod) AS type, a.attnotnull,
            a.attidentity, a.attgenerated, pg_get_expr(d.adbin, d.adrelid) AS default_value FROM pg_class c
            JOIN pg_attribute a ON a.attrelid = c.oid AND a.attnum > 0 AND NOT a.attisdropped
            LEFT JOIN pg_attrdef d ON d.adrelid = c.oid AND d.adnum = a.attnum
            WHERE c.relnamespace = current_schema()::regnamespace AND c.relkind IN ('r', 'p') ORDER BY c.relname, a.attnum"),
        'constraints' => DB::select('SELECT conrelid::regclass::text AS table_name, conname, pg_get_constraintdef(oid) AS definition,
            convalidated FROM pg_constraint WHERE connamespace = current_schema()::regnamespace ORDER BY table_name, conname'),
        'indexes' => DB::select('SELECT c.relname, pg_get_indexdef(i.indexrelid) AS definition, i.indisvalid, i.indisready FROM pg_index i
            JOIN pg_class c ON c.oid = i.indexrelid WHERE c.relnamespace = current_schema()::regnamespace ORDER BY c.relname'),
        'functions' => DB::select("SELECT pg_get_function_identity_arguments(oid) AS arguments, proname, pg_get_functiondef(oid) AS definition
            FROM pg_proc WHERE pronamespace = current_schema()::regnamespace AND prokind = 'f' ORDER BY proname, arguments"),
        'triggers' => DB::select('SELECT c.relname, t.tgname, t.tgenabled, pg_get_triggerdef(t.oid) AS definition FROM pg_trigger t
            JOIN pg_class c ON c.oid = t.tgrelid WHERE c.relnamespace = current_schema()::regnamespace AND NOT t.tgisinternal
            ORDER BY c.relname, t.tgname'),
        'constraint_triggers' => DB::select('SELECT r.relname, c.conname, t.tgfoid::regprocedure::text AS function_name,
            t.tgtype, t.tgenabled, t.tgdeferrable, t.tginitdeferred FROM pg_trigger t
            JOIN pg_constraint c ON c.oid = t.tgconstraint JOIN pg_class r ON r.oid = t.tgrelid
            WHERE r.relnamespace = current_schema()::regnamespace AND t.tgisinternal
            ORDER BY r.relname, c.conname, function_name, t.tgtype'),
    ];
}

beforeEach(function (): void {
    $this->closureDependenciesRemoved = false;
    $this->closureCurrentSchema = closureMigrationSchemaFingerprint();
    // The platform fee and automatic disbursement migrations rebind the primary closing authority, so they come off first and go back last.
    $this->closureDependants = [
        require database_path('migrations/2026_10_16_180415_disburse_funded_campaigns_automatically.php'),
        require database_path('migrations/2026_10_16_161905_add_platform_fee_to_disbursements.php'),
        require database_path('migrations/2026_10_12_113137_retain_primary_successful_issuance.php'),
        require database_path('migrations/2026_10_12_054843_retain_primary_terminal_failed_closings.php'),
    ];
    DB::transaction(function (): void {
        foreach ($this->closureDependants as $migration) {
            $migration->down();
        }
    });
    $this->closureDependenciesRemoved = true;
    $this->closureHistoricalSchema = closureMigrationSchemaFingerprint();
});

afterEach(function (): void {
    if (! $this->closureDependenciesRemoved) {
        return;
    }
    $closures = require database_path('migrations/2026_09_30_094556_bind_campaign_closures_to_complete_primary_returns.php');
    try {
        if (! Schema::hasTable('primary_campaign_closure_returns')) {
            $closures->up();
        }
        expect(closureMigrationSchemaFingerprint())->toEqual($this->closureHistoricalSchema);
    } finally {
        DB::transaction(function (): void {
            foreach (array_reverse($this->closureDependants) as $migration) {
                $migration->up();
            }
        });
        expect(closureMigrationSchemaFingerprint())->toEqual($this->closureCurrentSchema);
    }
});

it('changes closure guards without trapping earlier reads or later writes of an actual refund', function (bool $install, bool $firstRead): void {
    $this->freezeSecond();
    $migration = require database_path('migrations/2026_09_30_094556_bind_campaign_closures_to_complete_primary_returns.php');
    if ($install) {
        $migration->down();
    }
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $reserved = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
    $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not open closure migration barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($channels[0]);
        fclose($channels[1]);
        if ($install) {
            $migration->up();
        }
        throw new RuntimeException('Could not fork closure migration.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            DB::statement("SET statement_timeout = '12s'");
            fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $migration->{$install ? 'up' : 'down'}();
            exit(0);
        } catch (Throwable) {
            exit(1);
        }
    }
    fclose($channels[1]);
    $reaped = false;
    $status = 0;
    $event = 'eloquent.created: '.LedgerEntry::class;
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        expect($backend)->toBeGreaterThan(0);
        $pause = function () use ($channels, $backend, $pid, $firstRead, &$reaped, &$status): void {
            fwrite($channels[0], "go\n");
            $blocked = false;
            $deadline = hrtime(true) + 4_000_000_000;
            while (hrtime(true) < $deadline && ! $blocked && ! $reaped) {
                $blocked = (bool) DB::scalar('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?))', [$backend]);
                $reaped = pcntl_waitpid($pid, $status, WNOHANG) === $pid;
                if (! $blocked && ! $reaped) {
                    usleep(10_000);
                }
            }
            if ($firstRead) {
                expect($reaped)->toBeTrue()->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0);
            } else {
                expect($blocked)->toBeTrue()
                    ->and(DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND granted AND relation IN
                        ('business_profiles'::regclass, 'business_campaigns'::regclass, 'command_operations'::regclass))", [$backend]))->toBeFalse();
            }
        };
        if ($firstRead) {
            $paused = false;
            DB::listen(function (QueryExecuted $query) use ($pause, &$paused): void {
                if (! $paused && str_starts_with($query->sql, 'select * from "business_campaigns"')) {
                    $paused = true;
                    $pause();
                }
            });
        } else {
            Event::listen($event, function (LedgerEntry $entry) use ($pause): void {
                if ($entry->kind === 'primary_refund') {
                    $pause();
                }
            });
        }
        expect($checkout->refund($investor['user']->id, 1, $campaign->id, $root->id, 2, (string) Str::uuid())['code'])->toBe('COMMITMENT_REFUNDED');
        $deadline = hrtime(true) + 10_000_000_000;
        while (! $reaped && hrtime(true) < $deadline) {
            $reaped = pcntl_waitpid($pid, $status, WNOHANG) === $pid;
            if (! $reaped) {
                usleep(10_000);
            }
        }
        expect($reaped)->toBeTrue()->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0);
    } finally {
        Event::forget($event);
        fclose($channels[0]);
        if (! $reaped) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        if (! DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'campaign_closure_primary_returns')")) {
            $migration->up();
        }
    }
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
})->with(['install' => true, 'rollback' => false])->with(['first campaign read' => true, 'refund header before lines/receipt' => false]);

/** Table modes reproduce the write boundaries observed in the independently forked command probes. */
it('installs closure guards behind in-flight command table locks without blocking their remaining writes', function (array $written, array $remaining): void {
    $closures = require database_path('migrations/2026_09_30_094556_bind_campaign_closures_to_complete_primary_returns.php');
    $closures->down();
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
        $closures->up();
        throw new RuntimeException('Could not fork migration installer.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            DB::statement("SET statement_timeout = '12s'");
            fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $closures->up();
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getMessage()."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    $reaped = false;
    $status = 0;
    try {
        $installer = (int) trim((string) fgets($channels[0]));
        DB::beginTransaction();
        DB::statement('LOCK TABLE business_profiles, business_campaigns, primary_reservations IN ROW SHARE MODE');
        foreach ($written as $table) {
            DB::statement('LOCK TABLE '.$table.' IN ROW EXCLUSIVE MODE');
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
        expect($blocked)->toBeTrue('Installer must wait for the in-flight writer.');
        foreach ($remaining as $table) {
            DB::statement('LOCK TABLE '.$table.' IN ROW EXCLUSIVE MODE NOWAIT');
        }
        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        $deadline = hrtime(true) + 10_000_000_000;
        while (! $reaped && hrtime(true) < $deadline) {
            $reaped = pcntl_waitpid($pid, $status, WNOHANG) === $pid;
            if (! $reaped) {
                usleep(10_000);
            }
        }
        if (! $reaped) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        if (! Schema::hasTable('primary_campaign_closure_returns')) {
            $closures->up();
        }
    }
    expect($reaped)->toBeTrue()->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0)
        ->and(Schema::hasTable('primary_campaign_closure_returns'))->toBeTrue();
})->with([
    'confirm after version' => [['primary_reservation_versions'], ['primary_commitments', 'investor_wallets', 'ledger_entries', 'command_operations']],
    'release after version' => [['primary_reservation_versions'], ['investor_wallets', 'ledger_entries', 'command_operations']],
    'cancel after closure' => [['business_campaign_closures'], ['command_operations']],
    'reserve after journal' => [['primary_reservations', 'investor_wallets', 'ledger_entries', 'command_operations'], []],
    'confirm after journal' => [['primary_reservation_versions', 'primary_commitments', 'investor_wallets', 'ledger_entries', 'command_operations'], []],
    'release after journal' => [['primary_reservation_versions', 'investor_wallets', 'ledger_entries', 'command_operations'], []],
    'cancel after journal' => [['business_campaign_closures', 'command_operations'], []],
]);

it('audits retained empty closure history without taking Business row locks', function (): void {
    $this->freezeSecond();
    $campaign = PrimaryReservationFixture::campaign();
    expect(app(BusinessCampaignStore::class)->cancel($campaign->actor_user_id, 1, $campaign->business_id, $campaign->id, 1, null, (string) Str::uuid())['code'])
        ->toBe('CAMPAIGN_CANCELLED');
    $migration = require database_path('migrations/2026_09_30_094556_bind_campaign_closures_to_complete_primary_returns.php');
    $migration->down();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        $migration->up();
        throw new RuntimeException('Could not open closure audit barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($channels[0]);
        fclose($channels[1]);
        $migration->up();
        throw new RuntimeException('Could not fork closure audit.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            DB::statement("SET statement_timeout = '12s'");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $migration->up();
            exit(0);
        } catch (Throwable) {
            exit(1);
        }
    }
    fclose($channels[1]);
    $reaped = false;
    $status = 0;
    try {
        DB::beginTransaction();
        DB::table('business_profiles')->where('id', $campaign->business_id)->lockForUpdate()->sole();
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 4_000_000_000;
        while (! $reaped && hrtime(true) < $deadline) {
            $reaped = pcntl_waitpid($pid, $status, WNOHANG) === $pid;
            if (! $reaped) {
                usleep(10_000);
            }
        }
        expect($reaped)->toBeTrue()->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0);
    } finally {
        DB::rollBack();
        fclose($channels[0]);
        if (! $reaped) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        if (! Schema::hasTable('primary_campaign_closure_returns')) {
            $migration->up();
        }
    }
    expect(DB::table('business_campaign_closures')->count())->toBe(1)
        ->and(DB::table('primary_campaign_closure_returns')->count())->toBe(0);
});
