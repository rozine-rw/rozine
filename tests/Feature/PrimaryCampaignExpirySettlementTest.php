<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaignClosure;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->store = app(BusinessCampaignStore::class);
    $this->purchase = function (string $state = 'confirmed', string $units = '3'): PrimaryReservationRecord {
        $investor = PrimaryReservationFixture::investor();
        $checkout = app(PrimaryCheckout::class);
        $result = $checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        if ($state !== 'held') {
            $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
            $checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1,
                $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        }
        if ($state === 'refunded') {
            $checkout->refund($investor['user']->id, 1, $this->campaign->id, $root->id, 2, (string) Str::uuid());
        }

        return $root;
    };
});

it('returns partially funded purchases and unswept holds once with a durable system expiry cause', function (): void {
    ($this->purchase)();
    ($this->purchase)('refunded');
    ($this->purchase)('held');
    $commitments = PrimaryCommitment::query()->orderBy('id')->get()->toJson();
    $confirmed = PrimaryReservationVersion::query()->where('state', 'confirmed')->orderBy('id')->get()->toJson();
    $this->travelTo($this->campaign->expires_at);
    expect($this->store->expireDue(1))->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $closure = BusinessCampaignClosure::query()->sole();
    expect($closure->payload['committed_refunded'])->toBe(['currency' => 'RWF', 'amount' => '30000'])
        ->and($closure->payload['released_held'])->toBe(['currency' => 'RWF', 'amount' => '15000'])
        ->and($closure->payload['cash_returns'])->toHaveCount(3)
        ->and($closure->payload['investors'])->toBe(2)
        ->and(DB::table('primary_campaign_expiry_settlements')->sole()->business_campaign_closure_id)->toBe($closure->id)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(2)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe([])
        ->and(PrimaryCommitment::query()->orderBy('id')->get()->toJson())->toBe($commitments)
        ->and(PrimaryReservationVersion::query()->where('state', 'confirmed')->orderBy('id')->get()->toJson())->toBe($confirmed);
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    expect($this->store->expireDue(1))->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(1);
});

it('keeps complete committed cash for funding settlement after the deadline', function (bool $funded): void {
    ($this->purchase)('confirmed', '1080');
    ($this->purchase)('confirmed', '1080');
    if ($funded) {
        app(PrimaryFunding::class)->lock($this->campaign->id, fn (string $id): array => ['campaign_id' => $id,
            'publication_sha256' => $this->campaign->sha256,
            ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'Isolated test only.']])]);
    }
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $this->travelTo($this->campaign->expires_at);
    expect($this->store->expireDue(1))->toBe(0)
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toHaveCount(1);
    expect(fn () => app(PrimaryReservations::class)->settleExpiredCampaign($this->campaign->id, strtolower((string) Str::ulid())))
        ->toThrow(CommandRejection::class, $funded ? 'CAMPAIGN_FUNDED' : 'CAMPAIGN_SETTLEMENT_REQUIRED');
})->with([false, true]);

it('refuses early settlement and keeps original cash unchanged', function (): void {
    ($this->purchase)();
    $cash = LedgerEntry::query()->get()->toJson();
    expect(fn () => app(PrimaryReservations::class)->settleExpiredCampaign($this->campaign->id, strtolower((string) Str::ulid())))
        ->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_EXPIRED')
        ->and(LedgerEntry::query()->get()->toJson())->toBe($cash);
});

it('requires its final guarded closure and rolls back cash when that closure fails', function (): void {
    ($this->purchase)();
    $cash = LedgerEntry::query()->get()->toJson();
    $this->travelTo($this->campaign->expires_at);
    expect(fn () => DB::transaction(function (): void {
        app(PrimaryReservations::class)->settleExpiredCampaign($this->campaign->id, strtolower((string) Str::ulid()));
        DB::statement('SET CONSTRAINTS primary_expiry_settlement_bound IMMEDIATE');
    }))->toThrow(QueryException::class, 'exact system closure')
        ->and(LedgerEntry::query()->get()->toJson())->toBe($cash)
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(0);
    Event::listen('eloquent.created: '.BusinessCampaignClosure::class, fn () => throw new RuntimeException('CLOSURE_FAILURE'));
    try {
        expect(fn () => $this->store->expireDue(1))->toThrow(RuntimeException::class, 'CLOSURE_FAILURE')
            ->and(LedgerEntry::query()->get()->toJson())->toBe($cash)
            ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(0)
            ->and(BusinessCampaignClosure::query()->count())->toBe(0);
    } finally {
        Event::forget('eloquent.created: '.BusinessCampaignClosure::class);
    }
    expect($this->store->expireDue(1))->toBe(1);
});

it('refuses to forget retained settlement evidence and restores an unused schema', function (): void {
    $migration = require database_path('migrations/2026_09_30_204213_create_primary_campaign_expiry_settlements_table.php');
    $migration->down();
    $migration->up();
    ($this->purchase)();
    $this->travelTo($this->campaign->expires_at);
    $this->store->expireDue(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'forward migration')
        ->and(fn () => DB::transaction(fn () => DB::table('primary_campaign_expiry_settlements')->delete()))
        ->toThrow(QueryException::class, 'immutable');
});

it('refuses a forged system cause against an otherwise valid retained expiry closure', function (string $damage): void {
    $this->travelTo($this->campaign->expires_at);
    $this->store->expireDue(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $cause = (array) DB::table('primary_campaign_expiry_settlements')->sole();
    expect(fn () => DB::transaction(function () use ($cause, $damage): void {
        DB::statement('ALTER TABLE primary_campaign_expiry_settlements DISABLE TRIGGER primary_expiry_settlements_immutable');
        DB::table('primary_campaign_expiry_settlements')->delete();
        DB::statement('ALTER TABLE primary_campaign_expiry_settlements ENABLE TRIGGER primary_expiry_settlements_immutable');
        $forged = $cause;
        $forged[$damage] = $damage === 'created_at' ? $this->campaign->expires_at->subSecond() : strtolower((string) Str::ulid());
        DB::table('primary_campaign_expiry_settlements')->insert($forged);
        DB::statement('SET CONSTRAINTS primary_expiry_settlement_bound IMMEDIATE');
    }))->toThrow(QueryException::class, 'exact system closure')
        ->and((array) DB::table('primary_campaign_expiry_settlements')->sole())->toBe($cause);
})->with(['created_at', 'business_campaign_closure_id']);
