<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\BusinessCampaign;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('serializes competing reused claims at Business and commits or rolls back generations cash and feed together', function (bool $commit): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $first = PrimaryReservationFixture::investor();
    $other = PrimaryReservationFixture::investor();
    $parent = PrimaryReservationFixture::investor();
    $child = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $a = $checkout->reserve($first['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $checkout->reserve($other['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $checkout->release($first['user']->id, 1, $campaign->id, $a['data']['reservation_id'], 1, (string) Str::uuid());
    config(['database.connections.recycling_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('recycling_observer');
    $entries = LedgerEntry::query()->count();
    $feed = DB::table('change_feed')->count();
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('No recycling barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('No recycling contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        DB::purge('recycling_observer');
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $result = app(PrimaryCheckout::class)->reserve($child['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
            if ($result['code'] !== ($commit ? 'UNITS_UNAVAILABLE' : 'RESERVATION_HELD')) {
                fwrite(STDERR, 'recycling contender code: '.$result['code']."\n");
                exit(2);
            }
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getMessage()."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    $backend = (int) trim((string) fgets($channels[0]));
    DB::purge();
    try {
        DB::beginTransaction();
        $result = $checkout->reserve($parent['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($result['code'])->toBe('RESERVATION_HELD')
            ->and($observer->table('primary_held_claim_releases')->count())->toBe(0)
            ->and($observer->table('primary_held_claim_generations')->count())->toBe(0)
            ->and($observer->table('ledger_entries')->count())->toBe($entries)
            ->and($observer->table('change_feed')->count())->toBe($feed);
        foreach (['primary_reservations', 'primary_commitments', 'investor_wallets'] as $table) {
            foreach ($observer->table($table)->when($table === 'investor_wallets', fn ($q) => $q->whereIn('party_id', [$first['party']->id, $parent['party']->id]))->pluck('id') as $id) {
                expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                    ->toThrow(QueryException::class, 'could not obtain lock');
            }
        }
        fwrite($channels[0], "go\n");
        $blocked = false;
        $query = '';
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            if (DB::scalar('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?))', [$backend])) {
                $blocked = true;
                $query = DB::selectOne('SELECT query FROM pg_stat_activity WHERE pid=?', [$backend])->query;
                break;
            }
            usleep(10000);
        }
        if ($commit) {
            DB::commit();
        } else {
            DB::rollBack();
        }
        pcntl_waitpid($pid, $status);
        expect($blocked)->toBeTrue()->and($query)->toContain('"business_profiles"')
            ->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0);
        expect(PrimaryReservationRecord::query()->count())->toBe(3)
            ->and(DB::table('primary_held_claim_releases')->count())->toBe(1)
            ->and(DB::table('primary_held_claim_generations')->count())->toBe(1080)
            ->and(LedgerEntry::query()->count())->toBe($entries + 1)
            ->and(DB::table('change_feed')->count())->toBe($feed + 2)
            ->and(PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->exists())->toBe($commit);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
        DB::purge();
        DB::purge('recycling_observer');
    }
})->with(['caller commits' => true, 'caller rolls back' => false]);

/** @param list<int> $bindings */
function waitForHeldGenerationInstall(string $sql, array $bindings = [], float $seconds = 8.0): bool
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
function forkHeldGenerationInstall(Closure $work): array
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

it('installs held generations without deadlocking campaign readers or writers waiting for their Business', function (bool $writer): void {
    $migration = require database_path('migrations/2026_10_03_083345_create_primary_held_claim_generations.php');
    $campaign = BusinessCampaign::factory()->create();
    $migration->down();
    DB::disconnect();

    [$migrationPid, $migrationChannel, $migrationBackend] = forkHeldGenerationInstall(fn () => $migration->up());
    [$checkoutPid, $checkoutChannel, $checkoutBackend] = forkHeldGenerationInstall(function () use ($campaign, $writer): void {
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
        $migrationQueued = waitForHeldGenerationInstall("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_profiles'::regclass
            AND mode = 'AccessExclusiveLock' AND NOT granted) AS ok", [$migrationBackend]);
        fwrite($checkoutChannel, "go\n");
        $checkoutQueued = waitForHeldGenerationInstall("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_campaigns'::regclass
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
        if (! Schema::hasTable('primary_held_claim_generations')) {
            $migration->up();
        }
    }

    expect($migrationQueued)->toBeTrue()->and($checkoutQueued)->toBeTrue()
        ->and($results)->toBe([0, 0]);
})->with(['reader' => false, 'writer table admission' => true]);
