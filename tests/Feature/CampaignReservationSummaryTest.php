<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Models\BusinessCampaign;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->campaign = BusinessCampaign::factory()->create();
    $this->summary = app(CampaignReservationSummary::class);
});

function summaryReservation(BusinessCampaign $campaign, int $units, string $state = 'held', ?string $partyId = null): PrimaryReservationRecord
{
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create([
        'business_campaign_id' => $campaign->id, 'units' => $units, 'principal' => (string) ($units * 5000),
        ...($partyId === null ? [] : ['party_id' => $partyId]),
    ]);
    if ($state !== 'held') {
        $version = PrimaryReservationVersion::factory()->withCashMovement()->create([
            'primary_reservation_id' => $root->id, 'state' => $state,
            'created_at' => $state === 'expired' ? $root->expires_at : now(),
        ]);
        if ($state === 'confirmed') {
            PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $version->id]);
        }
    }

    return $root;
}

it('returns exact empty aggregates without creating reservation or cash evidence', function (): void {
    expect($this->summary->read($this->campaign->id, now()->toDateTimeImmutable()))->toBe([
        'committed_principal' => '0', 'committed_units' => '0', 'investors' => 0,
        'held_principal' => '0', 'held_units' => '0', 'expired_hold_principal' => '0', 'expired_hold_units' => '0',
        'returned_principal' => '0', 'returned_units' => '0', 'occupied_units' => '0',
    ])->and(PrimaryReservationRecord::query()->count())->toBe(0)->and(LedgerEntry::query()->count())->toBe(0);
});

it('counts latest reservation states once and investors by Party without leaking another campaign', function (): void {
    $first = summaryReservation($this->campaign, 2, 'confirmed');
    summaryReservation($this->campaign, 3, 'confirmed', $first->party_id);
    summaryReservation($this->campaign, 1, 'confirmed');
    $held = summaryReservation($this->campaign, 4);
    PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $held->id, 'revision' => 2]);
    PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $held->id, 'revision' => 3]);
    summaryReservation($this->campaign, 5, 'released');
    $this->travel(1)->second();
    summaryReservation($this->campaign, 6, 'expired');
    summaryReservation(BusinessCampaign::factory()->create(), 7, 'confirmed');
    $cash = LedgerEntry::query()->orderBy('id')->get()->toArray();
    $versions = PrimaryReservationVersion::query()->count();
    expect($this->summary->read($this->campaign->id, now()->toDateTimeImmutable()))->toBe([
        'committed_principal' => '30000', 'committed_units' => '6', 'investors' => 2,
        'held_principal' => '20000', 'held_units' => '4', 'expired_hold_principal' => '0', 'expired_hold_units' => '0',
        'returned_principal' => '55000', 'returned_units' => '11', 'occupied_units' => '21',
    ])->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($cash)
        ->and(PrimaryReservationVersion::query()->count())->toBe($versions);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('separates overdue cash from live holds at the exact deadline without releasing either', function (): void {
    $held = summaryReservation($this->campaign, 3);
    summaryReservation($this->campaign, 2, 'confirmed');
    $cash = LedgerEntry::query()->orderBy('id')->get()->toArray();
    $before = $this->summary->read($this->campaign->id, $held->expires_at->subMicrosecond()->toDateTimeImmutable());
    $at = $this->summary->read($this->campaign->id, $held->expires_at->toDateTimeImmutable());
    $otherZone = $this->summary->read($this->campaign->id, $held->expires_at->setTimezone('Asia/Qatar')->toDateTimeImmutable());
    expect($before)->toMatchArray(['held_principal' => '15000', 'held_units' => '3', 'expired_hold_units' => '0'])
        ->and($at)->toMatchArray(['held_principal' => '0', 'held_units' => '0', 'expired_hold_principal' => '15000', 'expired_hold_units' => '3',
            'committed_principal' => '10000', 'committed_units' => '2', 'investors' => 1, 'occupied_units' => '5'])
        ->and($otherZone)->toBe($at)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($cash)
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $held->id)->sole()->state)->toBe('held');
});

it('fails closed on missing current version or commitment evidence', function (string $damage): void {
    expect(fn () => DB::transaction(function () use ($damage): void {
        if ($damage === 'version') {
            PrimaryReservationRecord::factory()->create(['business_campaign_id' => $this->campaign->id]);
        } else {
            $root = summaryReservation($this->campaign, 1);
            PrimaryReservationVersion::factory()->confirmed()->withCashMovement()->create(['primary_reservation_id' => $root->id]);
        }
        $this->summary->read($this->campaign->id, now()->toDateTimeImmutable());
    }))->toThrow(RuntimeException::class, 'RESERVATION_SUMMARY_INTEGRITY_FAILED');
})->with(['version', 'commitment']);
