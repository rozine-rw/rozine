<?php

declare(strict_types=1);

/*
 * Review repros for PR #175 range 12466d27..f0de47b8 (S3-C K4 application gate). Copy into tests/Feature/.
 * Tests named "P*:" assert the SAFE behaviour, so a failure on f0de47b8 confirms a finding.
 * Tests named "control:" assert what the increment claims and must pass.
 * Tests named "observe:" pin current behaviour that is a known limit or pre-existing, and pass.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Wallet\GetInvestorWallet;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->campaigns = app(BusinessCampaignStore::class);
    $this->reserve = function (string $units = '3'): PrimaryReservationRecord {
        $held = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($held['code'])->toBe('RESERVATION_HELD');

        return PrimaryReservationRecord::query()->whereKey($held['data']['reservation_id'])->sole();
    };
    $this->confirm = function (PrimaryReservationRecord $root, int $revision = 1): array {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->first();

        return $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $root->id, $revision,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    };
    $this->cancel = fn (?string $request = null, ?string $reason = 'After confirm.'): array => $this->campaigns->cancel(
        $this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id, 1, $reason, $request ?? (string) Str::uuid());
    $this->breakdown = fn (): array => app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown'];
    $this->exposure = fn (string $business): array => app(BusinessExposureStore::class)->current($business);
    $this->secondCampaign = function (): BusinessCampaign {
        // The retained audit fixture uses one synthetic credential across its test users.
        Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));

        return PrimaryReservationFixture::campaign();
    };
});

/* K4 (from review175k), adapted: cancel after a confirmed commitment must refuse with CAMPAIGN_SETTLEMENT_REQUIRED. */
it('control K4: cancellation after a confirmed commitment refuses and preserves exposure, commitment and cash', function (): void {
    expect(($this->confirm)(($this->reserve)())['code'])->toBe('RESERVATION_CONFIRMED');
    $cash = LedgerEntry::query()->orderBy('id')->get()->toArray();
    $exposure = ($this->exposure)($this->campaign->business_id);
    $cancelled = ($this->cancel)();
    expect($cancelled)->toMatchArray(['status' => 'rejected', 'code' => 'CAMPAIGN_SETTLEMENT_REQUIRED', 'revision' => 1])
        ->and($cancelled['data'])->toMatchArray(['campaign_id' => $this->campaign->id, 'business_id' => $this->campaign->business_id])
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1)
        ->and(($this->breakdown)()['committed']['amount'])->toBe('15000')
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($cash)
        ->and(($this->exposure)($this->campaign->business_id))->toBe($exposure)->not->toBe([]);
});

it('control K4: the expiry sweep refuses a committed campaign and preserves its exposure', function (): void {
    expect(($this->confirm)(($this->reserve)())['code'])->toBe('RESERVATION_CONFIRMED');
    $exposure = ($this->exposure)($this->campaign->business_id);
    $this->travelTo($this->campaign->expires_at);
    expect(fn () => $this->campaigns->expireDue(100))->toThrow(CommandRejection::class, 'CAMPAIGN_SETTLEMENT_REQUIRED')
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(($this->exposure)($this->campaign->business_id))->toBe($exposure);
});

/*
 * P2-1. Head-of-line: the sweep processes due campaigns in (expires_at, id) order and one committed campaign
 * throws out of the loop. Every campaign that expires after it, for any Business, is never expired and keeps
 * its exposure reserved, on every scheduled run.
 */
it('P2-1: an investor-free campaign still expires when an earlier-expiring campaign has commitments', function (): void {
    expect(($this->confirm)(($this->reserve)())['code'])->toBe('RESERVATION_CONFIRMED');
    $this->travel(1)->minute();
    $other = ($this->secondCampaign)();
    expect($other->expires_at->gt($this->campaign->expires_at))->toBeTrue();
    $this->travelTo($other->expires_at);
    $failure = null;
    try {
        $this->campaigns->expireDue(100);
    } catch (CommandRejection $exception) {
        $failure = $exception->reason;
    }
    try {
        $artisan = Artisan::call('campaigns:expire');
    } catch (CommandRejection $exception) {
        $artisan = 'threw '.$exception->reason;
    }
    expect(BusinessCampaignClosure::query()->where('business_campaign_id', $other->id)->exists())
        ->toBeTrue('sweep aborted with '.var_export($failure, true).'; artisan exit '.$artisan.'; other campaign still unexpired with exposure '
            .json_encode(($this->exposure)($other->business_id)));
});

/* P2-1 companion: the limit cannot help, the committed campaign stays at the head of every scan. */
it('observe: the same committed campaign heads every sweep scan', function (): void {
    expect(($this->confirm)(($this->reserve)())['code'])->toBe('RESERVATION_CONFIRMED');
    $this->travel(1)->minute();
    $other = ($this->secondCampaign)();
    $this->travelTo($other->expires_at->addDay());
    foreach ([1, 2, 1000] as $limit) {
        expect(fn () => $this->campaigns->expireDue($limit))->toThrow(CommandRejection::class, 'CAMPAIGN_SETTLEMENT_REQUIRED');
    }
    expect(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(($this->exposure)($other->business_id))->toBe([['id' => $other->exposure_reservation_id, 'principal' => $other->principal]]);
});

/* Held (unconfirmed) reservations are not commitments: cancellation proceeds and leaves the hold to the Investor. */
it('observe: cancelling with only a held reservation closes the campaign and leaves the hold held', function (): void {
    $root = ($this->reserve)();
    $cancelled = ($this->cancel)();
    expect($cancelled['code'])->toBe('CAMPAIGN_CANCELLED')
        ->and($cancelled['data']['receipt']['investors'])->toBe(0)
        ->and(($this->breakdown)()['held']['amount'])->toBe('15000')
        ->and(($this->confirm)($root)['code'])->toBe('CAMPAIGN_CLOSED')
        ->and(($this->breakdown)()['held']['amount'])->toBe('15000');
    $released = $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $root->id, 1, (string) Str::uuid());
    expect($released['code'])->toBe('RESERVATION_RELEASED')
        ->and(($this->breakdown)()['held']['amount'])->toBe('0');
});

it('observe: after cancellation an expired hold is released only when the Investor next acts', function (): void {
    $root = ($this->reserve)();
    expect(($this->cancel)()['code'])->toBe('CAMPAIGN_CANCELLED');
    $this->travelTo($root->expires_at->addHour());
    expect(($this->breakdown)()['held']['amount'])->toBe('15000')
        ->and(($this->confirm)($root))->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED'])
        ->and(($this->breakdown)()['held']['amount'])->toBe('0');
});

/* Replay of the rejected receipt must stay byte-stable while commitments grow and the deadline passes. */
it('control: the rejected cancellation receipt replays unchanged after more commitments and after expiry', function (): void {
    expect(($this->confirm)(($this->reserve)())['code'])->toBe('RESERVATION_CONFIRMED');
    $request = (string) Str::uuid();
    $first = ($this->cancel)($request);
    expect($first['code'])->toBe('CAMPAIGN_SETTLEMENT_REQUIRED');
    $this->travel(1)->minute();
    expect(($this->confirm)(($this->reserve)('2'))['code'])->toBe('RESERVATION_CONFIRMED')
        ->and(PrimaryCommitment::query()->count())->toBe(2)
        ->and(($this->cancel)($request))->toBe($first);
    $this->travelTo($this->campaign->expires_at->addDay());
    expect(($this->cancel)($request))->toBe($first)
        ->and($this->campaigns->findCancellation($this->campaign->actor_user_id, 1, $request))->toBe($first)
        ->and(fn () => ($this->cancel)($request, 'Different reason.'))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(($this->cancel)()['code'])->toBe('CAMPAIGN_CLOSED')
        ->and(CommandOperation::query()->where('command', 'campaign.cancel')->count())->toBe(2);
});

/* can_cancel on the HTTP surfaces: the resource and the Inertia props hide the action; the POST refuses. */
it('control: web and API hide cancellation once a commitment exists and the POST returns the refusal', function (): void {
    $signatory = User::query()->findOrFail($this->campaign->actor_user_id);
    $parameters = ['business' => $this->campaign->business_id, 'campaign' => $this->campaign->id];
    $this->actingAs($signatory)->get(route('business.campaigns.show', $parameters))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('actions.cancel', fn ($value): bool => $value !== null)->where('allowed_actions', ['campaign.cancel']));
    expect(($this->confirm)(($this->reserve)())['code'])->toBe('RESERVATION_CONFIRMED');
    $this->get(route('business.campaigns.show', $parameters))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('actions.cancel', null)->where('allowed_actions', []));
    $this->postJson(route('business.campaigns.cancel', $parameters), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
        'campaign_id' => $this->campaign->id, 'expected_campaign_revision' => 1, 'reason' => null])
        ->assertStatus(409)->assertJsonPath('code', 'CAMPAIGN_SETTLEMENT_REQUIRED')
        ->assertJsonPath('data.current.actions.cancel', null)->assertJsonPath('data.current.allowed_actions', []);
    expect(BusinessCampaignClosure::query()->count())->toBe(0);
});

/*
 * P3-1. The Business campaign page still reports an investor-free raise: progress says 0 committed and
 * 0 investors, lifecycle stays "live" past the deadline, and the hidden cancel action has no explanation.
 */
it('P3-1: the Business campaign page reflects retained commitments', function (): void {
    expect(($this->confirm)(($this->reserve)())['code'])->toBe('RESERVATION_CONFIRMED');
    $this->travelTo($this->campaign->expires_at->addDay());
    $page = $this->campaigns->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    expect(['lifecycle' => $page['lifecycle'], 'committed' => $page['progress']['committed']['amount'] ?? null, 'investors' => $page['progress']['investors']])
        ->toBe(['lifecycle' => 'expired', 'committed' => '15000', 'investors' => 1]);
});

/* Known limit (claimed): a raw closure insert is not blocked by any database rule while commitments remain. */
it('observe: a raw SQL closure insert is accepted for a campaign with a confirmed commitment', function (): void {
    expect(($this->confirm)(($this->reserve)())['code'])->toBe('RESERVATION_CONFIRMED');
    $now = now('UTC')->startOfSecond();
    DB::insert('INSERT INTO business_campaign_closures (id, business_campaign_id, business_id, exposure_reservation_id, principal, phase, actor_user_id, closed_at, payload, sha256, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [(string) Str::ulid(), $this->campaign->id, $this->campaign->business_id, $this->campaign->exposure_reservation_id,
        $this->campaign->principal, 'cancelled', $this->campaign->actor_user_id, $now, '{}', str_repeat('0', 64), $now]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(BusinessCampaignClosure::query()->count())->toBe(1)->and(PrimaryCommitment::query()->count())->toBe(1);
});
