<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Identity\SelectActiveRole;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Domain\Identity\IdentityViolation;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\InvestorWallet;
use App\Models\PrimaryReservationRecord;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('retains caller authority through the outer commit including receipt lookup', function (string $action): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $request = (string) Str::uuid();
    $checkout = app(PrimaryCheckout::class);
    if ($action === 'lookup') {
        $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', $request, PrimaryReservationFixture::terms(...));
    }
    config(['database.connections.primary_caller_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('primary_caller_observer');
    $targets = ['business_profiles' => $campaign->business_id, 'users' => $investor['user']->id, 'parties' => $investor['party']->id];
    try {
        DB::beginTransaction();
        $result = $action === 'lookup'
            ? $checkout->findReservation($investor['user']->id, 1, $campaign->id, $request)
            : $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', $request, PrimaryReservationFixture::terms(...));
        expect($result['code'])->toBe('RESERVATION_HELD');
        if ($action === 'reserve') {
            $targets['business_campaigns'] = $campaign->id;
            $targets['investor_wallets'] = InvestorWallet::query()->where('party_id', $investor['party']->id)->value('id');
        }
        foreach ($targets as $table => $id) {
            expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                ->toThrow(QueryException::class, 'could not obtain lock');
        }
        DB::commit();
        foreach ($targets as $table => $id) {
            expect($observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first())?->id)->toBe($id);
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('primary_caller_observer');
    }
})->with(['reserve', 'lookup']);

it('waits for Business before locking a shared Investor and Business actor so cancellation can finish', function (): void {
    $this->freezeSecond();
    $campaign = PrimaryReservationFixture::campaign();
    $actor = User::query()->findOrFail($campaign->actor_user_id);
    RoleMembership::factory()->active()->create(['party_id' => $actor->party_id, 'role' => 'investor']);
    app(SelectActiveRole::class)->handle($actor->id, 'investor', 1, (string) Str::uuid());
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create caller lock barrier.');
    }
    stream_set_timeout($channels[0], 8);
    stream_set_timeout($channels[1], 8);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork the Primary caller.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '5s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            app(PrimaryCheckout::class)->reserve($actor->id, 2, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
            exit(4);
        } catch (IdentityViolation $exception) {
            exit($exception->getMessage() === 'ACTIVE_ROLE_REVISION_CONFLICT' ? 0 : 5);
        } catch (Throwable) {
            exit(6);
        }
    }
    fclose($channels[1]);
    $childStatus = null;
    try {
        $backend = trim((string) fgets($channels[0]));
        expect(ctype_digit($backend))->toBeTrue();
        DB::beginTransaction();
        DB::statement("SET LOCAL lock_timeout = '1s'");
        DB::table('business_profiles')->where('id', $campaign->business_id)->lock('FOR NO KEY UPDATE')->first();
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 3_000_000_000;
        $blocked = false;
        while (hrtime(true) < $deadline) {
            $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
            if ($blocked) {
                break;
            }
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                $childStatus = $status;
                break;
            }
            usleep(10000);
        }
        expect($blocked)->toBeTrue('Checkout must wait on Business before acquiring the shared actor.');
        expect(DB::table('users')->where('id', $actor->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($actor->id)
            ->and(DB::table('parties')->where('id', $actor->party_id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($actor->party_id)
            ->and(DB::table('business_campaigns')->where('id', $campaign->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($campaign->id);
        app(SelectActiveRole::class)->handle($actor->id, 'business', 2, (string) Str::uuid());
        $cancelled = app(BusinessCampaignStore::class)->cancel($actor->id, 3, $campaign->business_id, $campaign->id, 1, 'Race proof.', (string) Str::uuid());
        expect($cancelled['code'])->toBe('CAMPAIGN_CANCELLED');
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
    expect(pcntl_wifexited($childStatus))->toBeTrue()->and(pcntl_wexitstatus($childStatus))->toBe(0)
        ->and(BusinessCampaignClosure::query()->where('business_campaign_id', $campaign->id)->count())->toBe(1)
        ->and(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.reserve')->count())->toBe(0);
});
