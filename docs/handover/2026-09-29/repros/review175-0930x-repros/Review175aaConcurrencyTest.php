<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\PrimaryCampaignFunding;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * Review repros for #175 range 33ba9040..3bb74427 (durable funding lock): real forks, pg_blocking_pids, pg_stat_database.deadlocks.
 * Copy into tests/Concurrency/. RACE: parent holds one side in an open transaction, the child runs the other.
 */

/** @return array{campaign: BusinessCampaign, purchases: list<array{investor: array<string, mixed>, root: PrimaryReservationRecord, version: PrimaryReservationVersion}>} */
function r175aaRaise(bool $confirmSecond = true): array
{
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    $purchases = [];
    foreach ([true, $confirmSecond] as $confirm) {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        if ($confirm) {
            expect(r175aaConfirm($campaign, ['investor' => $investor, 'root' => $root, 'version' => $version]))->toBe('RESERVATION_CONFIRMED');
        }
        $purchases[] = ['investor' => $investor, 'root' => $root, 'version' => $version];
    }

    return ['campaign' => $campaign, 'purchases' => $purchases];
}

/** @param array{investor: array<string, mixed>, root: PrimaryReservationRecord, version: PrimaryReservationVersion} $purchase */
function r175aaConfirm(BusinessCampaign $campaign, array $purchase): string
{
    return app(PrimaryCheckout::class)->confirm($purchase['investor']['user']->id, 1, $campaign->id, $purchase['root']->id, 1,
        $purchase['version']->payload['terms']['disclosure_version'], $purchase['version']->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
}

/** @return array<string, mixed> */
function r175aaAdmission(BusinessCampaign $campaign, string $label = 'review fixture'): array
{
    return ['campaign_id' => $campaign->id, 'publication_sha256' => $campaign->sha256,
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => $label]])];
}

function r175aaDeadlocks(): int
{
    DB::statement('SELECT pg_stat_clear_snapshot()');

    return (int) DB::selectOne('SELECT deadlocks FROM pg_stat_database WHERE datname = current_database()')->deadlocks;
}

function r175aaDeadlocksSince(int $before): int
{
    $deadline = microtime(true) + 2.0;
    do {
        $delta = r175aaDeadlocks() - $before;
        if ($delta > 0) {
            return $delta;
        }
        usleep(100_000);
    } while (microtime(true) < $deadline);

    return 0;
}

/** @return array{blocked: bool, waiting: string, exit: int, message: string, deadlocks: int} */
function r175aaRace(Closure $holder, Closure $contender, bool $commit = true): array
{
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 25);
    stream_set_timeout($channels[1], 25);
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
            @fwrite($channels[1], $exception::class.' '.mb_substr(preg_replace('/\s+/', ' ', $exception->getMessage()), 0, 200)."\n");
            $code = 1;
        } finally {
            exit($code);
        }
    }
    fclose($channels[1]);
    $child = (int) trim((string) fgets($channels[0]));
    DB::purge();
    $before = r175aaDeadlocks();
    $blocked = false;
    $waiting = '';
    $status = 0;
    $message = '';
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
        pcntl_waitpid($pid, $status);
        DB::purge();
    }

    return ['blocked' => $blocked, 'waiting' => mb_substr(preg_replace('/\s+/', ' ', $waiting), 0, 90), 'exit' => pcntl_wexitstatus($status), 'message' => $message,
        'deadlocks' => r175aaDeadlocksSince($before)];
}

function r175aaLog(string $label, array $result): void
{
    fwrite(STDERR, "\n[$label] ".json_encode($result)."\n");
}

it('AC-1 two funding locks with different evidence: the waiter returns the winner\'s retained evidence', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign] = r175aaRaise();
    $result = r175aaRace(
        fn () => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign, 'winner')),
        fn (): string => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign, 'waiter'))['admission']['policy']['evidence']['synthetic']);
    r175aaLog('AC-1', $result);
    expect($result['blocked'])->toBeTrue()->and($result['waiting'])->toContain('"business_profiles"')
        ->and([$result['exit'], $result['message'], $result['deadlocks']])->toBe([0, 'winner', 0])
        ->and(PrimaryCampaignFunding::query()->count())->toBe(1)->and(DB::table('primary_funding_commitments')->count())->toBe(2);
});

it('AC-2 funding vs voluntary cancel, both winners', function (bool $fundingFirst): void {
    $this->freezeSecond();
    ['campaign' => $campaign] = r175aaRaise();
    $fund = fn () => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign))['funding_id'];
    $cancel = fn (): string => app(BusinessCampaignStore::class)->cancel($campaign->actor_user_id, 1, $campaign->business_id, $campaign->id, 1, null, (string) Str::uuid())['code'];
    $result = $fundingFirst ? r175aaRace($fund, $cancel) : r175aaRace($cancel, $fund);
    r175aaLog('AC-2 '.($fundingFirst ? 'funding first' : 'cancel first'), $result);
    expect($result['blocked'])->toBeTrue()->and($result['deadlocks'])->toBe(0)->and($result['exit'])->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)->and(PrimaryCampaignFunding::query()->count())->toBe(1);
    if ($fundingFirst) {
        expect($result['message'])->toBe('CAMPAIGN_FUNDED');
    }
})->with(['funding first' => true, 'cancel first' => false]);

it('AC-3 funding vs campaigns:expire after the deadline, both winners', function (bool $fundingFirst): void {
    $this->freezeSecond();
    ['campaign' => $campaign] = r175aaRaise();
    $this->travelTo($campaign->expires_at->addHour());
    $fund = fn () => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign))['funding_id'];
    $expire = fn (): string => (string) app(BusinessCampaignStore::class)->expireDue(10);
    $result = $fundingFirst ? r175aaRace($fund, $expire) : r175aaRace($expire, $fund);
    r175aaLog('AC-3 '.($fundingFirst ? 'funding first' : 'expire first'), $result);
    expect($result['deadlocks'])->toBe(0)->and($result['exit'])->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)->and(PrimaryCampaignFunding::query()->count())->toBe(1);
    if ($fundingFirst) {
        expect($result['blocked'])->toBeTrue()->and($result['message'])->toBe('0');
    }
})->with(['funding first' => true, 'expire first' => false]);

it('AC-4 an unfunded expiry that wins closes the campaign and funding is then refused', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $this->travelTo($campaign->expires_at);
    $result = r175aaRace(fn () => expect(app(BusinessCampaignStore::class)->expireDue(10))->toBe(1),
        fn () => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign)));
    r175aaLog('AC-4', $result);
    expect($result['blocked'])->toBeTrue()->and([$result['exit'], $result['message'], $result['deadlocks']])->toBe([2, 'CAMPAIGN_CLOSED', 0])
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0);
});

it('AC-5 a last confirmation in flight wins, then funding sees the complete raise', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'purchases' => [, $second]] = r175aaRaise(false);
    $result = r175aaRace(fn () => expect(r175aaConfirm($campaign, $second))->toBe('RESERVATION_CONFIRMED'),
        fn (): string => (string) count(app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign))['commitments']));
    r175aaLog('AC-5 confirm first', $result);
    expect($result['blocked'])->toBeTrue()->and($result['waiting'])->toContain('"business_profiles"')
        ->and([$result['exit'], $result['message'], $result['deadlocks']])->toBe([0, '2', 0])
        ->and(PrimaryCampaignFunding::query()->count())->toBe(1);
});

it('AC-6 a last confirmation that rolls back leaves funding refused as not fully committed', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'purchases' => [, $second]] = r175aaRaise(false);
    $result = r175aaRace(fn () => expect(r175aaConfirm($campaign, $second))->toBe('RESERVATION_CONFIRMED'),
        fn () => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign)), commit: false);
    r175aaLog('AC-6 confirm rolled back', $result);
    expect($result['blocked'])->toBeTrue()->and([$result['exit'], $result['message'], $result['deadlocks']])->toBe([2, 'CAMPAIGN_NOT_FULLY_COMMITTED', 0])
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0)->and(PrimaryCommitment::query()->count())->toBe(1);
});

it('AC-7 funding in flight refuses a new reserve and a repeat confirm once it commits', function (string $command): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'purchases' => [$first]] = r175aaRaise();
    $stranger = PrimaryReservationFixture::investor();
    $contender = $command === 'reserve'
        ? fn (): string => app(PrimaryCheckout::class)->reserve($stranger['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code']
        : fn (): string => r175aaConfirm($campaign, $first);
    $result = r175aaRace(fn () => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign)), $contender);
    r175aaLog('AC-7 '.$command, $result);
    expect($result['blocked'])->toBeTrue()->and($result['deadlocks'])->toBe(0)->and($result['exit'])->toBe(0)
        ->and(PrimaryReservationRecord::query()->count())->toBe(2)->and(PrimaryCommitment::query()->count())->toBe(2)
        ->and(PrimaryCampaignFunding::query()->count())->toBe(1);
})->with(['reserve', 'confirm']);

it('AC-8 funding vs primary:expire-reservations on an overdue hold', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'purchases' => [, $second]] = r175aaRaise(false);
    $this->travelTo($second['root']->expires_at);
    $result = r175aaRace(fn () => expect(app(PrimaryReservations::class)->expireDue(10))->toBe(1),
        fn () => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign)));
    r175aaLog('AC-8 sweep first', $result);
    expect($result['blocked'])->toBeTrue()->and([$result['exit'], $result['message'], $result['deadlocks']])->toBe([2, 'CAMPAIGN_NOT_FULLY_COMMITTED', 0])
        ->and(PrimaryCampaignFunding::query()->count())->toBe(0)
        ->and(PrimaryReservationVersion::query()->where('state', 'expired')->count())->toBe(1);
});

it('AC-9 a funded campaign and the two sweeps run concurrently without waiting on each other forever', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign] = r175aaRaise();
    $this->travelTo($campaign->expires_at->addHour());
    $result = r175aaRace(fn () => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => r175aaAdmission($campaign)),
        fn (): string => app(PrimaryReservations::class)->expireDue(10).'/'.app(BusinessCampaignStore::class)->expireDue(10));
    r175aaLog('AC-9', $result);
    expect([$result['exit'], $result['message'], $result['deadlocks']])->toBe([0, '0/0', 0])->and(PrimaryCampaignFunding::query()->count())->toBe(1);
});

/*
 * INSTALL: a real command is paused right after its journal row (RowExclusive on command_operations), the install
 * starts, and the command is released once the install is seen waiting (or after 1.5s if it never waits).
 * Safe outcome: exits [0, 0] and no deadlock.
 */
/** @return array{int, resource, int} */
function r175aaFork(Closure $work): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 30);
    stream_set_timeout($channels[1], 30);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '20s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $work($channels[1]);
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, 'r175aa child '.getmypid().': '.$exception::class.' '.mb_substr(preg_replace('/\s+/', ' ', $exception->getMessage()), 0, 330)."\n");
            exit(str_contains($exception->getMessage(), '40P01') ? 2 : 1);
        }
    }
    fclose($channels[1]);

    return [$pid, $channels[0], (int) trim((string) fgets($channels[0]))];
}

function r175aaMigration(): object
{
    $path = database_path('migrations/2026_09_30_054318_create_primary_campaign_fundings.php');
    if (getenv('R175AA_FIX') !== '1') {
        return require $path;
    }
    /* Candidate fix: one up-front lock in the order commands write; the journal row is completed last, so it is locked last. */
    $current = 'LOCK TABLE business_profiles, business_campaigns, primary_reservations, primary_commitments, investor_wallets, ledger_entries IN SHARE ROW EXCLUSIVE MODE';
    $source = file_get_contents($path);
    if (substr_count($source, $current) !== 1) {
        throw new RuntimeException('install lock statement changed');
    }
    $fixed = sys_get_temp_dir().'/r175aa_fixed_install.php';
    file_put_contents($fixed, str_replace($current, 'LOCK TABLE business_profiles, business_campaigns, primary_reservations, primary_reservation_versions, business_campaign_closures, '
        .'primary_commitments, investor_wallets, ledger_entries, command_operations IN SHARE ROW EXCLUSIVE MODE', $source));

    return require $fixed;
}

it('INSTALL vs an in-flight command paused after its journal row', function (string $command, string $pauseAfter): void {
    $this->freezeSecond();
    /* In-flight commands during a deploy run the PREVIOUS release, which never reads the funding table. */
    app()->instance(CampaignFundingEvidence::class, new class implements CampaignFundingEvidence
    {
        public function find(string $campaignId): ?array
        {
            return null;
        }
    });
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $held = null;
    if ($command !== 'reserve' && $command !== 'cancel') {
        $id = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['data']['reservation_id'];
        $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $id)->sole();
    }
    r175aaMigration()->down();
    DB::disconnect();
    [$commandPid, $commandChannel, $commandBackend] = r175aaFork(function ($channel) use ($command, $pauseAfter, $checkout, $investor, $campaign, $held): void {
        $paused = false;
        DB::listen(function (QueryExecuted $query) use ($channel, &$paused, $pauseAfter): void {
            if (! $paused && str_starts_with($query->sql, $pauseAfter)) {
                $paused = true;
                fwrite($channel, "paused\n");
                if (fgets($channel) !== "go\n") {
                    throw new RuntimeException('barrier lost');
                }
            }
        });
        $code = match ($command) {
            'reserve' => $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'],
            'confirm' => $checkout->confirm($investor['user']->id, 1, $campaign->id, $held->primary_reservation_id, 1, $held->payload['terms']['disclosure_version'],
                $held->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'],
            'release' => $checkout->release($investor['user']->id, 1, $campaign->id, $held->primary_reservation_id, 1, (string) Str::uuid())['code'],
            'cancel' => app(BusinessCampaignStore::class)->cancel($campaign->actor_user_id, 1, $campaign->business_id, $campaign->id, 1, null, (string) Str::uuid())['code'],
        };
        fwrite(STDERR, "r175aa $command result: $code\n");
        if (! in_array($code, ['RESERVATION_HELD', 'RESERVATION_CONFIRMED', 'RESERVATION_RELEASED', 'CAMPAIGN_CANCELLED'], true)) {
            throw new RuntimeException('command '.$code);
        }
    });
    [$migrationPid, $migrationChannel, $migrationBackend] = r175aaFork(fn () => r175aaMigration()->up());
    DB::purge();
    $before = r175aaDeadlocks();
    $exits = [];
    $migrationWaitsOn = 'never waited';
    $commandWaitsOn = 'never waited';
    try {
        fwrite($commandChannel, "go\n");
        $paused = trim((string) fgets($commandChannel)) === 'paused';
        fwrite($migrationChannel, "go\n");
        $deadline = microtime(true) + 1.5;
        while (microtime(true) < $deadline) {
            if ((bool) DB::selectOne('SELECT ? = ANY(pg_blocking_pids(?)) AS ok', [$commandBackend, $migrationBackend])->ok) {
                $lock = DB::selectOne('SELECT relation::regclass::text AS relation, mode FROM pg_locks WHERE pid = ? AND NOT granted LIMIT 1', [$migrationBackend]);
                $granted = DB::select("SELECT relation::regclass::text AS relation FROM pg_locks WHERE pid = ? AND granted AND mode = 'ShareRowExclusiveLock' AND locktype = 'relation'", [$migrationBackend]);
                $migrationWaitsOn = ($lock->relation ?? '?').' '.($lock->mode ?? '?').' (holding SRE on '.implode(',', array_column($granted, 'relation')).')';
                break;
            }
            usleep(10000);
        }
        @fwrite($commandChannel, "go\n");
        $commandWaitsOn = 'never waited';
        $deadline = microtime(true) + 0.8;
        while (microtime(true) < $deadline) {
            $lock = DB::selectOne("SELECT relation::regclass::text AS relation, mode FROM pg_locks WHERE pid = ? AND NOT granted AND locktype = 'relation' LIMIT 1", [$commandBackend]);
            if ($lock !== null) {
                $commandWaitsOn = $lock->relation.' '.$lock->mode;
                break;
            }
            usleep(5000);
        }
        foreach ([$commandPid, $migrationPid] as $pid) {
            pcntl_waitpid($pid, $status);
            $exits[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
        }
    } finally {
        fclose($commandChannel);
        fclose($migrationChannel);
        foreach ([$commandPid, $migrationPid] as $pid) {
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        if ((int) DB::selectOne("SELECT count(*) AS n FROM pg_tables WHERE tablename = 'primary_campaign_fundings'")->n === 0) {
            r175aaMigration()->up();
        }
    }
    $deadlocks = r175aaDeadlocksSince($before);
    fwrite(STDERR, sprintf("\n[INSTALL vs %s paused after %s] paused=%s install waits on: %s | command then waits on: %s | exits(command,migration)=%s deadlocks=%d (2 = deadlock victim)\n",
        $command, $pauseAfter, var_export($paused, true), $migrationWaitsOn, $commandWaitsOn ?? '?', json_encode($exits), $deadlocks));
    expect($paused)->toBeTrue()->and(['exits' => $exits, 'deadlocks' => $deadlocks])->toBe(['exits' => [0, 0], 'deadlocks' => 0]);
})->with([
    ['reserve', 'insert into "command_operations"'],
    ['confirm', 'insert into "command_operations"'],
    ['confirm', 'insert into "primary_reservation_versions"'],
    ['release', 'insert into "command_operations"'],
    ['release', 'insert into "primary_reservation_versions"'],
    ['cancel', 'insert into "command_operations"'],
    ['cancel', 'insert into "business_campaign_closures"'],
]);
