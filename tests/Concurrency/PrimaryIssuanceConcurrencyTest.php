<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Primary\Contracts\IssuedHoldingEvidence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryIssuanceFixture;

/** Actual two-process lock barrier, with bounded cleanup and no time-based assumption of contention. */
function contendPrimaryIssuance(Closure $held, Closure $contended): string
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create issuance barrier.');
    }
    foreach ($channels as $channel) {
        stream_set_timeout($channel, 12);
    }
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork issuance contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        DB::statement("SET lock_timeout = '8s'");
        fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
        if (fgets($channels[1]) !== "go\n") {
            exit(3);
        }
        try {
            $contended();
            $outcome = 'completed';
        } catch (QueryException $exception) {
            $outcome = str_contains($exception->getMessage(), 'Retained primary issuances require a forward migration') ? 'occupied-refused' : $exception->getMessage();
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
        $waiter = (int) trim((string) fgets($channels[0]));
        $blocked = false;
        DB::transaction(function () use ($held, $channels, $waiter, &$blocked): void {
            $held();
            fwrite($channels[0], "go\n");
            $deadline = hrtime(true) + 5_000_000_000;
            while (hrtime(true) < $deadline && ! $blocked) {
                $blocked = (bool) DB::scalar('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?))', [$waiter]);
                if (! $blocked) {
                    usleep(10_000);
                }
            }
        });
        $outcome = trim((string) fgets($channels[0]));
        expect($blocked)->toBeTrue();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        $deadline = hrtime(true) + 10_000_000_000;
        $waited = 0;
        while (($waited = pcntl_waitpid($pid, $status, WNOHANG)) === 0 && hrtime(true) < $deadline) {
            usleep(10_000);
        }
        if ($waited === 0) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
    }
    expect(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0);

    return $outcome;
}

it('serializes two genuine success callbacks and commits complete Holdings journals and conversion once', function (): void {
    $this->freezeSecond();
    $approved = PrimaryIssuanceFixture::approved(units: ['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    $observe = fn () => $approved['observe']('2026-01-30T22:30:00+00:00');
    expect(contendPrimaryIssuance($observe, $observe))->toBe('completed');
    $campaign = $approved['campaign'];
    $bundle = DB::transaction(fn (): ?array => app(IssuedHoldingEvidence::class)->find($campaign->campaignId));
    expect($bundle['issued_holdings'])->toHaveCount(3)->and(DB::table('primary_campaign_issuances')->count())->toBe(1)
        ->and(DB::table('primary_holdings')->count())->toBe(3)->and(DB::table('ledger_entries')->where('kind', 'primary_issue')->count())->toBe(3)
        ->and(app(BusinessExposureStore::class)->current($campaign->businessId))->toBe([['id' => $campaign->exposureReservationId, 'principal' => $campaign->principal]])
        ->and(DB::table('ledger_entries')->where('kind', 'primary_refund')->count())->toBe(0);
});

it('serializes schema install and genuine issuance in either order and refuses occupied rollback', function (bool $issueFirst): void {
    $this->freezeSecond();
    $approved = PrimaryIssuanceFixture::approved();
    $observe = fn () => $approved['observe']('2026-01-30T22:30:00+00:00');
    $migration = require database_path('migrations/2026_10_12_113137_retain_primary_successful_issuance.php');
    $install = fn () => DB::transaction(function () use ($migration): void {
        $migration->down();
        $migration->up();
    });
    $outcome = $issueFirst ? contendPrimaryIssuance($observe, $install) : contendPrimaryIssuance($install, $observe);
    expect($outcome)->toBe($issueFirst ? 'occupied-refused' : 'completed')
        ->and(DB::table('primary_campaign_issuances')->count())->toBe(1)
        ->and(DB::transaction(fn (): ?array => app(IssuedHoldingEvidence::class)->find($approved['campaign']->campaignId))['issued_holdings'])->toHaveCount(2);
})->with(['issue transaction first' => true, 'empty schema installation first' => false]);
