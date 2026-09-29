<?php

declare(strict_types=1);

/*
 * Review repro for PR #175 range ad598a09..2271131e. Copy into tests/Concurrency/ and run on PostgreSQL.
 *
 * Distinct-actor variant of PrimaryCheckoutLockTest's race. A different Business signatory cancels,
 * so the Investor's context revision does not change. The waiting purchase therefore gets past
 * authority, and it must observe the committed closure (a journaled CAMPAIGN_CLOSED refusal), not a
 * revision conflict. The wait itself is proven from pg_stat_activity and pg_locks, not from timing.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('makes a waiting purchase observe a distinct signatory cancellation as a journaled closure', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $signatory = User::query()->findOrFail($campaign->actor_user_id);
    $investor = PrimaryReservationFixture::investor();
    $request = (string) Str::uuid();
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
            $result = app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', $request, PrimaryReservationFixture::terms(...));
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
        expect($waiting?->wait_event_type)->toBe('Lock')
            ->and($waiting->blocked_by_me)->toBeTrue()
            ->and($waiting->tuple_relations)->toBe('business_profiles');
        expect(DB::table('users')->where('id', $investor['user']->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($investor['user']->id)
            ->and(DB::table('parties')->where('id', $investor['party']->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($investor['party']->id)
            ->and(DB::table('business_campaigns')->where('id', $campaign->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($campaign->id)
            ->and(DB::table('investor_wallets')->where('party_id', $investor['party']->id)->lock('FOR UPDATE NOWAIT')->first()?->party_id)->toBe($investor['party']->id);
        $cancelled = app(BusinessCampaignStore::class)->cancel($signatory->id, 1, $campaign->business_id, $campaign->id, 1, 'Distinct actor race.', (string) Str::uuid());
        expect($cancelled['code'])->toBe('CAMPAIGN_CANCELLED');
        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        pcntl_waitpid($pid, $childStatus);
        fclose($channels[0]);
    }
    expect(pcntl_wexitstatus($childStatus))->toBe(0)
        ->and(BusinessCampaignClosure::query()->where('business_campaign_id', $campaign->id)->count())->toBe(1)
        ->and(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.reserve')->sole()->result['code'])->toBe('CAMPAIGN_CLOSED')
        ->and(app(PrimaryCheckout::class)->findReservation($investor['user']->id, 1, $campaign->id, $request)['code'])->toBe('CAMPAIGN_CLOSED');
});
