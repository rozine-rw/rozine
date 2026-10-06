<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryFunding;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;

it('serializes its migration with a real funding writer at the Business gate in either order', function (string $direction, bool $writerFirst): void {
    $this->freezeSecond();
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed(fund: false);
    $migration = require database_path('migrations/2026_09_30_234802_require_complete_primary_holdings_for_issued_closings.php');
    if ($direction === 'up') {
        $migration->down();
    }
    $fund = fn (): array => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => PrimaryHoldingFixture::admission($campaign));
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create completeness install barrier.');
    }
    foreach ($channels as $channel) {
        stream_set_timeout($channel, 10);
    }
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        foreach ($channels as $channel) {
            fclose($channel);
        }
        throw new RuntimeException('Could not fork completeness install worker.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '6s'");
            fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            if ($writerFirst) {
                $migration->{$direction}();
                $outcome = 'migrated';
            } else {
                $outcome = DB::transaction($fund)['scope'];
            }
            fwrite($channels[1], $outcome."\n");
            exit(0);
        } catch (Throwable $exception) {
            fwrite($channels[1], $exception::class."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    $status = 0;
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        DB::beginTransaction();
        if ($writerFirst) {
            DB::table('business_profiles')->where('id', $campaign->business_id)->lockForUpdate()->first();
        } else {
            $migration->{$direction}();
        }
        fwrite($channels[0], "go\n");
        $waiting = '';
        $deadline = hrtime(true) + 4_000_000_000;
        while (hrtime(true) < $deadline) {
            if ((bool) DB::scalar('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?))', [$backend])) {
                $waiting = (string) DB::scalar('SELECT query FROM pg_stat_activity WHERE pid = ?', [$backend]);
                break;
            }
            usleep(10_000);
        }
        expect($waiting)->toContain($writerFirst ? 'LOCK TABLE business_profiles IN EXCLUSIVE MODE' : '"business_profiles"')
            ->and(DB::scalar('SELECT count(*) FROM pg_locks WHERE pid = pg_backend_pid() AND NOT granted'))->toBe(0);
        if ($writerFirst) {
            expect($fund()['scope'])->toBe('primary-funding-v1');
        }
        DB::commit();
        expect(trim((string) fgets($channels[0])))->toBe($writerFirst ? 'migrated' : 'primary-funding-v1');
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        $deadline = hrtime(true) + 8_000_000_000;
        do {
            $waited = pcntl_waitpid($pid, $status, WNOHANG);
            if ($waited === 0) {
                usleep(10_000);
            }
        } while ($waited === 0 && hrtime(true) < $deadline);
        if ($waited === 0) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        if (! (bool) DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'primary_issued_closing_complete')")) {
            $migration->up();
        }
    }
    expect(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0)
        ->and(DB::table('primary_campaign_fundings')->count())->toBe(1);
})->with(['install' => ['up'], 'rollback' => ['down']])->with(['writer first' => [true], 'migration first' => [false]]);
