<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * Review repros for #175 C/E: real forks + pg_blocking_pids.
 * Parent holds one side in an open transaction; child runs the other and must wait on
 * business_profiles (the first lock of every path) - never deadlock.
 */

function review175xRaise(bool $fullyConfirmed): array
{
    InvestorWalletFixture::policy(maximum: null);
    $fixture = AuditSealingFixture::ready(signatories: 2, requiredSignatories: 1);
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $campaigns = app(BusinessCampaignStore::class);
    $campaigns->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Verified release.', (string) Str::uuid());
    $campaigns->publish($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
        $fixture['application']->id, $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $campaign = BusinessCampaign::query()->sole();
    $checkout = app(PrimaryCheckout::class);
    $purchases = [];
    foreach ([true, $fullyConfirmed] as $confirm) {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        if ($confirm) {
            $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        }
        $purchases[] = ['investor' => $investor, 'root' => $root, 'version' => $version];
    }

    return ['fixture' => $fixture, 'campaign' => $campaign, 'purchases' => $purchases];
}

/**
 * Parent runs $holder inside an open transaction, then releases the child which runs $contender.
 * Returns [blocked, waitingQuery, childExit]. Child exit: 0 ok, 2 CommandRejection (reason on stderr), 1 other (e.g. deadlock).
 */
function review175xRace(Closure $holder, Closure $contender, bool $commit = true): array
{
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 15);
    stream_set_timeout($channels[1], 15);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        $code = 1;
        try {
            DB::purge();
            DB::statement("SET lock_timeout = '10s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                $code = 3;
            } else {
                DB::transaction($contender);
                @fwrite($channels[1], "ok\n");
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
    try {
        DB::beginTransaction();
        $parent = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $holder();
        fwrite($channels[0], "go\n");
        $deadline = microtime(true) + 6;
        while (microtime(true) < $deadline) {
            if ((bool) DB::selectOne('SELECT ? = ANY(pg_blocking_pids(?)) AS blocked', [$parent, $child])->blocked) {
                $blocked = true;
                $waiting = DB::selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$child])->query;
                break;
            }
            usleep(10000);
        }
        $commit ? DB::commit() : DB::rollBack();
        $message = trim((string) fgets($channels[0]));
        pcntl_waitpid($pid, $status);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        DB::purge();
    }

    return [$blocked, $waiting, pcntl_wexitstatus($status), $message];
}

it('C-R10: funding candidate locks serialize every Primary/Business contender on the Business row without deadlock', function (string $case): void {
    $this->freezeSecond();
    $raise = review175xRaise(true);
    $campaign = $raise['campaign'];
    [$first] = $raise['purchases'];
    $contender = match ($case) {
        'reserve' => function () use ($campaign): void {
            $investor = PrimaryReservationFixture::investor();
            app(PrimaryReservations::class)->reserve($campaign->id, $investor['party']->id, (string) Str::uuid(), '1', PrimaryReservationFixture::terms(...));
        },
        'release' => fn () => app(PrimaryReservations::class)->release($campaign->id, $first['root']->id, $first['root']->party_id, strtolower((string) Str::ulid()), 2),
        'expire' => fn () => app(PrimaryReservations::class)->expire($campaign->id, $first['root']->id),
        'cancel' => fn () => app(BusinessCampaignStore::class)->cancel($campaign->actor_user_id, 1, $campaign->business_id, $campaign->id, 1, null, (string) Str::uuid()),
        'second funding reader' => fn () => app(PrimaryReservations::class)->lockFundingCandidate($campaign->id),
    };
    // Build any fixture rows the contender needs before the race (investor wallet for reserve).
    [$blocked, $waiting, $exit, $message] = review175xRace(
        fn () => expect(app(PrimaryReservations::class)->lockFundingCandidate($campaign->id)->purchases)->toHaveCount(2),
        $contender);
    expect($blocked)->toBeTrue()->and($waiting)->toContain('"business_profiles"')
        ->and($exit)->not->toBe(1, $message);
})->with(['release', 'expire', 'cancel', 'second funding reader']);

it('C-R11: the funding reader waits for an in-flight contender holding the Business lock (reverse direction)', function (string $case): void {
    $this->freezeSecond();
    $raise = review175xRaise(false);
    $campaign = $raise['campaign'];
    [$first, $second] = $raise['purchases'];
    $holder = match ($case) {
        'confirm' => fn () => app(PrimaryCheckout::class)->confirm($second['investor']['user']->id, 1, $campaign->id, $second['root']->id, 1,
            $second['version']->payload['terms']['disclosure_version'], $second['version']->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...)),
        'release' => fn () => app(PrimaryCheckout::class)->release($second['investor']['user']->id, 1, $campaign->id, $second['root']->id, 1, (string) Str::uuid()),
    };
    [$blocked, $waiting, $exit, $message] = review175xRace($holder,
        fn () => app(PrimaryReservations::class)->lockFundingCandidate($campaign->id));
    expect($blocked)->toBeTrue()->and($waiting)->toContain('"business_profiles"')
        ->and([$exit, $message])->toBe($case === 'confirm' ? [0, 'ok'] : [2, 'CAMPAIGN_NOT_FULLY_COMMITTED']);
})->with(['confirm', 'release']);

it('E-R9: a mandate change committed while the funding reader waits is observed after the Business lock', function (): void {
    $this->freezeSecond();
    $raise = review175xRaise(true);
    $campaign = $raise['campaign'];
    $authority = $raise['fixture']['audit']['authority'];
    $authority['terms']['people'][] = ['party_id' => $raise['purchases'][1]['investor']['party']->id, 'name' => 'Newly declared owner', 'roles' => ['beneficial_owner'], 'permissions' => []];
    [$blocked, $waiting, $exit, $message] = review175xRace(fn () => BusinessAuthorityFixture::configure($authority, 1),
        fn () => app(PrimaryReservations::class)->lockFundingCandidate($campaign->id));
    expect($blocked)->toBeTrue()->and($waiting)->toContain('"business_profiles"')
        ->and($exit)->toBe(2)->and($message)->toBe('CONNECTED_BUSINESS_INVESTMENT_PROHIBITED');
});
