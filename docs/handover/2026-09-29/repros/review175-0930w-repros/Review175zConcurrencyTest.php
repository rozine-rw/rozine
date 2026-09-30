<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * Review repros for #175 range d701d879..33ba9040: real forks + pg_blocking_pids at the publication deadline.
 * Parent holds one side in an open transaction; the child runs the other and must wait on business_profiles.
 */

/** @return array{campaign: BusinessCampaign, purchases: list<array{investor: array<string, mixed>, root: PrimaryReservationRecord, version: PrimaryReservationVersion}>} */
function review175zRaise(bool $confirmSecond, ?Closure $beforePurchases = null): array
{
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investors = [PrimaryReservationFixture::investor(), PrimaryReservationFixture::investor()];
    if ($beforePurchases !== null) {
        $beforePurchases($campaign);
    }
    $checkout = app(PrimaryCheckout::class);
    $purchases = [];
    foreach ([true, $confirmSecond] as $index => $confirm) {
        $investor = $investors[$index];
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        if ($confirm) {
            expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
        }
        $purchases[] = ['investor' => $investor, 'root' => $root, 'version' => $version];
    }

    return ['campaign' => $campaign, 'purchases' => $purchases];
}

/**
 * Parent runs $holder inside an open transaction, then releases the child which runs $contender.
 * $beforeCommit runs after the child is proven blocked and before the parent commits.
 *
 * @return array{bool, string, int, string}
 */
function review175zRace(Closure $holder, Closure $contender, ?Closure $beforeCommit = null): array
{
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 20);
    stream_set_timeout($channels[1], 20);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        $code = 1;
        try {
            DB::purge();
            DB::statement("SET lock_timeout = '15s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                $code = 3;
            } else {
                $result = DB::transaction($contender);
                @fwrite($channels[1], (is_string($result) ? $result : 'ok')."\n");
                $code = 0;
            }
        } catch (CommandRejection $exception) {
            @fwrite($channels[1], $exception->reason."\n");
            $code = 2;
        } catch (Throwable $exception) {
            @fwrite($channels[1], $exception::class.' '.$exception->getMessage()."\n");
            $code = 1;
        } finally {
            exit($code);
        }
    }
    fclose($channels[1]);
    $child = (int) trim((string) fgets($channels[0]));
    DB::purge();
    $blocked = false;
    $waiting = '';
    $status = 0;
    try {
        DB::beginTransaction();
        $parent = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $holder();
        fwrite($channels[0], "go\n");
        $deadline = microtime(true) + 8;
        while (microtime(true) < $deadline) {
            if ((bool) DB::selectOne('SELECT ? = ANY(pg_blocking_pids(?)) AS blocked', [$parent, $child])->blocked) {
                $blocked = true;
                $waiting = DB::selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$child])->query;
                break;
            }
            usleep(10000);
        }
        if ($beforeCommit !== null) {
            $beforeCommit();
        }
        DB::commit();
        $message = trim((string) fgets($channels[0]));
        pcntl_waitpid($pid, $status);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
        DB::purge();
    }

    return [$blocked, $waiting, pcntl_wexitstatus($status), $message];
}

it('ZC-1: a final confirmation that starts before the deadline but waits past it on the funding lock is refused, with real moving clocks', function (): void {
    // Moving clock (no freeze): shift real time so the deadline is a few seconds ahead once purchases are placed.
    $raise = review175zRaise(false, function (BusinessCampaign $campaign): void {
        $shift = ($campaign->expires_at->getTimestamp() + $campaign->expires_at->micro / 1e6) - microtime(true) - 10.0;
        Carbon::setTestNow(fn ($real) => $real->addMicroseconds((int) round($shift * 1e6)));
    });
    $campaign = $raise['campaign'];
    [, $second] = $raise['purchases'];
    expect(now()->lt($campaign->expires_at))->toBeTrue()
        ->and($second['root']->expires_at->equalTo($campaign->expires_at))->toBeTrue();
    $holderStarted = null;
    [$blocked, $waiting, $exit, $message] = review175zRace(
        function () use ($campaign, &$holderStarted): void {
            $holderStarted = now();
            app(PrimaryCampaignSource::class)->lockForFunding($campaign->id);
        },
        fn (): string => app(PrimaryCheckout::class)->confirm($second['investor']['user']->id, 1, $campaign->id, $second['root']->id, 1,
            $second['version']->payload['terms']['disclosure_version'], $second['version']->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'],
        function () use ($campaign): void {
            while (now()->lte($campaign->expires_at->addMilliseconds(300))) {
                usleep(20000);
            }
        });
    expect($holderStarted->lt($campaign->expires_at))->toBeTrue()
        ->and($blocked)->toBeTrue()->and($waiting)->toContain('"business_profiles"')
        ->and([$exit, $message])->toBe([0, 'RESERVATION_EXPIRED'])
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $second['root']->id)->orderByDesc('revision')->first()->state)->toBe('expired')
        ->and(fn () => DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($campaign->id)))
        ->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FULLY_COMMITTED');
    Carbon::setTestNow();
});

it('ZC-2: after the deadline the campaign expiry sweep waits for the funding reader and still defers the committed raise', function (): void {
    $this->freezeSecond();
    $raise = review175zRaise(true);
    $campaign = $raise['campaign'];
    $this->travelTo($campaign->expires_at->addDay());
    [$blocked, $waiting, $exit, $message] = review175zRace(
        fn () => expect(app(PrimaryReservations::class)->lockFundingCandidate($campaign->id)->purchases)->toHaveCount(2),
        fn (): string => (string) app(BusinessCampaignStore::class)->expireDue(10));
    expect($blocked)->toBeTrue()->and($waiting)->toContain('"business_profiles"')
        ->and([$exit, $message])->toBe([0, '0'])
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($campaign->id))->purchases)->toHaveCount(2);
});

it('ZC-3: an expiry closure committed while the funding source waits is seen after the Business lock and refused', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $this->travelTo($campaign->expires_at);
    [$blocked, $waiting, $exit, $message] = review175zRace(
        fn () => expect(app(BusinessCampaignStore::class)->expireDue(10))->toBe(1),
        fn () => app(PrimaryCampaignSource::class)->lockForFunding($campaign->id));
    expect($blocked)->toBeTrue()->and($waiting)->toContain('"business_profiles"')
        ->and([$exit, $message])->toBe([2, 'CAMPAIGN_CLOSED'])
        ->and(BusinessCampaignClosure::query()->count())->toBe(1);
});
