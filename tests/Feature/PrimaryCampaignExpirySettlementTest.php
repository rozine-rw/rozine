<?php

declare(strict_types=1);

use App\Application\Business\ConfigureBusinessAuthority;
use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\PrimaryCampaignReturns;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery\CompositeExpectation;
use Tests\Support\AuditSealingFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->store = app(BusinessCampaignStore::class);
    $fixture = AuditSealingFixture::ready(signatories: 2, requiredSignatories: 1);
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $this->store->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Verified release.', (string) Str::uuid());
    $this->store->publish($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
        $fixture['application']->id, $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $this->campaign = BusinessCampaign::query()->where('business_application_id', $fixture['application']->id)->sole();
    $this->changeAuthority = function (string $change, string $partyId): void {
        if ($change === 'current') {
            return;
        }
        $business = BusinessProfile::query()->whereKey($this->campaign->business_id)->sole();
        $mandate = BusinessMandate::query()->where('business_id', $business->id)->where('version', $business->mandate_version)->sole();
        $terms = $mandate->terms;
        if ($change === 'connected') {
            $terms['people'][] = ['party_id' => $partyId, 'name' => 'Later declared beneficial owner', 'roles' => ['beneficial_owner'], 'permissions' => []];
        } elseif ($change === 'future') {
            $terms['effective_at'] = $this->campaign->expires_at->addSecond()->format('Y-m-d\TH:i:s\Z');
        } elseif ($change === 'expired') {
            $terms['expires_at'] = $this->campaign->expires_at->format('Y-m-d\TH:i:s\Z');
        } else {
            $terms['status'] = 'revoked';
        }
        $staff = User::factory()->withTwoFactor()->create();
        app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Review changed mandate.', (string) Str::uuid(), ['compliance']);
        expect(app(ConfigureBusinessAuthority::class)->handle($staff->id, $business->entity_kind, $business->entity_party_id,
            $business->profile, $terms, $business->revision, 'fixture:reviewed-expiry-authority', 'Reviewed later authority change.',
            (string) Str::uuid())['code'])->toBe('BUSINESS_AUTHORITY_RECORDED');
    };
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

it('reports only newly written command-local expiry subjects without recording feed rows', function (array $states): void {
    $expected = [];
    foreach ($states as $state) {
        $root = ($this->purchase)(in_array($state, ['released', 'expired'], true) ? 'held' : $state);
        if ($state === 'released') {
            $actor = CommandOperation::query()->whereKey($root->origin_operation_id)->sole()->actor_user_id;
            app(PrimaryCheckout::class)->release($actor, 1, $this->campaign->id, $root->id, 1, (string) Str::uuid());
        } elseif ($state === 'expired') {
            $this->travelTo($root->expires_at);
            app(PrimaryReservations::class)->expire($this->campaign->id, $root->id);
        }
        if (in_array($state, ['confirmed', 'held'], true)) {
            $expected[$root->id] = ['party_id' => $root->party_id, 'reservation_id' => $root->id];
        }
    }
    ksort($expected);
    $this->travelTo($this->campaign->expires_at);
    $feedCount = DB::table('change_feed')->count();
    $real = app(PrimaryReservations::class);
    $subjects = null;
    $primary = Mockery::mock(PrimaryReservations::class);
    $settlement = $primary->shouldReceive('settleExpiredCampaign');
    $returns = $primary->shouldReceive('lockReturnedCampaign');
    if (! $settlement instanceof CompositeExpectation || ! $returns instanceof CompositeExpectation) {
        throw new LogicException('Expected settlement capture expectations.');
    }
    $settlement->__call('once', [])->__call('andReturnUsing', [function (string $campaign, string $closure) use ($real, &$subjects, $feedCount): array {
        $subjects = $real->settleExpiredCampaign($campaign, $closure);
        expect(DB::table('change_feed')->count())->toBe($feedCount);

        return $subjects;
    }]);
    $returns->__call('once', [])->__call('andReturnUsing', [fn (string $campaign): PrimaryCampaignReturns => $real->lockReturnedCampaign($campaign)]);
    app()->instance(PrimaryReservations::class, $primary);
    $store = app(BusinessCampaignStore::class);
    expect($store->expireDue(1))->toBe(1)->and($subjects)->toBe(array_values($expected));
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    expect($store->expireDue(1))->toBe(0)
        ->and(DB::table('change_feed')->count())->toBe($feedCount + 1)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash);
})->with([
    'empty campaign' => [[]],
    'previous returns only' => [['refunded', 'released', 'expired']],
    'new and previous returns' => [['confirmed', 'held', 'refunded', 'released', 'expired']],
]);

it('rolls back observed expiry subjects with every cash return when the closing caller fails', function (): void {
    ($this->purchase)();
    ($this->purchase)('held');
    $this->travelTo($this->campaign->expires_at);
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $versions = PrimaryReservationVersion::query()->orderBy('id')->get()->toJson();
    $feed = DB::table('change_feed')->orderBy('id')->get()->toJson();
    $real = app(PrimaryReservations::class);
    $subjects = null;
    $primary = Mockery::mock(PrimaryReservations::class);
    $settlement = $primary->shouldReceive('settleExpiredCampaign');
    $returns = $primary->shouldReceive('lockReturnedCampaign');
    if (! $settlement instanceof CompositeExpectation || ! $returns instanceof CompositeExpectation) {
        throw new LogicException('Expected settlement capture expectations.');
    }
    $settlement->__call('once', [])->__call('andReturnUsing', [function (string $campaign, string $closure) use ($real, &$subjects): array {
        return $subjects = $real->settleExpiredCampaign($campaign, $closure);
    }]);
    $returns->__call('once', [])->__call('andThrow', [new RuntimeException('CLOSING_CALLER_FAILURE')]);
    app()->instance(PrimaryReservations::class, $primary);
    expect(fn () => app(BusinessCampaignStore::class)->expireDue(1))->toThrow(RuntimeException::class, 'CLOSING_CALLER_FAILURE')
        ->and($subjects)->toHaveCount(2)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(PrimaryReservationVersion::query()->orderBy('id')->get()->toJson())->toBe($versions)
        ->and(DB::table('change_feed')->orderBy('id')->get()->toJson())->toBe($feed)
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0);
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

it('settles returned purchases and remaining committed cash independently of later authority', function (bool $allReturned, string $change): void {
    $returned = ($this->purchase)('refunded', '1080');
    ($this->purchase)($allReturned ? 'refunded' : 'confirmed', '1080');
    ($this->changeAuthority)($change, $returned->party_id);
    $commitments = PrimaryCommitment::query()->orderBy('id')->get()->toJson();
    $confirmations = PrimaryReservationVersion::query()->where('state', 'confirmed')->orderBy('id')->get()->toJson();
    $this->travelTo($this->campaign->expires_at);
    expect($this->store->expireDue(1))->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $closure = BusinessCampaignClosure::query()->sole();
    expect($closure->phase)->toBe('expired')
        ->and($closure->payload['committed_refunded'])->toBe(['currency' => 'RWF', 'amount' => '10800000'])
        ->and($closure->payload['cash_returns'])->toHaveCount(2)
        ->and(DB::table('primary_campaign_expiry_settlements')->sole()->business_campaign_closure_id)->toBe($closure->id)
        ->and(DB::table('business_campaign_expiry_failures')->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(2)
        ->and(DB::table('primary_campaign_fundings')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe([])
        ->and(PrimaryCommitment::query()->orderBy('id')->get()->toJson())->toBe($commitments)
        ->and(PrimaryReservationVersion::query()->where('state', 'confirmed')->orderBy('id')->get()->toJson())->toBe($confirmations);
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    expect($this->store->expireDue(1))->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash);
})->with([true, false])->with(['current', 'revoked', 'expired', 'future', 'connected']);

it('defers complete original cash without waiving current funding connections', function (string $change): void {
    $root = ($this->purchase)('confirmed', '1080');
    ($this->purchase)('confirmed', '1080');
    ($this->changeAuthority)($change, $root->party_id);
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $this->travelTo($this->campaign->expires_at);
    if ($change === 'current') {
        expect(app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id)->purchases)->toHaveCount(2);
    } else {
        expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))
            ->toThrow(CommandRejection::class, $change === 'connected' ? 'CONNECTED_BUSINESS_INVESTMENT_PROHIBITED' : 'MANDATE_REQUIRED');
    }
    expect($this->store->expireDue(1))->toBe(0)
        ->and(fn () => app(PrimaryReservations::class)->settleExpiredCampaign($this->campaign->id, strtolower((string) Str::ulid())))
        ->toThrow(CommandRejection::class, 'CAMPAIGN_SETTLEMENT_REQUIRED')
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(0)
        ->and(DB::table('business_campaign_expiry_failures')->count())->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toHaveCount(1);
})->with(['current', 'revoked', 'expired', 'future', 'connected']);

it('keeps damaged returned cash unavailable for system expiry even after mandate revocation', function (): void {
    $returned = ($this->purchase)('refunded', '1080');
    ($this->purchase)('confirmed', '1080');
    ($this->changeAuthority)('revoked', $returned->party_id);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    DB::table('ledger_entries')->where('source_id', $returned->id)->where('kind', 'primary_refund')
        ->update(['origin_operation_id' => $returned->id]);
    DB::statement('ALTER TABLE ledger_entries ENABLE TRIGGER USER');
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $this->travelTo($this->campaign->expires_at);
    expect(fn () => $this->store->expireDue(1))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toHaveCount(1);
});

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
