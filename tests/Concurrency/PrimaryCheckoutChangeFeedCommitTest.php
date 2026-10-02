<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Models\InvestorWallet;
use App\Models\PrimaryReservationRecord;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('exposes standalone cash and feed together only after the callers real commit', function (bool $commit): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $request = (string) Str::uuid();
    config(['database.connections.primary_beacon_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('primary_beacon_observer');
    try {
        DB::beginTransaction();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', $request, PrimaryReservationFixture::terms(...));
        $id = $result['data']['reservation_id'];
        expect(DB::table('change_feed')->where('topic', 'purchase')->where('subject', $id)->count())->toBe(1)
            ->and($observer->table('change_feed')->where('topic', 'purchase')->where('subject', $id)->count())->toBe(0)
            ->and($observer->table('primary_reservations')->where('id', $id)->exists())->toBeFalse()
            ->and($observer->table('ledger_entries')->where('kind', 'primary_hold')->count())->toBe(0);
        $commit ? DB::commit() : DB::rollBack();
        expect($observer->table('change_feed')->where('topic', 'purchase')->where('subject', $id)->count())->toBe($commit ? 1 : 0)
            ->and($observer->table('primary_reservations')->where('id', $id)->exists())->toBe($commit)
            ->and($observer->table('ledger_entries')->where('kind', 'primary_hold')->count())->toBe($commit ? 1 : 0);
        if ($commit) {
            expect($checkout->reserve($investor['user']->id, 1, $campaign->id, '1', $request, PrimaryReservationFixture::terms(...)))->toBe($result)
                ->and($observer->table('change_feed')->where('topic', 'purchase')->where('subject', $id)->count())->toBe(1)
                ->and(PrimaryReservationRecord::query()->whereKey($id)->exists())->toBeTrue();
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('primary_beacon_observer');
    }
})->with(['commit' => true, 'rollback' => false]);

it('keeps sweeper cash and feed invisible together until the callers real commit', function (bool $commit): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $this->travelTo($root->expires_at);
    $before = (int) DB::table('change_feed')->max('id');
    config(['database.connections.primary_beacon_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('primary_beacon_observer');
    $targets = ['business_profiles' => $campaign->business_id, 'business_campaigns' => $campaign->id,
        'primary_reservations' => $root->id, 'investor_wallets' => InvestorWallet::query()->where('party_id', $investor['party']->id)->value('id')];
    $observed = false;
    $financial = [];
    DB::listen(function (QueryExecuted $query) use (&$observed, &$financial, $observer, $targets): void {
        if ($query->connectionName !== config('database.default')) {
            return;
        }
        if (str_contains($query->sql, 'pg_advisory_xact_lock') && str_starts_with((string) ($query->bindings[0] ?? ''), 'change-feed|')) {
            $observed = true;
            foreach ($targets as $table => $id) {
                expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                    ->toThrow(QueryException::class, 'could not obtain lock');
            }
        } elseif ($observed && preg_match('/^(insert|update|delete)/i', $query->sql) === 1 && ! str_contains($query->sql, 'change_feed')) {
            $financial[] = $query->sql;
        }
    });
    try {
        DB::beginTransaction();
        expect(app(PrimaryReservations::class)->expireDue(1))->toBe(1)
            ->and($observed)->toBeTrue()->and($financial)->toBe([])
            ->and(DB::table('change_feed')->where('id', '>', $before)->count())->toBe(2)
            ->and($observer->table('change_feed')->where('id', '>', $before)->count())->toBe(0)
            ->and($observer->table('ledger_entries')->where('kind', 'primary_release')->count())->toBe(0)
            ->and($observer->table('primary_reservation_versions')->where('state', 'expired')->count())->toBe(0);
        $commit ? DB::commit() : DB::rollBack();
        expect($observer->table('change_feed')->where('id', '>', $before)->count())->toBe($commit ? 2 : 0)
            ->and($observer->table('ledger_entries')->where('kind', 'primary_release')->count())->toBe($commit ? 1 : 0)
            ->and($observer->table('primary_reservation_versions')->where('state', 'expired')->count())->toBe($commit ? 1 : 0);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('primary_beacon_observer');
    }
})->with(['commit' => true, 'rollback' => false]);
