<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaignClosure;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery\CompositeExpectation;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $version = PrimaryReservationVersion::query()->sole();
    expect($checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])
        ->toBe('RESERVATION_CONFIRMED');
    $this->campaigns = app(BusinessCampaignStore::class);
    $this->exposure = app(BusinessExposureStore::class)->current($this->campaign->business_id);
    $this->cash = LedgerEntry::query()->orderBy('id')->get()->toArray();
});

it('refuses and replays cancellation until confirmed commitments can be settled atomically', function (): void {
    $request = (string) Str::uuid();
    $cancel = fn (): array => $this->campaigns->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, 'Cancel after confirmation.', $request);
    $result = $cancel();
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => 'CAMPAIGN_SETTLEMENT_REQUIRED', 'revision' => 1,
        'data' => ['campaign_id' => $this->campaign->id, 'business_id' => $this->campaign->business_id]])
        ->and($cancel())->toBe($result)
        ->and($this->campaigns->findCancellation($this->campaign->actor_user_id, 1, $request))->toBe($result)
        ->and($this->campaigns->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id)['can_cancel'])->toBeFalse()
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($this->cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($this->exposure);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('reports deferred settlement without releasing committed campaign exposure', function (): void {
    $this->travelTo($this->campaign->expires_at);
    Event::fake([MessageLogged::class]);
    expect($this->campaigns->expireDue(100))->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($this->cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($this->exposure);
    Event::assertDispatchedTimes(MessageLogged::class, 1);
    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'notice'
        && $event->message === 'Campaign expiry deferred until commitments are settled.'
        && $event->context === ['campaign_id' => $this->campaign->id, 'business_id' => $this->campaign->business_id, 'code' => 'CAMPAIGN_SETTLEMENT_REQUIRED']);
});

it('keeps investor-free closure available for a different campaign', function (): void {
    // The retained audit fixture uses one synthetic credential across its test users.
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $other = PrimaryReservationFixture::campaign();
    expect($this->campaigns->cancel($other->actor_user_id, 1, $other->business_id, $other->id, 1, null, (string) Str::uuid())['code'])
        ->toBe('CAMPAIGN_CANCELLED')
        ->and(BusinessCampaignClosure::query()->sole()->business_campaign_id)->toBe($other->id)
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($this->exposure)
        ->and(app(BusinessExposureStore::class)->current($other->business_id))->toBe([]);
});

it('advances past committed campaigns without consuming the closure limit or skipping later businesses', function (int $delay): void {
    $this->travel($delay)->seconds();
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $second = PrimaryReservationFixture::campaign();
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $third = PrimaryReservationFixture::campaign();
    $cash = LedgerEntry::query()->orderBy('id')->get()->toArray();
    $this->travelTo($third->expires_at);
    Event::fake([MessageLogged::class]);
    expect(Artisan::call('campaigns:expire', ['--limit' => 1]))->toBe(0)
        ->and(Artisan::output())->toContain('Expired 1 campaigns.')
        ->and(BusinessCampaignClosure::query()->sole()->business_campaign_id)->toBe($second->id)
        ->and(app(BusinessExposureStore::class)->current($second->business_id))->toBe([])
        ->and(app(BusinessExposureStore::class)->current($third->business_id))->toHaveCount(1)
        ->and($this->campaigns->expireDue(2))->toBe(1)
        ->and(BusinessCampaignClosure::query()->count())->toBe(2)
        ->and(app(BusinessExposureStore::class)->current($third->business_id))->toBe([])
        ->and($this->campaigns->expireDue(1))->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($this->exposure);
    Event::assertDispatchedTimes(MessageLogged::class, 3);
    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'notice'
        && $event->message === 'Campaign expiry deferred until commitments are settled.'
        && $event->context === ['campaign_id' => $this->campaign->id, 'business_id' => $this->campaign->business_id, 'code' => 'CAMPAIGN_SETTLEMENT_REQUIRED']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with([0, 1]);

it('propagates unrelated command refusals during expiry', function (): void {
    $this->travelTo($this->campaign->expires_at);
    $primary = Mockery::mock(PrimaryReservations::class);
    $expectation = $primary->shouldReceive('lockReturnedCampaign');
    if (! $expectation instanceof CompositeExpectation) {
        throw new LogicException('Expected a method expectation.');
    }
    $expectation->__call('once', [])->__call('andThrow', [new CommandRejection('UNRELATED_REFUSAL')]);
    app()->instance(PrimaryReservations::class, $primary);
    expect(fn () => app(BusinessCampaignStore::class)->expireDue(1))->toThrow(CommandRejection::class, 'UNRELATED_REFUSAL')
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($this->cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($this->exposure);
});

it('retains the closed campaign refusal after the deadline even with commitments', function (): void {
    $this->travelTo($this->campaign->expires_at);
    $result = $this->campaigns->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, null, (string) Str::uuid());
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => 'CAMPAIGN_CLOSED', 'revision' => 1,
        'data' => ['campaign_id' => $this->campaign->id, 'business_id' => $this->campaign->business_id]])
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($this->cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($this->exposure);
});
