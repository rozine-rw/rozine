<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * Review #175 (A): a real forked sweep blocked behind one externally held row lock. pg_locks and
 * NOWAIT probes show which rows the sweep already holds at that moment, proving
 * Business -> campaign -> root -> wallet and that candidate selection takes no row locks.
 */
it('PROBE sweep lock order under a blocker on each tier', function (string $blocked): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $this->travelTo($root->expires_at);
    $rows = ['business_profiles' => $campaign->business_id, 'business_campaigns' => $campaign->id, 'primary_reservations' => $root->id,
        'investor_wallets' => InvestorWallet::query()->where('party_id', $investor['party']->id)->value('id')];
    config(['database.connections.r175w_blocker' => config('database.connections.pgsql'), 'database.connections.r175w_probe' => config('database.connections.pgsql')]);
    DB::disconnect();
    $blocker = DB::connection('r175w_blocker');
    $blocker->beginTransaction();
    $blocker->table($blocked)->where('id', $rows[$blocked])->lockForUpdate()->first();
    $pid = pcntl_fork();
    if ($pid === 0) {
        DB::purge();
        try {
            DB::statement("SET application_name = 'r175w_sweep'");
            DB::statement("SET lock_timeout = '15s'");
            exit(app(PrimaryReservations::class)->expireDue(1) === 1 ? 0 : 2);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
            exit(4);
        }
    }
    $probe = DB::connection('r175w_probe');
    $waiting = null;
    for ($i = 0; $i < 200 && $waiting === null; $i++) {
        usleep(25000);
        $waiting = $probe->selectOne("SELECT a.pid, a.wait_event_type, a.query FROM pg_stat_activity a WHERE a.application_name = 'r175w_sweep' AND a.wait_event_type = 'Lock'");
    }
    $locks = $waiting === null ? [] : $probe->select('SELECT locktype, relation::regclass::text AS rel, mode, granted FROM pg_locks WHERE pid = ? ORDER BY granted, locktype', [$waiting->pid]);
    $held = [];
    foreach ($rows as $table => $id) {
        if ($table === $blocked) {
            continue;
        }
        try {
            $probe->transaction(fn () => $probe->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first());
            $held[$table] = false;
        } catch (Illuminate\Database\QueryException) {
            $held[$table] = true;
        }
    }
    $blocker->rollBack();
    pcntl_waitpid($pid, $status);
    fwrite(STDERR, "blocked={$blocked} waiting_sql=".substr((string) $waiting?->query, 0, 90)."\n  child_held_rows=".json_encode($held)
        ."\n  ungranted=".json_encode(array_values(array_filter(array_map(fn ($l) => $l->granted ? null : $l->locktype.':'.$l->rel.':'.$l->mode, $locks))))."\n");
    $expected = match ($blocked) {
        'business_profiles' => ['business_campaigns' => false, 'primary_reservations' => false, 'investor_wallets' => false],
        'business_campaigns' => ['business_profiles' => true, 'primary_reservations' => false, 'investor_wallets' => false],
        'primary_reservations' => ['business_profiles' => true, 'business_campaigns' => true, 'investor_wallets' => false],
        'investor_wallets' => ['business_profiles' => true, 'business_campaigns' => true, 'primary_reservations' => true],
    };
    expect($waiting)->not->toBeNull()->and($held)->toBe($expected)
        ->and(pcntl_wexitstatus($status))->toBe(0)
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('state'))->toBe('expired')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
})->with(['business_profiles', 'business_campaigns', 'primary_reservations', 'investor_wallets']);

it('PROBE a sweep blocked on one Business stalls every other overdue hold behind it', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $first = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $first->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->travel(1)->seconds();
    Illuminate\Support\Facades\Cache::forget('fortify.2fa_codes.'.md5((new PragmaRX\Google2FA\Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $second = PrimaryReservationFixture::campaign();
    app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $second->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->travelTo(PrimaryReservationRecord::query()->max('expires_at'));
    config(['database.connections.r175w_blocker' => config('database.connections.pgsql')]);
    DB::disconnect();
    [$parentEnd, $childEnd] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($parentEnd);
        DB::purge();
        fgets($childEnd);
        try {
            DB::statement("SET lock_timeout = '3s'");
            app(PrimaryReservations::class)->expireDue(100);
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, 'child: '.$exception::class.': '.substr($exception->getMessage(), 0, 80)."\n");
            exit(4);
        }
    }
    fclose($childEnd);
    $blocker = DB::connection('r175w_blocker');
    $blocker->beginTransaction();
    $blocker->table('business_profiles')->where('id', $first->business_id)->lockForUpdate()->first();
    $started = microtime(true);
    fwrite($parentEnd, "go\n");
    pcntl_waitpid($pid, $status);
    $blocker->rollBack();
    $states = PrimaryReservationVersion::query()->orderBy('primary_reservation_id')->orderByDesc('revision')->get()->groupBy('primary_reservation_id')->map(fn ($v) => $v->first()->state)->values()->all();
    fwrite(STDERR, 'exit='.pcntl_wexitstatus($status).' elapsed='.round(microtime(true) - $started, 2).'s states='.json_encode($states)."\n");
    expect(pcntl_wexitstatus($status))->toBe(4)->and($states)->toBe(['held', 'held']);
});
