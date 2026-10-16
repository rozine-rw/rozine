<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
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

it('moves refunded purchases out of committed totals while retaining occupied ordinals and original evidence', function (): void {
    $returned = summaryReservation($this->campaign, 2, 'confirmed');
    summaryReservation($this->campaign, 3, 'confirmed', $returned->party_id);
    $last = summaryReservation($this->campaign, 1, 'confirmed');
    $wallets = app(WalletPostings::class);
    foreach ([$returned, $last] as $root) {
        $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
        $wallet = $wallets->lockForParty($root->party_id);
        $wallets->refund($wallet, WalletMoney::of($root->principal), $source);
        expect($wallets->refund($wallet, WalletMoney::of($root->principal), $source)->replayed)->toBeTrue();
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $entries = LedgerEntry::query()->orderBy('id')->get()->toArray();
    $versions = PrimaryReservationVersion::query()->orderBy('id')->get()->toArray();
    expect($this->summary->read($this->campaign->id, now()->toDateTimeImmutable()))->toMatchArray([
        'committed_principal' => '15000', 'committed_units' => '3', 'investors' => 1,
        'returned_principal' => '15000', 'returned_units' => '3', 'occupied_units' => '6',
    ])->and(PrimaryCommitment::query()->count())->toBe(3)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($entries)
        ->and(PrimaryReservationVersion::query()->orderBy('id')->get()->toArray())->toBe($versions);
});

it('does not subtract another campaigns refund from retained commitments', function (): void {
    $root = summaryReservation($this->campaign, 2, 'confirmed');
    $other = summaryReservation(BusinessCampaign::factory()->create(), 3, 'confirmed', $root->party_id);
    $wallets = app(WalletPostings::class);
    $wallets->refund($wallets->lockForParty($other->party_id), WalletMoney::of($other->principal),
        new PostingSource('primary_reservation', $other->id, $other->origin_operation_id));
    expect($this->summary->read($this->campaign->id, now()->toDateTimeImmutable()))->toMatchArray([
        'committed_principal' => '10000', 'committed_units' => '2', 'investors' => 1,
        'returned_principal' => '0', 'returned_units' => '0', 'occupied_units' => '2',
    ]);
});

it('fails closed rather than projecting an incomplete or misbound refund as returned money', function (string $damage): void {
    $root = summaryReservation($this->campaign, 2, 'confirmed');
    $other = summaryReservation($this->campaign, 3, 'confirmed');
    $wallets = app(WalletPostings::class);
    $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
        new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    $entry = LedgerEntry::query()->where('kind', 'primary_refund')->sole();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $table = in_array($damage, ['amount', 'incomplete'], true) ? 'ledger_lines' : 'ledger_entries';
    DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER USER');
    try {
        match ($damage) {
            'operation' => DB::table('ledger_entries')->where('id', $entry->id)->update(['origin_operation_id' => $other->origin_operation_id]),
            'wallet' => DB::table('ledger_entries')->where('id', $entry->id)->update(['wallet_id' => $wallets->lockForParty($other->party_id)->walletId]),
            'amount' => DB::table('ledger_lines')->where('entry_id', $entry->id)->update(['amount' => '5000']),
            'incomplete' => DB::table('ledger_lines')->where('entry_id', $entry->id)->where('direction', 'credit')->delete(),
            default => throw new InvalidArgumentException('Unknown refund corruption fixture.'),
        };
    } finally {
        DB::statement('ALTER TABLE '.$table.' ENABLE TRIGGER USER');
    }
    expect(fn () => $this->summary->read($this->campaign->id, now()->toDateTimeImmutable()))
        ->toThrow(RuntimeException::class, 'RESERVATION_SUMMARY_INTEGRITY_FAILED');
})->with(['operation', 'wallet', 'amount', 'incomplete']);

it('reports zero committed investors after every purchase is refunded without erasing the original commitments', function (): void {
    $roots = [summaryReservation($this->campaign, 2, 'confirmed'), summaryReservation($this->campaign, 3, 'confirmed')];
    $wallets = app(WalletPostings::class);
    foreach ($roots as $root) {
        $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    }
    expect($this->summary->read($this->campaign->id, now()->toDateTimeImmutable()))->toMatchArray([
        'committed_principal' => '0', 'committed_units' => '0', 'investors' => 0,
        'returned_principal' => '25000', 'returned_units' => '5', 'occupied_units' => '5',
    ])->and(PrimaryCommitment::query()->count())->toBe(2);
});
