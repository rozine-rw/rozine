<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaignClosure;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => 'CAMPAIGN_SETTLEMENT_REQUIRED', 'revision' => 1])
        ->and($cancel())->toBe($result)
        ->and($this->campaigns->findCancellation($this->campaign->actor_user_id, 1, $request))->toBe($result)
        ->and($this->campaigns->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id)['can_cancel'])->toBeFalse()
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($this->cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($this->exposure);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('fails the legacy expiry sweep closed instead of releasing committed campaign exposure', function (): void {
    $this->travelTo($this->campaign->expires_at);
    expect(fn () => $this->campaigns->expireDue(100))->toThrow(CommandRejection::class, 'CAMPAIGN_SETTLEMENT_REQUIRED')
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($this->cash)
        ->and(app(BusinessExposureStore::class)->current($this->campaign->business_id))->toBe($this->exposure);
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
