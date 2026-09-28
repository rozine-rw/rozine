<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('requires an outer transaction before acquiring any funding candidate locks', function (): void {
    expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate('campaign'))
        ->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('retains all candidate locks until the callers real transaction ends', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    foreach ([1, 2] as $index) {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    }
    config(['database.connections.funding_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('funding_observer');
    $targets = [['business_profiles', $campaign->business_id], ['business_campaigns', $campaign->id]];
    foreach (['primary_reservations', 'primary_commitments', 'investor_wallets'] as $table) {
        foreach (DB::table($table)->pluck('id') as $id) {
            $targets[] = [$table, $id];
        }
    }
    try {
        DB::beginTransaction();
        expect(app(PrimaryReservations::class)->lockFundingCandidate($campaign->id)->purchases)->toHaveCount(2);
        foreach ($targets as [$table, $id]) {
            expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                ->toThrow(QueryException::class, 'could not obtain lock');
        }
        DB::rollBack();
        foreach ($targets as [$table, $id]) {
            expect($observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first())?->id)->toBe($id);
        }
        expect(PrimaryCommitment::query()->count())->toBe(2)
            ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(2);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('funding_observer');
    }
});
