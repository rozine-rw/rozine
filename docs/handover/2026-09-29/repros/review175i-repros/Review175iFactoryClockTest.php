<?php

declare(strict_types=1);

/*
 * Review repro for ad598a09 ("Freeze clocks for wallet reservation fixtures"). Copy into tests/Feature/.
 *
 * PrimaryReservationRecordFactory::definition() evaluates 'created_at' => now()->startOfSecond()
 * eagerly, before Laravel resolves the BusinessCampaign::factory() relation, whose own definition
 * stamps 'live_at' => now()->startOfSecond(). When the wall clock crosses a second boundary between
 * the two calls, created_at < live_at and validate_primary_reservation() refuses the insert.
 * freezeSecond() hides that ordering; the production reserve() path is not affected (it locks the
 * campaign and only then reads now()).
 *
 * FAILS on ad598a09 (demonstrates the fixture flake deterministically). Any test that uses the factory
 * or PrimaryReservationFixture::postingSource() without freezing the clock is exposed to it.
 */

use App\Models\BusinessCampaign;
use App\Models\PrimaryReservationRecord;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

it('DEFECT: the reservation factory survives a second boundary between its own and its campaign clock reads', function (): void {
    $base = CarbonImmutable::now()->startOfSecond();
    $calls = 0;
    // First clock read lands at .999999, every later read one microsecond later: a real boundary crossing.
    Carbon::setTestNow(function () use ($base, &$calls): Carbon {
        return Carbon::instance($calls++ === 0 ? $base->addMicroseconds(999999) : $base->addSecond());
    });

    try {
        $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
        expect($root->created_at->greaterThanOrEqualTo(BusinessCampaign::query()->findOrFail($root->business_campaign_id)->live_at))->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});
