<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\PrimaryReservationRecord;
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
