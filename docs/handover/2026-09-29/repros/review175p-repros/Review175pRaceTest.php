<?php

declare(strict_types=1);

/*
 * Review race repros for PR #175 range 12466d27..f0de47b8 (K4 application gate). Copy into tests/Concurrency/.
 * Each case holds one side uncommitted in the parent, starts the contender in a forked child on its own
 * connection, proves the contender waits on the parent through pg_blocking_pids/pg_locks/pg_stat_activity,
 * then commits and checks the contender's retained outcome. Lock evidence is appended to REVIEW_LOCK_LOG.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Wallet\GetInvestorWallet;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
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
        stream_set_timeout($channels[0], 10);
        stream_set_timeout($channels[1], 10);
        DB::disconnect();
        $pid = pcntl_fork();
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
        if (($log = getenv('REVIEW_LOCK_LOG')) !== false) {
            file_put_contents($log, json_encode($evidence, JSON_PRETTY_PRINT)."\n", FILE_APPEND);
        }
        expect($blocked)->toBeTrue($label.': contender was not blocked by the holder')
            ->and($evidence['contender_query']->query)->toContain('"business_profiles"')->toContain('for update');

        return $evidence['contender_outcome'];
    };
});

it('R1: a confirmation queued behind an uncommitted cancellation is refused after it commits', function (): void {
    $confirm = ($this->reserveAndConfirmer)();
    $outcome = ($this->race)('R1 cancel holds, confirm waits',
        fn (): string => app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
            $this->campaign->id, 1, null, (string) Str::uuid())['code'],
        fn (): string => $confirm()['code']);
    expect($outcome)->toBe('CAMPAIGN_CLOSED')
        ->and(BusinessCampaignClosure::query()->count())->toBe(1)
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0)
        ->and(app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown']['held']['amount'])->toBe('5000');
});

it('R2: an expiry sweep queued behind an uncommitted confirmation refuses the committed campaign', function (): void {
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
    expect($outcome)->toBe('threw CommandRejection CAMPAIGN_SETTLEMENT_REQUIRED')
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($exposure);
});

it('R3: a confirmation queued behind an uncommitted expiry (skewed clock) is refused after it commits', function (): void {
    $this->travelTo($this->campaign->expires_at->subSeconds(60));
    $confirm = ($this->reserveAndConfirmer)();
    $this->travelTo($this->campaign->expires_at);
    $outcome = ($this->race)('R3 expiry holds, confirm waits',
        fn (): string => 'expired '.app(BusinessCampaignStore::class)->expireDue(100),
        function () use ($confirm): string {
            $this->travelTo($this->campaign->expires_at->subSeconds(30));

            return $confirm()['code'];
        });
    expect($outcome)->toBe('CAMPAIGN_CLOSED')
        ->and(BusinessCampaignClosure::query()->sole()->phase)->toBe('expired')
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->sole()->result['code'])->toBe('CAMPAIGN_CLOSED');
});
