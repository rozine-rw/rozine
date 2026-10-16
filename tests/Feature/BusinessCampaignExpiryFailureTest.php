<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\AuditSealingFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    PrimaryReservationFixture::campaign();
    $other = AuditSealingFixture::ready();
    $totp = new Google2FA;
    $secret = $totp->generateSecretKey();
    $other['user']->forceFill(['two_factor_secret' => encrypt($secret)])->save();
    $other['code'] = $totp->getCurrentOtp($secret);
    AuditSealingFixture::seal($other);
    AuditSealingFixture::cosign($other);
    $store = app(BusinessCampaignStore::class);
    $store->release($other['audit']['staff']->id, $other['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    $store->publish($other['audit']['authority']['users'][0]->id, 1, $other['audit']['business'], $other['application']->id,
        $other['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    [$this->first, $this->second] = BusinessCampaign::query()->orderBy('expires_at')->orderBy('id')->get()->all();
    $this->travelTo($this->second->expires_at);
    $this->store = app(BusinessCampaignStore::class);
});

it('defers complete committed cash without recording failure or stopping healthy campaign closure', function (): void {
    $this->travelTo($this->first->created_at);
    InvestorWalletFixture::policy(maximum: null);
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $reserved = $checkout->reserve($investor['user']->id, 1, $this->first->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
    expect($checkout->confirm($investor['user']->id, 1, $this->first->id, $root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(),
        PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    $otherInvestor = PrimaryReservationFixture::investor();
    $reserved = $checkout->reserve($otherInvestor['user']->id, 1, $this->first->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $otherRoot = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $otherVersion = PrimaryReservationVersion::query()->where('primary_reservation_id', $otherRoot->id)->sole();
    expect($checkout->confirm($otherInvestor['user']->id, 1, $this->first->id, $otherRoot->id, 1,
        $otherVersion->payload['terms']['disclosure_version'], $otherVersion->payload['disclosure_sha256'], (string) Str::uuid(),
        PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $this->travelTo($this->second->expires_at);
    $log = Log::spy();
    expect($this->store->expireDue(1))->toBe(1)->and($this->store->expireDue(1))->toBe(0)
        ->and(BusinessCampaignClosure::query()->sole()->business_campaign_id)->toBe($this->second->id)
        ->and(app(BusinessExposureStore::class)->current($this->first->business_id))->toHaveCount(1)
        ->and(DB::table('business_campaign_expiry_failures')->count())->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash);
    $log->shouldHaveReceived('notice', ['Campaign expiry deferred until commitments are settled.', [
        'campaign_id' => $this->first->id, 'business_id' => $this->first->business_id, 'code' => 'CAMPAIGN_SETTLEMENT_REQUIRED',
    ]]);
    $log->shouldNotHaveReceived('error');
});

it('closes a healthy campaign after damaged publication and retries without losing exposure', function (): void {
    $digest = $this->first->sha256;
    $changeDigest = function (string $digest): void {
        DB::statement('ALTER TABLE business_campaigns DISABLE TRIGGER business_campaigns_protected');
        try {
            DB::table('business_campaigns')->where('id', $this->first->id)->update(['sha256' => $digest]);
        } finally {
            DB::statement('ALTER TABLE business_campaigns ENABLE TRIGGER business_campaigns_protected');
        }
    };
    $exposure = app(BusinessExposureStore::class)->current($this->first->business_id);
    $changeDigest(str_repeat('0', 64));
    expect(fn () => $this->store->expireDue(1))->toThrow(RuntimeException::class, 'CAMPAIGN_INTEGRITY_FAILED')
        ->and(BusinessCampaignClosure::query()->sole()->business_campaign_id)->toBe($this->second->id)
        ->and(app(BusinessExposureStore::class)->current($this->first->business_id))->toBe($exposure)
        ->and(app(BusinessExposureStore::class)->current($this->second->business_id))->toBe([]);
    $failure = DB::table('business_campaign_expiry_failures')->sole();
    expect($failure->business_campaign_id)->toBe($this->first->id)->and($failure->reason_code)->toBe('CAMPAIGN_INTEGRITY_FAILED');
    $changeDigest($digest);
    expect($this->store->expireDue(1))->toBe(1)->and($this->store->expireDue(1))->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(2)
        ->and(app(BusinessExposureStore::class)->current($this->first->business_id))->toBe([])
        ->and(DB::table('business_campaign_expiry_failures')->sole()->reason_code)->toBe('CAMPAIGN_INTEGRITY_FAILED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('rolls back a failed inserted closure and still commits the later healthy closure', function (): void {
    $failure = new RuntimeException('private persistence detail');
    $event = 'eloquent.created: '.BusinessCampaignClosure::class;
    Event::listen($event, function (BusinessCampaignClosure $closure) use ($failure): void {
        if ($closure->business_campaign_id === $this->first->id) {
            throw $failure;
        }
    });
    try {
        try {
            $this->store->expireDue(1);
            $this->fail('The original failure must remain visible to the caller.');
        } catch (Throwable $caught) {
            expect($caught)->toBe($failure);
        }
    } finally {
        Event::forget($event);
    }
    expect(BusinessCampaignClosure::query()->sole()->business_campaign_id)->toBe($this->second->id)
        ->and(app(BusinessExposureStore::class)->current($this->first->business_id))->toHaveCount(1)
        ->and(DB::table('business_campaign_expiry_failures')->sole()->reason_code)->toBe('UNCLASSIFIED_EXPIRY_FAILURE')
        ->and($this->store->expireDue(1))->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('records every failed candidate while propagating the first original failure', function (): void {
    $first = new RuntimeException('CAMPAIGN_INTEGRITY_FAILED');
    $second = new RuntimeException('CAMPAIGN_EXPOSURE_INTEGRITY_FAILED');
    $source = $this->createMock(PublishedCampaignEvidence::class);
    $source->expects($this->exactly(2))->method('find')->willReturnCallback(
        fn (string $id) => throw ($id === $this->first->id ? $first : $second));
    app()->instance(PublishedCampaignEvidence::class, $source);
    try {
        app(BusinessCampaignStore::class)->expireDue(1);
        $this->fail('The first original failure must be propagated.');
    } catch (Throwable $caught) {
        expect($caught)->toBe($first);
    }
    expect(DB::table('business_campaign_expiry_failures')->count())->toBe(2)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($this->first->business_id))->toHaveCount(1)
        ->and(app(BusinessExposureStore::class)->current($this->second->business_id))->toHaveCount(1);
});

it('retains only closed failure codes and explicit safe log context', function (string $type, string $reason, string $expected): void {
    $failure = match ($type) {
        'command' => new CommandRejection($reason, data: ['private' => 'private refusal detail']),
        'wallet' => new WalletViolation($reason),
        default => new RuntimeException($reason),
    };
    $source = app(PublishedCampaignEvidence::class);
    $mock = $this->createMock(PublishedCampaignEvidence::class);
    $mock->method('find')->willReturnCallback(fn (string $id): array => $id === $this->first->id ? throw $failure : $source->find($id));
    app()->instance(PublishedCampaignEvidence::class, $mock);
    $log = Log::spy();
    expect(fn () => app(BusinessCampaignStore::class)->expireDue(1))->toThrow($failure::class, $reason);
    $retained = DB::table('business_campaign_expiry_failures')->sole();
    expect($retained->business_campaign_id)->toBe($this->first->id)
        ->and($retained->exception_class)->toBe($failure::class)->and($retained->reason_code)->toBe($expected);
    $log->shouldHaveReceived('error', ['Business campaign expiry failed.', [
        'campaign_id' => $this->first->id, 'business_id' => $this->first->business_id,
        'exception_class' => $failure::class, 'reason_code' => $expected,
    ]]);
})->with([
    'integrity' => ['runtime', 'CAMPAIGN_INTEGRITY_FAILED', 'CAMPAIGN_INTEGRITY_FAILED'],
    'command refusal' => ['command', 'CAMPAIGN_NOT_FOUND', 'CAMPAIGN_NOT_FOUND'],
    'wallet refusal' => ['wallet', 'WALLET_POSTING_CONFLICT', 'WALLET_POSTING_CONFLICT'],
    'private runtime' => ['runtime', 'private exception message', 'UNCLASSIFIED_EXPIRY_FAILURE'],
    'private command' => ['command', 'private command reason', 'UNCLASSIFIED_EXPIRY_FAILURE'],
    'private wallet' => ['wallet', 'private wallet reason', 'UNCLASSIFIED_EXPIRY_FAILURE'],
]);

it('updates the existing failure reason and timestamp on a later unsuccessful retry', function (): void {
    $failures = [new RuntimeException('CAMPAIGN_INTEGRITY_FAILED'), new WalletViolation('WALLET_POSTING_CONFLICT')];
    $source = app(PublishedCampaignEvidence::class);
    $mock = $this->createMock(PublishedCampaignEvidence::class);
    $mock->method('find')->willReturnCallback(function (string $id) use ($source, &$failures): array {
        if ($id === $this->first->id) {
            throw array_shift($failures);
        }

        return $source->find($id);
    });
    app()->instance(PublishedCampaignEvidence::class, $mock);
    expect(fn () => app(BusinessCampaignStore::class)->expireDue(1))->toThrow(RuntimeException::class, 'CAMPAIGN_INTEGRITY_FAILED');
    $first = DB::table('business_campaign_expiry_failures')->sole();
    $this->travel(1)->second();
    expect(fn () => app(BusinessCampaignStore::class)->expireDue(1))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
    $second = DB::table('business_campaign_expiry_failures')->sole();
    expect($second->reason_code)->toBe('WALLET_POSTING_CONFLICT')->and($second->exception_class)->toBe(WalletViolation::class)
        ->and($second->last_attempted_at)->not->toBe($first->last_attempted_at)
        ->and(DB::table('business_campaign_expiry_failures')->count())->toBe(1);
});

it('allows an empty diagnostic migration roundtrip and refuses destructive rollback with retained failures', function (): void {
    $migration = require database_path('migrations/2026_09_30_184347_create_business_campaign_expiry_failures_table.php');
    $migration->down();
    expect(Schema::hasTable('business_campaign_expiry_failures'))->toBeFalse();
    $migration->up();
    expect(Schema::hasTable('business_campaign_expiry_failures'))->toBeTrue();
    DB::table('business_campaign_expiry_failures')->insert(['business_campaign_id' => $this->first->id,
        'last_attempted_at' => now('UTC'), 'exception_class' => RuntimeException::class, 'reason_code' => 'CAMPAIGN_INTEGRITY_FAILED']);
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'forward migration')
        ->and(DB::table('business_campaign_expiry_failures')->count())->toBe(1);
    expect(fn () => DB::transaction(fn () => DB::table('business_campaign_expiry_failures')->insert([
        'business_campaign_id' => (string) Str::ulid(), 'last_attempted_at' => now('UTC'),
        'exception_class' => RuntimeException::class, 'reason_code' => 'CAMPAIGN_INTEGRITY_FAILED',
    ])))->toThrow(QueryException::class);
});
