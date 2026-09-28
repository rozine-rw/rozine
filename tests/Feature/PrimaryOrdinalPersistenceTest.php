<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessCampaign;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

function removePrimaryOrdinalProjection(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::unprepared('DROP TRIGGER primary_ordinal_evidence ON primary_reservations;
        DROP TABLE primary_ordinal_claims;
        DROP FUNCTION retain_primary_ordinal_claims(), validate_primary_ordinal_claim();
        DROP TRIGGER primary_ordinals_unique ON primary_reservations;
        DROP FUNCTION exclude_primary_ordinal_overlap();
        ALTER TABLE primary_reservations DROP COLUMN ordinal_ranges;
        DROP FUNCTION primary_ordinal_count(int8multirange);');
}

it('retains fragmented exact unit claims and permits adjacent allocations', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create(['units' => 3, 'principal' => '15000', 'ordinal_ranges' => '{[1,3),[5,6)}']);
    PrimaryReservationRecord::factory()->withInitialVersion()->create(['business_campaign_id' => $root->business_campaign_id,
        'units' => 2, 'principal' => '10000', 'ordinal_ranges' => '{[3,5)}']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(DB::table('primary_ordinal_claims')->where('primary_reservation_id', $root->id)->orderBy('ordinal')->pluck('ordinal')->all())->toBe([1, 2, 5])
        ->and(DB::table('primary_ordinal_claims')->count())->toBe(5)->and($root->toArray())->not->toHaveKey('ordinal_ranges');
});

it('rejects raw overlapping claims without relying on encrypted payloads', function (string $ranges): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create(['units' => 3, 'principal' => '15000', 'ordinal_ranges' => '{[1,3),[5,6)}']);
    expect(fn () => DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create([
        'business_campaign_id' => $root->business_campaign_id, 'units' => 1, 'principal' => '5000', 'ordinal_ranges' => $ranges,
    ])))->toThrow(QueryException::class, 'cannot overlap retained allocations');
    expect(PrimaryReservationRecord::query()->count())->toBe(1)->and(DB::table('primary_ordinal_claims')->count())->toBe(3);
})->with(['{[1,2)}', '{[2,3)}', '{[5,6)}']);

it('rejects empty unbounded out of campaign and wrong quantity claims', function (?string $ranges): void {
    expect(fn () => DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create(['ordinal_ranges' => $ranges])))
        ->toThrow(QueryException::class);
    expect(PrimaryReservationRecord::query()->count())->toBe(0)->and(DB::table('primary_ordinal_claims')->count())->toBe(0);
})->with([null, '{}', '{(,2)}', '{[1,)}', '{[0,1)}', '{[20000,20001)}', '{[1,3)}']);

it('keeps claims immutable and refuses claims for a different parent campaign or ordinal', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $other = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    expect(fn () => DB::transaction(fn () => DB::table('primary_ordinal_claims')->where('primary_reservation_id', $root->id)->delete()))
        ->toThrow(QueryException::class, 'immutable');
    expect(fn () => DB::transaction(fn () => DB::table('primary_ordinal_claims')->where('primary_reservation_id', $root->id)->update(['ordinal' => 2])))
        ->toThrow(QueryException::class, 'immutable');
    foreach ([[$root->business_campaign_id, 2], [$other->business_campaign_id, 2]] as [$campaign, $ordinal]) {
        expect(fn () => DB::transaction(fn () => DB::table('primary_ordinal_claims')->insert([
            'business_campaign_id' => $campaign, 'ordinal' => $ordinal, 'primary_reservation_id' => $root->id,
        ])))->toThrow(QueryException::class, 'must bind its reservation');
    }
});

it('backfills only verified retained ordinal evidence and preserves the original ciphertext', function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    PrimaryReservationFixture::reserve($campaign, PrimaryReservationFixture::investor(), '2');
    $original = DB::table('primary_reservations')->sole();
    removePrimaryOrdinalProjection();
    $migration = require database_path('migrations/2026_09_28_154941_enforce_primary_ordinal_exclusion.php');
    $migration->up();
    $restored = DB::table('primary_reservations')->sole();
    expect($restored->ordinal_ranges)->toBe('{[1,3)}')->and($restored->payload)->toBe($original->payload)
        ->and($restored->sha256)->toBe($original->sha256)->and(DB::table('primary_ordinal_claims')->orderBy('ordinal')->pluck('ordinal')->all())->toBe([1, 2]);
    expect(fn () => DB::transaction(fn () => DB::table('primary_reservations')->update(['units' => 3])))->toThrow(QueryException::class, 'immutable');
});

it('rolls schema and trigger changes back when historical ordinal evidence cannot be verified', function (string $damage): void {
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    PrimaryReservationFixture::reserve($campaign, PrimaryReservationFixture::investor(), '2');
    $root = PrimaryReservationRecord::query()->sole();
    removePrimaryOrdinalProjection();
    $payload = $root->payload;
    match ($damage) {
        'digest' => $payload['principal'] = '1',
        'binding' => $payload['party_id'] = 'wrong',
        'quantity' => $payload['ordinals'] = [['first' => '1', 'last' => '1']],
        'shape' => $payload['ordinals'] = null,
        'bounds' => $payload['ordinals'] = [['first' => '0', 'last' => '1']],
        default => throw new InvalidArgumentException('Unknown historical allocation damage.'),
    };
    DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER primary_reservations_immutable');
    $root->forceFill(['payload' => $payload, 'sha256' => $damage === 'digest' ? $root->sha256 : hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservations ENABLE TRIGGER primary_reservations_immutable');
    $migration = require database_path('migrations/2026_09_28_154941_enforce_primary_ordinal_exclusion.php');
    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'verified historical evidence')
        ->and(Schema::hasColumn('primary_reservations', 'ordinal_ranges'))->toBeFalse()->and(Schema::hasTable('primary_ordinal_claims'))->toBeFalse();
    expect(fn () => DB::transaction(fn () => DB::table('primary_reservations')->update(['units' => 3])))->toThrow(QueryException::class, 'immutable');
})->with(['digest', 'binding', 'quantity', 'shape', 'bounds']);

it('treats a cross reservation overlap as an integrity failure even when both payloads have valid digests', function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    PrimaryReservationFixture::reserve($campaign, $investor, '2');
    PrimaryReservationFixture::reserve($campaign, PrimaryReservationFixture::investor(), '2');
    [$first, $second] = PrimaryReservationRecord::query()->orderBy('id')->get()->all();
    $payload = [...$second->payload, 'ordinals' => $first->payload['ordinals'], 'rights' => $first->payload['rights']];
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER primary_reservations_immutable');
    $second->forceFill(['ordinal_ranges' => $first->ordinal_ranges, 'payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservations ENABLE TRIGGER primary_reservations_immutable');
    expect(fn () => PrimaryReservationFixture::reserve($campaign, $investor, '1'))->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
    removePrimaryOrdinalProjection();
    $migration = require database_path('migrations/2026_09_28_154941_enforce_primary_ordinal_exclusion.php');
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'verified nonoverlapping evidence');
});

it('retains all claims until a verified forward release migration exists', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $root->id, 'state' => 'released']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(fn () => DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create([
        'business_campaign_id' => $root->business_campaign_id, 'ordinal_ranges' => '{[1,2)}',
    ])))->toThrow(QueryException::class, 'cannot overlap retained allocations');
});

it('retains exactly the maximum published unit count without expanding beyond its bounds', function (): void {
    $campaign = BusinessCampaign::factory()->create(['principal' => '100000000']);
    PrimaryReservationRecord::factory()->withInitialVersion()->create(['business_campaign_id' => $campaign->id,
        'units' => 20000, 'principal' => '100000000', 'ordinal_ranges' => '{[1,20001)}']);
    expect(DB::table('primary_ordinal_claims')->count())->toBe(20000)->and(DB::table('primary_ordinal_claims')->max('ordinal'))->toBe(20000);
});
