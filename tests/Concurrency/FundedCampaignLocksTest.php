<?php

declare(strict_types=1);

use App\Infrastructure\Primary\EloquentFundedCampaigns;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;

it('retains the real Business and campaign row gates until the caller commits', function (string $gate): void {
    $this->freezeSecond();
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $table = $gate === 'business' ? 'business_profiles' : 'business_campaigns';
    $id = $gate === 'business' ? $campaign->business_id : $campaign->id;
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create funding gate barrier.');
    }
    foreach ($channels as $channel) {
        stream_set_timeout($channel, 10);
    }
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork funding gate contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        DB::statement("SET lock_timeout = '6s'");
        fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
        if (fgets($channels[1]) !== "go\n") {
            exit(3);
        }
        try {
            $outcome = DB::transaction(fn (): string => DB::table($table)->where('id', $id)->lockForUpdate()->first()->id);
        } catch (Throwable $exception) {
            $outcome = $exception::class;
        }
        fwrite($channels[1], $outcome."\n");
        fclose($channels[1]);
        exit(0);
    }
    fclose($channels[1]);
    $status = 0;
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        DB::beginTransaction();
        $adapter = app(EloquentFundedCampaigns::class);
        $adapter->lockBusiness($campaign->business_id);
        expect($adapter->lockFunded($campaign->id)->campaignId)->toBe($campaign->id);
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 4_000_000_000;
        $blocked = false;
        while (hrtime(true) < $deadline && ! $blocked) {
            $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
            if (! $blocked) {
                usleep(10_000);
            }
        }
        $query = DB::selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$backend])->query;
        DB::commit();
        expect($blocked)->toBeTrue()->and($query)->toContain('"'.$table.'"', 'for update')
            ->and(trim((string) fgets($channels[0])))->toBe($id);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        $deadline = hrtime(true) + 8_000_000_000;
        while (pcntl_waitpid($pid, $status, WNOHANG) === 0 && hrtime(true) < $deadline) {
            usleep(10_000);
        }
        if (pcntl_waitpid($pid, $status, WNOHANG) === 0) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
    }
    expect(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0);
})->with(['Business gate' => ['business'], 'campaign gate' => ['campaign']]);
