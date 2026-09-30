<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Brick\Math\BigInteger;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/* Review #175 range 8d500fc7..d701d879 (progress/lifecycle). PROBE = expected to hold; OBSERVE = pins a finding. */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = function (array $investor, string $units): PrimaryReservationRecord {
        $result = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($result['code'] ?? 'accepted')->not->toBe('UNITS_UNAVAILABLE');

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->confirm = function (array $investor, PrimaryReservationRecord $root): void {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
        expect($this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, $version->revision,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    };
    $this->release = fn (array $investor, PrimaryReservationRecord $root) => $this->checkout->release($investor['user']->id, 1, $this->campaign->id, $root->id, 1, (string) Str::uuid());
    $this->page = fn (): array => app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    /* Every invariant the contract promises, checked against an independent recount from the summary port. */
    $this->reconciles = function (array $page): string {
        $p = $page['progress'];
        $s = app(CampaignReservationSummary::class)->read($this->campaign->id, now('UTC')->toDateTimeImmutable());
        $u = $p['units'];
        $units = BigInteger::of($u['committed'])->plus($u['reserved'])->plus($u['available'])->plus($u['unavailable']);
        $money = BigInteger::of($p['committed']['amount'])->plus($p['reserved']['amount'])->plus($p['remaining']['amount']);
        $returnedPlusOverdue = BigInteger::of($s['returned_units'])->plus($s['expired_hold_units']);
        expect((string) $units)->toBe($u['total'])->and((string) $money)->toBe($page['principal'])
            ->and($p['remaining']['amount'])->toBe((string) BigInteger::of($page['principal'])->minus($s['committed_principal'])->minus($s['held_principal']))
            ->and($u['unavailable'])->toBe((string) $returnedPlusOverdue)
            ->and((string) BigInteger::of($u['committed'])->multipliedBy(5000))->toBe($p['committed']['amount'])
            ->and((string) BigInteger::of($u['reserved'])->multipliedBy(5000))->toBe($p['reserved']['amount'])
            ->and($page['lifecycle'])->toBe($p['lifecycle']);

        return $p['lifecycle'].' can_cancel='.json_encode($page['can_cancel']).' units='.json_encode($u).' remaining='.$p['remaining']['amount'];
    };
});

it('PROBE reconciles a mixed campaign with live, overdue-unswept, swept, released and confirmed roots through the deadline', function (): void {
    $a = PrimaryReservationFixture::investor();
    $b = PrimaryReservationFixture::investor();
    ($this->confirm)($a, ($this->reserve)($a, '3'));
    ($this->confirm)($b, ($this->reserve)($b, '2'));
    ($this->release)($a, ($this->reserve)($a, '5'));
    $swept = ($this->reserve)($b, '6');
    $overdue = ($this->reserve)($a, '7');
    $this->travelTo($overdue->expires_at);
    expect(app(PrimaryReservations::class)->expireDue(1))->toBe(1);
    ($this->reserve)($b, '4');
    $log = ['open: '.($this->reconciles)(($this->page)())];
    expect(($this->page)()['lifecycle'])->toBe('live');
    $this->travelTo($this->campaign->expires_at);
    $log[] = 'deadline: '.($this->reconciles)(($this->page)());
    $this->artisan('campaigns:expire')->assertSuccessful();
    $this->artisan('primary:expire-reservations')->assertSuccessful();
    $page = ($this->page)();
    $log[] = 'after both sweeps: '.($this->reconciles)($page);
    fwrite(STDERR, implode("\n", $log)."\n");
    expect($page['lifecycle'])->toBe('closing_pending_settlement')->and($page['can_cancel'])->toBeFalse()
        ->and($page['progress']['reserved']['amount'])->toBe('0')->and($swept->id)->not->toBe($overdue->id);
});

it('PROBE lifecycle for mixed inventory corners', function (string $case, string $expected): void {
    $a = PrimaryReservationFixture::investor();
    $b = PrimaryReservationFixture::investor();
    $c = PrimaryReservationFixture::investor();
    match ($case) {
        'held_plus_burned' => [($this->reserve)($a, '1080'), ($this->release)($b, ($this->reserve)($b, '540')), ($this->reserve)($c, '540')],
        'committed_plus_burned' => [($this->confirm)($a, ($this->reserve)($a, '1080')), ($this->release)($b, ($this->reserve)($b, '1080'))],
        'committed_plus_held' => [($this->confirm)($a, ($this->reserve)($a, '1080')), ($this->reserve)($b, '1080')],
        'one_unit_left' => [($this->confirm)($a, ($this->reserve)($a, '1080')), ($this->reserve)($b, '1079')],
        'overdue_occupies_last_units' => [($this->confirm)($a, ($this->reserve)($a, '1080')), ($this->reserve)($b, '1080'), $this->travel(5)->minutes()],
    };
    $page = ($this->page)();
    fwrite(STDERR, $case.': '.($this->reconciles)($page)."\n");
    expect($page['lifecycle'])->toBe($expected);
})->with([
    ['held_plus_burned', 'fully_reserved'],
    ['committed_plus_burned', 'inventory_unavailable'],
    ['committed_plus_held', 'fully_reserved'],
    ['one_unit_left', 'live'],
    ['overdue_occupies_last_units', 'inventory_unavailable'],
]);

it('PROBE persisted closure wins over the deadline and inventory', function (string $closure): void {
    $a = PrimaryReservationFixture::investor();
    ($this->reserve)($a, '1080');
    if ($closure === 'cancelled') {
        $result = app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id, 1, null, (string) Str::uuid());
        expect($result['code'])->toBe('CAMPAIGN_CANCELLED');
        $this->travelTo($this->campaign->expires_at);
    } else {
        $this->travelTo($this->campaign->expires_at);
        expect(($this->page)()['lifecycle'])->toBe('closing_pending_settlement');
        $this->artisan('campaigns:expire')->assertSuccessful();
    }
    $page = ($this->page)();
    fwrite(STDERR, $closure.': top lifecycle='.$page['lifecycle'].' progress='.json_encode($page['progress'])."\n");
    expect($page['lifecycle'])->toBe($closure)->and($page['progress']['phase'])->toBe($closure)->and($page['can_cancel'])->toBeFalse();
})->with(['cancelled', 'expired']);

it('OBSERVE a closed campaign has campaign.lifecycle but no note.progress.lifecycle on both transports', function (string $transport): void {
    $this->travelTo($this->campaign->expires_at);
    $this->artisan('campaigns:expire')->assertSuccessful();
    $parameters = ['business' => $this->campaign->business_id, 'campaign' => $this->campaign->id];
    $actor = User::query()->findOrFail($this->campaign->actor_user_id);
    if ($transport === 'token') {
        Sanctum::actingAs($actor, ['business:read']);
        $json = $this->getJson(route('api.v1.business.campaigns.show', $parameters))->assertOk()->json('data');
        fwrite(STDERR, "token closed: campaign.lifecycle={$json['campaign']['lifecycle']} note.progress.lifecycle=".json_encode($json['note']['progress']['lifecycle'] ?? null)."\n");
        expect($json['campaign']['lifecycle'])->toBe('expired')->and($json['note']['progress'])->not->toHaveKey('lifecycle');
    } else {
        $this->actingAs($actor);
        $this->get(route('business.campaigns.show', $parameters))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('campaign.lifecycle', 'expired')->where('note.progress.phase', 'expired')->missing('note.progress.lifecycle'));
    }
})->with(['browser', 'token']);

it('PROBE every open state is identical on campaign.lifecycle and note.progress.lifecycle over both transports', function (string $state, string $transport): void {
    $a = PrimaryReservationFixture::investor();
    $b = PrimaryReservationFixture::investor();
    match ($state) {
        'fully_reserved' => [($this->reserve)($a, '1080'), ($this->reserve)($b, '1080')],
        'sold_out_pending_settlement' => [($this->confirm)($a, ($this->reserve)($a, '1080')), ($this->confirm)($b, ($this->reserve)($b, '1080'))],
        'inventory_unavailable' => [($this->release)($a, ($this->reserve)($a, '1080')), ($this->release)($b, ($this->reserve)($b, '1080'))],
        'closing_pending_settlement' => [($this->confirm)($a, ($this->reserve)($a, '3')), $this->travelTo($this->campaign->expires_at->addDay())],
        'live' => ($this->reserve)($a, '3'),
    };
    $parameters = ['business' => $this->campaign->business_id, 'campaign' => $this->campaign->id];
    $actor = User::query()->findOrFail($this->campaign->actor_user_id);
    if ($transport === 'token') {
        Sanctum::actingAs($actor, ['business:read']);
        $this->getJson(route('api.v1.business.campaigns.show', $parameters))->assertOk()
            ->assertJsonPath('data.campaign.lifecycle', $state)->assertJsonPath('data.note.progress.lifecycle', $state);
    } else {
        $this->actingAs($actor);
        $this->get(route('business.campaigns.show', $parameters))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('campaign.lifecycle', $state)->where('note.progress.lifecycle', $state));
    }
})->with(['live', 'fully_reserved', 'sold_out_pending_settlement', 'inventory_unavailable', 'closing_pending_settlement'])->with(['browser', 'token']);
