<?php

declare(strict_types=1);

/*
 * Review repro for PR #175 c8f30fcb, migration 2026_09_28_175455 up(). Copy into tests/Concurrency/.
 *
 * up() takes ACCESS EXCLUSIVE on business_profiles and then on business_campaigns. The real
 * EloquentPrimaryCampaignSource::lockBusiness() (called first by every PrimaryCheckout command, and
 * the same shape as EloquentBusinessCampaignStore::findCancellation()) reads business_campaigns
 * (ACCESS SHARE, held to the end of the transaction) and only then locks the Business row.
 *
 * Scenario, using the real lockBusiness() code:
 *   1. an in-flight Business transaction holds the Business row (FOR UPDATE);
 *   2. the migration queues for business_profiles behind it;
 *   3. a checkout command starts: its campaign read is granted, its Business lock queues behind the
 *      migration;
 *   4. the Business transaction commits: the migration gets business_profiles, then waits for
 *      business_campaigns, which the checkout holds, while the checkout waits for the migration.
 * PostgreSQL aborts one side with 40P01. The migration's wait starts last, so its deadlock check
 * finds the cycle and the migration is the side aborted: the deploy fails.
 *
 * FAILS on c8f30fcb. Dropping business_campaigns from the LOCK TABLE list (175455 never reads it)
 * makes it pass.
 */

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Models\BusinessCampaign;
use Illuminate\Support\Facades\DB;

function r175lWaitFor(string $sql, array $bindings = [], float $seconds = 8.0): bool
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

function r175lFork(Closure $work): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 15);
    stream_set_timeout($channels[1], 15);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
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

it('DEFECT: installing 175455 does not deadlock with a checkout that read its campaign before locking the Business', function (): void {
    $path = database_path('migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php');
    $campaign = BusinessCampaign::factory()->create();
    (require $path)->down();
    DB::disconnect();

    [$migrationPid, $migrationChannel, $migrationBackend] = r175lFork(fn () => (require $path)->up());
    [$checkoutPid, $checkoutChannel, $checkoutBackend] = r175lFork(function () use ($campaign): void {
        DB::transaction(fn () => app(PrimaryCampaignSource::class)->lockBusiness($campaign->id), 1);
    });
    DB::purge();
    $results = [];
    try {
        DB::beginTransaction();
        DB::table('business_profiles')->where('id', $campaign->business_id)->lockForUpdate()->first();
        fwrite($migrationChannel, "go\n");
        $migrationQueued = r175lWaitFor("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_profiles'::regclass
            AND mode = 'AccessExclusiveLock' AND NOT granted) AS ok", [$migrationBackend]);
        fwrite($checkoutChannel, "go\n");
        $checkoutQueued = r175lWaitFor("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND relation = 'business_campaigns'::regclass
            AND mode = 'AccessShareLock' AND granted) AND ? = ANY(pg_blocking_pids(?)) AS ok", [$checkoutBackend, $migrationBackend, $checkoutBackend]);
        usleep(1_500_000); // the checkout's own deadlock check runs now and finds no cycle yet
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
        if ((int) DB::selectOne("SELECT count(*) AS total FROM pg_trigger WHERE tgname = 'ledger_primary_terminal_bound'")->total === 0) {
            (require $path)->up();
        }
    }

    expect($migrationQueued)->toBeTrue()->and($checkoutQueued)->toBeTrue()
        ->and($results)->toBe([0, 0]); // c8f30fcb: [2, 0], the migration is aborted with 40P01
});
