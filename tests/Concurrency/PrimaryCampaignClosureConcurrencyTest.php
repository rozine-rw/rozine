<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\GetInvestorWallet;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserveAndConfirmer = function (): Closure {
        $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->sole();
        $version = PrimaryReservationVersion::query()->sole();

        return fn (): array => app(PrimaryCheckout::class)->confirm($this->investor['user']->id, 1, $this->campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    };
    /*
     * Runs $holder inside an open parent transaction, starts $contender in a child, proves it is blocked
     * by the parent and records the evidence, then commits. Returns the child's reported outcome.
     */
    $this->race = function (string $label, Closure $holder, Closure $contender): string {
        $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($channels === false) {
            throw new RuntimeException('Could not create campaign closure barrier.');
        }
        stream_set_timeout($channels[0], 10);
        stream_set_timeout($channels[1], 10);
        DB::disconnect();
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork campaign closure contender.');
        }
        if ($pid === 0) {
            fclose($channels[0]);
            DB::purge();
            try {
                DB::statement("SET lock_timeout = '6s'");
                fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
                if (fgets($channels[1]) !== "go\n") {
                    exit(3);
                }
                $outcome = $contender();
            } catch (Throwable $exception) {
                $outcome = 'threw '.class_basename($exception).' '.$exception->getMessage();
            }
            fwrite($channels[1], $outcome."\n");
            exit(0);
        }
        fclose($channels[1]);
        $evidence = ['case' => $label];
        try {
            $backend = (int) trim((string) fgets($channels[0]));
            DB::beginTransaction();
            $evidence['holder'] = $holder();
            $evidence['holder_pid'] = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
            fwrite($channels[0], "go\n");
            $deadline = hrtime(true) + 4_000_000_000;
            $blocked = false;
            while (hrtime(true) < $deadline && ! $blocked) {
                $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
                if (! $blocked) {
                    usleep(10_000);
                }
            }
            $evidence['contender_blocked_by_holder'] = $blocked;
            $evidence['contender_waiting_lock'] = DB::select('SELECT locktype, relation::regclass::text AS relation, mode, granted FROM pg_locks WHERE pid = ? AND NOT granted', [$backend]);
            $evidence['contender_query'] = DB::selectOne('SELECT wait_event_type, wait_event, query FROM pg_stat_activity WHERE pid = ?', [$backend]);
            $evidence['holder_relation_locks'] = array_map(fn ($row) => $row->relation.':'.$row->mode, DB::select(
                "SELECT relation::regclass::text AS relation, mode FROM pg_locks WHERE pid = pg_backend_pid() AND locktype = 'relation'
                 AND relation::regclass::text IN ('business_profiles', 'business_campaigns', 'business_campaign_closures', 'primary_reservations', 'primary_commitments')
                 ORDER BY 1, 2"));
            DB::commit();
            $evidence['contender_outcome'] = trim((string) fgets($channels[0]));
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            fclose($channels[0]);
            pcntl_waitpid($pid, $status);
        }
        expect(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0)
            ->and($blocked)->toBeTrue($label.': contender was not blocked by the holder')
            ->and($evidence['contender_query']->query)->toContain('"business_profiles"')->toContain('for update');

        return $evidence['contender_outcome'];
    };
});

it('a confirmation queued behind an atomic held release and cancellation is refused after it commits', function (): void {
    $confirm = ($this->reserveAndConfirmer)();
    $outcome = ($this->race)('R1 cancel holds, confirm waits',
        function (): string {
            $root = PrimaryReservationRecord::query()->sole();
            expect(app(PrimaryCheckout::class)->release($this->investor['user']->id, 1, $this->campaign->id,
                $root->id, 1, (string) Str::uuid())['code'])->toBe('RESERVATION_RELEASED');

            return app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
                $this->campaign->id, 1, null, (string) Str::uuid())['code'];
        },
        fn (): string => $confirm()['code']);
    expect($outcome)->toBe('VERSION_CONFLICT')
        ->and(BusinessCampaignClosure::query()->count())->toBe(1)
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0)
        ->and(app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown']['held']['amount'])->toBe('0')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1)
        ->and(DB::table('primary_campaign_closure_returns')->count())->toBe(1)
        ->and(app(PrimaryCheckout::class)->reserve($this->investor['user']->id, 1, $this->campaign->id, '1',
            (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('CAMPAIGN_CLOSED');
});

it('an expiry sweep queued behind an uncommitted confirmation defers settlement', function (): void {
    $this->travelTo($this->campaign->expires_at->subSeconds(60));
    $confirm = ($this->reserveAndConfirmer)();
    $this->travelTo($this->campaign->expires_at->subSeconds(30));
    $exposure = app(BusinessExposureStore::class)->current($this->campaign->business_id);
    $outcome = ($this->race)('R2 confirm holds, expiry sweep waits',
        fn (): string => $confirm()['code'],
        function (): string {
            $this->travelTo($this->campaign->expires_at);

            return 'expired '.app(BusinessCampaignStore::class)->expireDue(100);
        });
    expect($outcome)->toBe('expired 0')
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($exposure);
});

it('a confirmation queued behind an uncommitted expiry (skewed clock) is refused after it commits', function (): void {
    $this->travelTo($this->campaign->expires_at->subSeconds(60));
    $confirm = ($this->reserveAndConfirmer)();
    $this->travelTo($this->campaign->expires_at);
    $outcome = ($this->race)('R3 expiry holds, confirm waits',
        function (): string {
            $root = PrimaryReservationRecord::query()->sole();
            expect(app(PrimaryReservations::class)->expire($this->campaign->id, $root->id))->not->toBeNull();

            return 'expired '.app(BusinessCampaignStore::class)->expireDue(100);
        },
        function () use ($confirm): string {
            $this->travelTo($this->campaign->expires_at->subSeconds(30));

            return $confirm()['code'];
        });
    expect($outcome)->toBe('VERSION_CONFLICT')
        ->and(BusinessCampaignClosure::query()->sole()->phase)->toBe('expired')
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->sole()->result['code'])->toBe('VERSION_CONFLICT')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1)
        ->and(DB::table('primary_campaign_closure_returns')->count())->toBe(1);
});

it('refuses direct cash-return closure under snapshot isolation even without any roots', function (string $isolation): void {
    DB::beginTransaction();
    try {
        DB::statement('SET TRANSACTION ISOLATION LEVEL '.$isolation);
        expect(fn () => DB::transaction(fn () => BusinessCampaignClosure::factory()->create(['business_campaign_id' => $this->campaign->id])))
            ->toThrow(QueryException::class, 'requires READ COMMITTED');
    } finally {
        DB::rollBack();
    }
    expect(BusinessCampaignClosure::query()->count())->toBe(0);
})->with(['REPEATABLE READ', 'SERIALIZABLE']);

it('verifies a committed returned closure from a read-only transaction without locking shared wallets', function (): void {
    ($this->reserveAndConfirmer)();
    $root = PrimaryReservationRecord::query()->sole();
    $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $root->id, 1, (string) Str::uuid());
    app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, null, (string) Str::uuid());
    DB::beginTransaction();
    try {
        DB::statement('SET TRANSACTION READ ONLY');
        expect(app(CampaignClosureEvidence::class)->find($this->campaign->id)['phase'])->toBe('cancelled');
    } finally {
        DB::rollBack();
    }
});

it('commits returned closure while its Investor Party is locked by an unrelated command', function (): void {
    ($this->reserveAndConfirmer)();
    $root = PrimaryReservationRecord::query()->sole();
    expect($this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $root->id, 1, (string) Str::uuid())['code'])
        ->toBe('RESERVATION_RELEASED');
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not open the Investor Party closure barrier.');
    }
    stream_set_timeout($channels[0], 8);
    stream_set_timeout($channels[1], 8);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork returned closure.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '6s'");
            DB::statement("SET statement_timeout = '8s'");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $result = app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
                $this->campaign->id, 1, null, (string) Str::uuid());
            fwrite($channels[1], $result['code']."\n");
            exit(0);
        } catch (Throwable) {
            exit(1);
        }
    }
    fclose($channels[1]);
    $reaped = false;
    $status = 0;
    try {
        DB::beginTransaction();
        DB::table('parties')->where('id', $this->investor['party']->id)->lockForUpdate()->sole();
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 4_000_000_000;
        while (! $reaped && hrtime(true) < $deadline) {
            $reaped = pcntl_waitpid($pid, $status, WNOHANG) === $pid;
            if (! $reaped) {
                usleep(10_000);
            }
        }
        expect($reaped)->toBeTrue('Closure must not acquire the Investor Party after its wallet')
            ->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0)
            ->and(trim((string) fgets($channels[0])))->toBe('CAMPAIGN_CANCELLED');
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        if (! $reaped) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
    }
    expect(BusinessCampaignClosure::query()->count())->toBe(1)
        ->and(DB::table('primary_campaign_closure_returns')->count())->toBe(1)
        ->and(LedgerEntry::query()->where('source_id', $root->id)->count())->toBe(2);
});
