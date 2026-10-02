<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\InvestorFundingMethod;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    /** @param array{user: User, party: Party, method: InvestorFundingMethod}|null $investor */
    $this->purchase = function (string $units = '3', string $state = 'refunded', ?array $investor = null, ?BusinessCampaign $campaign = null): PrimaryReservationRecord {
        $investor ??= PrimaryReservationFixture::investor();
        $campaign ??= $this->campaign;
        $checkout = app(PrimaryCheckout::class);
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        if (in_array($state, ['confirmed', 'refunded'], true)) {
            expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                $held->payload['terms']['disclosure_version'], $held->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])
                ->toBe('RESERVATION_CONFIRMED');
        }
        if ($state === 'refunded') {
            expect($checkout->refund($investor['user']->id, 1, $campaign->id, $root->id, 2, (string) Str::uuid())['code'])->toBe('COMMITMENT_REFUNDED');
        } elseif ($state === 'released') {
            expect($checkout->release($investor['user']->id, 1, $campaign->id, $root->id, 1, (string) Str::uuid())['code'])->toBe('RESERVATION_RELEASED');
        } elseif ($state === 'expired') {
            $this->travelTo($root->expires_at);
            expect(app(PrimaryReservations::class)->expire($campaign->id, $root->id))->not->toBeNull();
        }

        return $root;
    };
    $this->store = app(BusinessCampaignStore::class);
    $this->cancel = fn (?string $request = null) => $this->store->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, 'Return verified.', $request ?? (string) Str::uuid());
});

it('closes a fully returned campaign once with retained refunds distinct Parties and original cash', function (): void {
    $investor = PrimaryReservationFixture::investor();
    ($this->purchase)('3', investor: $investor);
    ($this->purchase)('1', investor: $investor);
    ($this->purchase)('2');
    ($this->purchase)('4', 'released');
    ($this->purchase)('5', 'expired');
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $history = PrimaryReservationVersion::query()->orderBy('id')->get()->toJson();
    $commitments = PrimaryCommitment::query()->orderBy('id')->get()->toJson();
    expect($this->store->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id)['can_cancel'])->toBeTrue();
    $request = (string) Str::uuid();
    $result = ($this->cancel)($request);
    expect($result['code'])->toBe('CAMPAIGN_CANCELLED')
        ->and($result['data']['receipt']['committed_refunded'])->toEqual(['currency' => 'RWF', 'amount' => '30000'])
        ->and($result['data']['receipt']['investors'])->toBe(2);
    $closure = BusinessCampaignClosure::query()->sole();
    expect($closure->payload['scope'])->toBe('unfunded-returned-v1')
        ->and($closure->payload['released_held'])->toEqual(['currency' => 'RWF', 'amount' => '45000'])
        ->and($closure->payload['cash_returns'])->toHaveCount(5)
        ->and(DB::table('primary_campaign_closure_returns')->count())->toBe(5)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe([]);
    $this->travel(60)->days();
    expect(($this->cancel)($request))->toBe($result)
        ->and($this->store->findCancellation($this->campaign->actor_user_id, 1, $request))->toBe($result)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(PrimaryReservationVersion::query()->orderBy('id')->get()->toJson())->toBe($history)
        ->and(PrimaryCommitment::query()->orderBy('id')->get()->toJson())->toBe($commitments);
    $page = $this->store->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    expect($page['progress'])->toEqual(['phase' => 'cancelled', 'committed_refunded' => ['currency' => 'RWF', 'amount' => '30000'],
        'investors' => 2, 'closed_at' => $closure->payload['closed_at']]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('closes returned commitments after expiry retaining the deadline and actual processing time', function (): void {
    ($this->purchase)();
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $this->travelTo($this->campaign->expires_at->addDays(60));
    expect($this->store->expireDue(1))->toBe(1)->and($this->store->expireDue(1))->toBe(0);
    $closure = BusinessCampaignClosure::query()->sole();
    expect($closure->phase)->toBe('expired')->and($closure->actor_user_id)->toBeNull()
        ->and($closure->closed_at->equalTo($this->campaign->expires_at))->toBeTrue()
        ->and($closure->payload['recorded_at'])->toBe(now()->toIso8601String())
        ->and($closure->payload['committed_refunded'])->toEqual(['currency' => 'RWF', 'amount' => '15000'])
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe([])
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(CommandOperation::query()->where('command', 'campaign.cancel')->count())->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('retains exposure while any hold or commitment is still unreturned', function (string $state): void {
    ($this->purchase)();
    $root = ($this->purchase)('4', $state);
    $exposure = app(BusinessExposureStore::class)->current($this->campaign->business_id);
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $request = (string) Str::uuid();
    $result = ($this->cancel)($request);
    expect($result['code'])->toBe('CAMPAIGN_SETTLEMENT_REQUIRED')->and(($this->cancel)($request))->toBe($result)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(DB::table('primary_campaign_closure_returns')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($exposure)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and($this->store->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id)['can_cancel'])->toBeFalse();
    if ($state === 'held') {
        $this->travelTo($root->expires_at);
        app(PrimaryReservations::class)->expire($this->campaign->id, $root->id);
        expect(($this->cancel)()['code'])->toBe('CAMPAIGN_CANCELLED');
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['held', 'confirmed']);

it('rolls back returned membership closure receipt and exposure release together', function (): void {
    ($this->purchase)();
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $request = (string) Str::uuid();
    DB::unprepared("CREATE FUNCTION fail_closure_return_insert() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'synthetic closure return failure'; END; $$; CREATE TRIGGER fail_closure_return_insert BEFORE INSERT ON primary_campaign_closure_returns FOR EACH ROW EXECUTE FUNCTION fail_closure_return_insert()");
    try {
        expect(fn () => ($this->cancel)($request))->toThrow(QueryException::class, 'synthetic closure return failure');
    } finally {
        DB::unprepared('DROP TRIGGER fail_closure_return_insert ON primary_campaign_closure_returns; DROP FUNCTION fail_closure_return_insert()');
    }
    expect(BusinessCampaignClosure::query()->count())->toBe(0)->and(DB::table('primary_campaign_closure_returns')->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'campaign.cancel')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toHaveCount(1)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(($this->cancel)($request)['code'])->toBe('CAMPAIGN_CANCELLED');
});

it('refuses digest-consistent forged closure totals or cash membership before releasing exposure', function (string $field): void {
    ($this->purchase)();
    ($this->cancel)();
    $closure = BusinessCampaignClosure::query()->sole();
    $payload = $closure->payload;
    match ($field) {
        'principal' => $payload['committed_refunded']['amount'] = '5000',
        'released' => $payload['released_held']['amount'] = '5000',
        'investors' => $payload['investors'] = 0,
        'cash' => $payload['cash_returns'][0]['return_entry_id'] = $payload['cash_returns'][0]['hold_entry_id'],
        'missing' => $payload['cash_returns'] = [],
        'type' => $payload['cash_returns'] = null,
        'scope' => $payload['scope'] = 'unfunded-v1',
        default => throw new LogicException('Unknown forgery.'),
    };
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE business_campaign_closures DISABLE TRIGGER business_campaign_closures_protected');
    try {
        $closure->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE business_campaign_closures ENABLE TRIGGER business_campaign_closures_protected');
    }
    expect(fn () => DB::transaction(fn () => app(BusinessExposureStore::class)->current($this->campaign->business_id)))
        ->toThrow(RuntimeException::class, 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
})->with(['principal', 'released', 'investors', 'cash', 'missing', 'type', 'scope']);

it('continues to verify legacy investor-free closures', function (): void {
    ($this->cancel)();
    $closure = BusinessCampaignClosure::query()->sole();
    $payload = $closure->payload;
    $payload['scope'] = 'unfunded-v1';
    unset($payload['cash_returns'], $payload['released_held']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE business_campaign_closures DISABLE TRIGGER business_campaign_closures_protected');
    try {
        $closure->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE business_campaign_closures ENABLE TRIGGER business_campaign_closures_protected');
    }
    expect(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe([]);
});

it('refuses corrupted relational return bindings before treating campaign exposure as released', function (): void {
    ($this->purchase)();
    ($this->cancel)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE primary_campaign_closure_returns DISABLE TRIGGER primary_closure_returns_protected');
    try {
        DB::table('primary_campaign_closure_returns')->update(['principal' => '5000']);
    } finally {
        DB::statement('ALTER TABLE primary_campaign_closure_returns ENABLE TRIGGER primary_closure_returns_protected');
    }
    expect(fn () => DB::transaction(fn () => app(BusinessExposureStore::class)->current($this->campaign->business_id)))
        ->toThrow(RuntimeException::class, 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
});
