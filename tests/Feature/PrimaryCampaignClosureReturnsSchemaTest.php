<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\BusinessCampaignClosure;
use App\Models\Party;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $reserved = $checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $this->root->id)->sole();
    $checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1,
        $held->payload['terms']['disclosure_version'], $held->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $checkout->refund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 2, (string) Str::uuid());
    DB::beginTransaction();
    try {
        app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id, 1, null, (string) Str::uuid());
        $this->closure = BusinessCampaignClosure::query()->sole()->getAttributes();
        $this->binding = (array) DB::table('primary_campaign_closure_returns')->sole();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    } finally {
        DB::rollBack();
    }
    $this->retain = function (array $changes = [], bool $bindingFirst = false): void {
        if ($bindingFirst) {
            DB::table('primary_campaign_closure_returns')->insert([...$this->binding, ...$changes]);
        }
        DB::table('business_campaign_closures')->insert($this->closure);
        if (! $bindingFirst) {
            DB::table('primary_campaign_closure_returns')->insert([...$this->binding, ...$changes]);
        }
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    };
});

it('accepts exact complete cash-return bindings in either outer transaction insertion order', function (bool $bindingFirst): void {
    ($this->retain)(bindingFirst: $bindingFirst);
    expect(DB::table('primary_campaign_closure_returns')->count())->toBe(1)
        ->and(BusinessCampaignClosure::query()->count())->toBe(1);
})->with([false, true]);

it('refuses a closure with omitted cash-return membership at the outer constraint gate', function (): void {
    expect(fn () => DB::transaction(function (): void {
        DB::table('business_campaign_closures')->insert($this->closure);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, 'complete unfunded cash-return membership');
    expect(BusinessCampaignClosure::query()->count())->toBe(0);
});

it('refuses a cash-return binding with no closure', function (): void {
    expect(fn () => DB::transaction(function (): void {
        DB::table('primary_campaign_closure_returns')->insert($this->binding);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class);
    expect(DB::table('primary_campaign_closure_returns')->count())->toBe(0);
});

it('refuses forged purchase version Party wallet principal operation or cash identities', function (string $field): void {
    $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $this->root->id)->where('state', 'held')->sole();
    $changes = match ($field) {
        'version' => ['primary_reservation_version_id' => $held->id],
        'digest' => ['version_sha256' => str_repeat('0', 64)],
        'party' => ['party_id' => Party::factory()->create()->id],
        'wallet' => ['wallet_id' => (string) Str::ulid()],
        'principal' => ['principal' => '5000'],
        'operation' => ['origin_operation_id' => $held->id],
        'hold' => ['hold_entry_id' => $this->binding['return_entry_id']],
        'commit' => ['commit_entry_id' => $this->binding['hold_entry_id']],
        'return' => ['return_entry_id' => $this->binding['hold_entry_id']],
        'kind' => ['return_kind' => 'primary_release'],
        'missing_commit' => ['commit_entry_id' => null],
        'missing_commitment' => ['primary_commitment_id' => null],
        default => throw new LogicException('Unknown forgery.'),
    };
    expect(fn () => DB::transaction(fn () => ($this->retain)($changes)))->toThrow(QueryException::class);
    expect(BusinessCampaignClosure::query()->count())->toBe(0)->and(DB::table('primary_campaign_closure_returns')->count())->toBe(0);
})->with(['version', 'digest', 'party', 'wallet', 'principal', 'operation', 'hold', 'commit', 'return', 'kind', 'missing_commit', 'missing_commitment']);

it('cannot omit another live hold or unreturned confirmation from closure membership', function (bool $confirmed): void {
    $checkout = app(PrimaryCheckout::class);
    $result = $checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '4', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    if ($confirmed) {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $result['data']['reservation_id'])->sole();
        $checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $result['data']['reservation_id'], 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    }
    expect(fn () => DB::transaction(fn () => ($this->retain)()))->toThrow(QueryException::class, 'complete unfunded cash-return membership');
})->with([false, true]);

it('protects retained closure cash returns from mutation deletion duplication and rollback', function (string $case): void {
    ($this->retain)();
    expect(fn () => DB::transaction(function () use ($case): void {
        match ($case) {
            'update' => DB::table('primary_campaign_closure_returns')->update(['principal' => '5000']),
            'delete' => DB::table('primary_campaign_closure_returns')->delete(),
            'duplicate' => DB::table('primary_campaign_closure_returns')->insert($this->binding),
            'rollback' => (require database_path('migrations/2026_09_30_094556_bind_campaign_closures_to_complete_primary_returns.php'))->down(),
            default => throw new LogicException('Unknown mutation.'),
        };
    }))->toThrow(QueryException::class);
})->with(['update', 'delete', 'duplicate', 'rollback']);

it('restores an empty closure-binding schema without weakening its checks', function (): void {
    $migration = require database_path('migrations/2026_09_30_094556_bind_campaign_closures_to_complete_primary_returns.php');
    $migration->down();
    expect(Schema::hasTable('primary_campaign_closure_returns'))->toBeFalse();
    $migration->up();
    expect(Schema::hasTable('primary_campaign_closure_returns'))->toBeTrue();
    ($this->retain)();
    expect(DB::table('primary_campaign_closure_returns')->count())->toBe(1);
});

it('audits historical closures and refuses silently inventing missing cash-return bindings', function (): void {
    $migration = require database_path('migrations/2026_09_30_094556_bind_campaign_closures_to_complete_primary_returns.php');
    $migration->down();
    DB::table('business_campaign_closures')->insert($this->closure);
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'complete unfunded cash-return membership')
        ->and(Schema::hasTable('primary_campaign_closure_returns'))->toBeFalse()
        ->and(BusinessCampaignClosure::query()->count())->toBe(1);
});
