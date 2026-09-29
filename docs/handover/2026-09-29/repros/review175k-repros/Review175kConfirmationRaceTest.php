<?php

declare(strict_types=1);

/*
 * Review repros for PR #175 range 2271131e..85b68691. Copy into tests/Concurrency/ and run on PostgreSQL.
 *
 * (a) A confirm that is blocked on the Business row while a signatory cancels must, once released,
 *     record a journaled CAMPAIGN_CLOSED and leave the hold untouched. The wait is proven from
 *     pg_stat_activity/pg_locks, not timing, and User/Party/campaign/wallet are shown to be free.
 * (b) A requote racing a matching confirmation (distinct keys) has exactly one winner; the loser gets
 *     VERSION_CONFLICT, and cash moves at most once.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('makes a waiting confirmation observe a committed cancellation as a journaled closure', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $signatory = User::query()->findOrFail($campaign->actor_user_id);
    $investor = PrimaryReservationFixture::investor();
    app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $version = PrimaryReservationVersion::query()->sole();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 8);
    stream_set_timeout($channels[1], 8);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '5s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $result = app(PrimaryCheckout::class)->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
            exit($result['status'] === 'rejected' && $result['code'] === 'CAMPAIGN_CLOSED' ? 0 : 4);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
            exit(6);
        }
    }
    fclose($channels[1]);
    $childStatus = null;
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        DB::beginTransaction();
        DB::statement("SET LOCAL lock_timeout = '1s'");
        DB::table('business_profiles')->where('id', $campaign->business_id)->lock('FOR NO KEY UPDATE')->first();
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 3_000_000_000;
        $waiting = null;
        while (hrtime(true) < $deadline) {
            $waiting = DB::selectOne("SELECT wait_event_type, pg_backend_pid() = ANY(pg_blocking_pids(pid)) AS blocked_by_me,
                (SELECT string_agg(DISTINCT l.relation::regclass::text, ',') FROM pg_locks l WHERE l.pid = a.pid AND l.locktype = 'tuple') AS tuple_relations
                FROM pg_stat_activity a WHERE pid = ?", [$backend]);
            if ($waiting?->wait_event_type === 'Lock' && $waiting->blocked_by_me) {
                break;
            }
            usleep(10000);
        }
        expect($waiting?->wait_event_type)->toBe('Lock')->and($waiting->blocked_by_me)->toBeTrue()
            ->and($waiting->tuple_relations)->toBe('business_profiles');
        expect(DB::table('users')->where('id', $investor['user']->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($investor['user']->id)
            ->and(DB::table('parties')->where('id', $investor['party']->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($investor['party']->id)
            ->and(DB::table('primary_reservations')->where('id', $root->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($root->id)
            ->and(DB::table('investor_wallets')->where('party_id', $investor['party']->id)->lock('FOR UPDATE NOWAIT')->first()?->party_id)->toBe($investor['party']->id);
        expect(app(BusinessCampaignStore::class)->cancel($signatory->id, 1, $campaign->business_id, $campaign->id, 1, 'Race.', (string) Str::uuid())['code'])
            ->toBe('CAMPAIGN_CANCELLED');
        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($childStatus === null) {
            pcntl_waitpid($pid, $childStatus);
        }
        fclose($channels[0]);
    }
    expect(pcntl_wifexited($childStatus) ? pcntl_wexitstatus($childStatus) : -1)->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->sole()->result['code'])->toBe('CAMPAIGN_CLOSED');
});

it('gives a racing requote and confirmation exactly one winner', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $version = PrimaryReservationVersion::query()->sole();
    $children = [];
    DB::disconnect();
    foreach (['requote', 'confirm'] as $role) {
        $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_timeout($channels[0], 8);
        stream_set_timeout($channels[1], 8);
        $pid = pcntl_fork();
        if ($pid === 0) {
            fclose($channels[0]);
            DB::purge();
            try {
                DB::statement("SET lock_timeout = '5s'");
                fwrite($channels[1], "ready\n");
                if (fgets($channels[1]) !== "go\n") {
                    exit(3);
                }
                $admit = $role === 'confirm' ? PrimaryReservationFixture::terms(...) : function (UnitRights $rights, array $campaign): PrimaryTerms {
                    $terms = PrimaryReservationFixture::terms($rights, $campaign);

                    return PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion, 'synthetic-disclosure-2', $terms->earningsFee, $terms->payoutFee, $rights);
                };
                $result = app(PrimaryCheckout::class)->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                    $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), $admit);
                exit(match ($result['code']) {
                    'RESERVATION_CONFIRMED' => 10, 'RESERVATION_REQUOTED' => 11, 'VERSION_CONFLICT' => 12, default => 13
                });
            } catch (Throwable) {
                exit(14);
            }
        }
        fclose($channels[1]);
        $children[$pid] = $channels[0];
    }
    foreach ($children as $channel) {
        expect(fgets($channel))->toBe("ready\n");
    }
    foreach ($children as $channel) {
        fwrite($channel, "go\n");
    }
    $results = [];
    foreach ($children as $pid => $channel) {
        pcntl_waitpid($pid, $status);
        $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
        fclose($channel);
    }
    sort($results);
    expect(in_array($results, [[10, 12], [11, 12]], true))->toBeTrue('results '.json_encode($results))
        ->and(PrimaryReservationVersion::query()->count())->toBe(2)
        ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(PrimaryCommitment::query()->count())
        ->and(PrimaryCommitment::query()->count())->toBeLessThanOrEqual(1);
});
