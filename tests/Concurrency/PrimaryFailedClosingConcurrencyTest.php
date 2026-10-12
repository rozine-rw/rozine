<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Infrastructure\Primary\EloquentFundedCampaigns;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryFailedClosingFixture;

it('serializes concurrent authentic failure calls on the campaign and commits one complete refund', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryFailedClosingFixture::close(settle: false);
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create terminal failure barrier.');
    }
    foreach ($channels as $channel) {
        stream_set_timeout($channel, 10);
    }
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork terminal failure contender.');
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
            PrimaryFailedClosingFixture::replay($campaign, $instruction);
            $outcome = 'closed';
        } catch (Throwable $exception) {
            $outcome = $exception::class.':'.$exception->getMessage();
        }
        fwrite($channels[1], $outcome."\n");
        fclose($channels[1]);
        exit(0);
    }
    fclose($channels[1]);
    $status = 0;
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        $blocked = false;
        DB::transaction(function () use ($campaign, $instruction, $channels, $backend, &$blocked): void {
            $adapter = app(EloquentFundedCampaigns::class);
            $adapter->lockBusiness($campaign->businessId);
            $adapter->lockFunded($campaign->campaignId);
            fwrite($channels[0], "go\n");
            $deadline = hrtime(true) + 4_000_000_000;
            while (hrtime(true) < $deadline && ! $blocked) {
                $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
                if (! $blocked) {
                    usleep(10_000);
                }
            }
            $adapter->failClose($campaign, $instruction);
        });
        expect($blocked)->toBeTrue()->and(trim((string) fgets($channels[0])))->toBe('closed')
            ->and(DB::table('primary_failed_closings')->count())->toBe(1)
            ->and(DB::table('business_campaign_closures')->count())->toBe(1)
            ->and(DB::table('ledger_entries')->where('kind', 'primary_refund')->count())->toBe(count($campaign->commitments))
            ->and(app(BusinessExposureStore::class)->current($campaign->businessId))->toBe([]);
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
});
