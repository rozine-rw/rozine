<?php

declare(strict_types=1);

/*
 * Review repro for PR #175 range e12a6ea9..ec64f0ea. Copy into tests/Concurrency/.
 * S10 proves each campaign is expired in its own transaction: while the sweep waits on the second Business,
 * the first (deferred) Business row is already unlocked, and the sweep then completes the later closure.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\BusinessCampaignClosure;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/**
 * Holds $heldBusiness FOR UPDATE, runs expireDue($limit) in a forked child, waits until the child is blocked on it,
 * then probes (from another connection) whether $probeBusiness is free and how many closures are already committed.
 *
 * @return array{blocked: bool, waiting: object, probe_free: bool, closures_while_waiting: int, outcome: string, status: int}
 */
function sweepAgainstHeldBusiness(string $heldBusiness, string $probeBusiness, int $limit): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 15);
    stream_set_timeout($channels[1], 15);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $outcome = 'expired '.app(BusinessCampaignStore::class)->expireDue($limit);
        } catch (Throwable $exception) {
            $outcome = 'threw '.class_basename($exception).' '.$exception->getMessage();
        }
        fwrite($channels[1], $outcome."\n");
        exit(0);
    }
    fclose($channels[1]);
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        DB::beginTransaction();
        DB::select('SELECT id FROM business_profiles WHERE id = ? FOR UPDATE', [$heldBusiness]);
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 6_000_000_000;
        $blocked = false;
        while (hrtime(true) < $deadline && ! $blocked) {
            $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
            if (! $blocked) {
                usleep(10_000);
            }
        }
        $waiting = DB::selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$backend]);
        $closures = (int) DB::selectOne('SELECT count(*) AS n FROM business_campaign_closures')->n;
        DB::statement('SAVEPOINT probe');
        try {
            DB::select('SELECT id FROM business_profiles WHERE id = ? FOR UPDATE NOWAIT', [$probeBusiness]);
            $free = true;
        } catch (Throwable) {
            DB::statement('ROLLBACK TO SAVEPOINT probe');
            $free = false;
        }
        DB::commit();
        $outcome = trim((string) fgets($channels[0]));
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
    }

    return ['blocked' => $blocked, 'waiting' => $waiting, 'probe_free' => $free, 'closures_while_waiting' => $closures,
        'outcome' => $outcome, 'status' => pcntl_wexitstatus($status)];
}

it('S10: the sweep releases a deferred Business lock before it waits on the next Business', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $committed = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $committed->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $version = PrimaryReservationVersion::query()->sole();
    expect($checkout->confirm($investor['user']->id, 1, $committed->id, $root->id, 1, $version->payload['terms']['disclosure_version'],
        $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    $this->travel(1)->minute();
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $open = PrimaryReservationFixture::campaign();
    expect($open->business_id)->not->toBe($committed->business_id);
    $this->travelTo($open->expires_at);
    $run = sweepAgainstHeldBusiness($open->business_id, $committed->business_id, 1);
    expect($run['status'])->toBe(0)
        ->and($run['blocked'])->toBeTrue('sweep never waited on the second Business')
        ->and($run['waiting']->query)->toContain('"business_profiles"')->toContain('for update')
        ->and($run['probe_free'])->toBeTrue('the deferred Business row was still locked while the sweep waited on the next Business')
        ->and($run['outcome'])->toBe('expired 1')
        ->and(BusinessCampaignClosure::query()->sole()->business_campaign_id)->toBe($open->id)
        ->and(PrimaryCommitment::query()->count())->toBe(1);
});

it('S11: each closure commits and unlocks its Business before the sweep waits on the next Business', function (): void {
    $this->freezeSecond();
    $first = PrimaryReservationFixture::campaign();
    $this->travel(1)->minute();
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $second = PrimaryReservationFixture::campaign();
    expect($second->business_id)->not->toBe($first->business_id);
    $this->travelTo($second->expires_at);
    $run = sweepAgainstHeldBusiness($second->business_id, $first->business_id, 100);
    expect($run['status'])->toBe(0)
        ->and($run['blocked'])->toBeTrue('sweep never waited on the second Business')
        ->and($run['closures_while_waiting'])->toBe(1, 'the first closure was not yet committed while the sweep waited')
        ->and($run['probe_free'])->toBeTrue('the first Business row was still locked while the sweep waited on the next Business')
        ->and($run['outcome'])->toBe('expired 2')
        ->and(BusinessCampaignClosure::query()->count())->toBe(2);
});
