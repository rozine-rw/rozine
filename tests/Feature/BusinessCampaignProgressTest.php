<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Brick\Math\BigInteger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = function (string $units): PrimaryReservationRecord {
        $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->confirm = function (PrimaryReservationRecord $root): void {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
        $result = $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $root->id, $version->revision,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($result['code'])->toBe('RESERVATION_CONFIRMED');
    };
    $this->page = fn (): array => app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
});

it('reports real retained commitments and live holds without changing evidence', function (): void {
    ($this->confirm)(($this->reserve)('3'));
    ($this->confirm)(($this->reserve)('2'));
    ($this->reserve)('4');
    $released = ($this->reserve)('5');
    $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $released->id, 1, (string) Str::uuid());
    $cash = LedgerEntry::query()->count();
    $operations = CommandOperation::query()->count();
    $progress = ($this->page)()['progress'];
    expect($progress)->toMatchArray(['phase' => 'raising', 'lifecycle' => 'live', 'investors' => 1, 'funded_pct' => '0.2',
        'committed' => ['currency' => 'RWF', 'amount' => '25000'], 'reserved' => ['currency' => 'RWF', 'amount' => '20000'],
        'remaining' => ['currency' => 'RWF', 'amount' => '10775000'],
        'units' => ['total' => '2160', 'available' => '2146', 'reserved' => '4', 'committed' => '5']])
        ->and(($this->page)()['can_cancel'])->toBeFalse()
        ->and(LedgerEntry::query()->count())->toBe($cash)->and(CommandOperation::query()->count())->toBe($operations);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('removes overdue holds from reserved totals without advertising recycled capacity', function (): void {
    $root = ($this->reserve)('3');
    $this->travelTo($root->expires_at);
    $before = ($this->page)()['progress'];
    expect($before)->toMatchArray(['reserved' => ['currency' => 'RWF', 'amount' => '0'],
        'units' => ['total' => '2160', 'available' => '2157', 'reserved' => '0', 'committed' => '0']])
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0);
    expect(app(PrimaryReservations::class)->expireDue(1))->toBe(1)
        ->and(($this->page)()['progress'])->toBe($before);
});

it('never treats complete retained commitments as the funded settlement phase', function (bool $complete): void {
    $units = BigInteger::of($this->campaign->payload['quote']['units'])->dividedBy(2);
    ($this->confirm)(($this->reserve)((string) $units));
    $this->investor = PrimaryReservationFixture::investor();
    ($this->confirm)(($this->reserve)((string) ($complete ? $units : $units->minus(1))));
    expect(($this->page)()['progress'])->toMatchArray(['phase' => 'raising', 'investors' => 2, 'funded_pct' => $complete ? '100.0' : '99.9',
        'remaining' => ['currency' => 'RWF', 'amount' => $complete ? '0' : '5000'],
        'units' => ['total' => '2160', 'available' => $complete ? '0' : '1', 'reserved' => '0', 'committed' => $complete ? '2160' : '2159']]);
})->with([false, true]);

it('keeps another campaigns commitments out of the authorized progress', function (): void {
    ($this->confirm)(($this->reserve)('3'));
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $other = PrimaryReservationFixture::campaign();
    $page = app(BusinessCampaignStore::class)->campaign($other->actor_user_id, 1, $other->business_id, $other->id);
    expect($page['progress'])->toMatchArray(['investors' => 0, 'funded_pct' => '0.0', 'committed' => ['currency' => 'RWF', 'amount' => '0'],
        'units' => ['total' => '2160', 'available' => '2160', 'reserved' => '0', 'committed' => '0']]);
});

it('returns the same server totals through browser and token transports', function (string $transport): void {
    ($this->confirm)(($this->reserve)('3'));
    ($this->reserve)('4');
    $parameters = ['business' => $this->campaign->business_id, 'campaign' => $this->campaign->id];
    $actor = User::query()->findOrFail($this->campaign->actor_user_id);
    if ($transport === 'token') {
        Sanctum::actingAs($actor, ['business:read']);
    } else {
        $this->actingAs($actor);
    }
    if ($transport === 'browser') {
        $this->get(route('business.campaigns.show', $parameters))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('note.progress.committed.amount', '15000')->where('note.progress.reserved.amount', '20000')
            ->where('note.progress.units.available', '2153')->where('note.progress.investors', 1));
    } else {
        $this->getJson(route(($transport === 'token' ? 'api.v1.' : '').'business.campaigns.show', $parameters))->assertOk()
            ->assertJsonPath('data.note.progress.committed.amount', '15000')->assertJsonPath('data.note.progress.reserved.amount', '20000')
            ->assertJsonPath('data.note.progress.units.available', '2153')->assertJsonPath('data.note.progress.investors', 1);
    }
})->with(['browser', 'token']);

it('refuses impossible aggregate amounts or occupied capacity', function (string $field): void {
    $summary = app(CampaignReservationSummary::class)->read($this->campaign->id, now()->toDateTimeImmutable());
    $summary[$field] = '999999999';
    $port = $this->createMock(CampaignReservationSummary::class);
    $port->expects($this->once())->method('read')->willReturn($summary);
    app()->instance(CampaignReservationSummary::class, $port);
    expect($this->page)->toThrow(RuntimeException::class, 'RESERVATION_SUMMARY_INTEGRITY_FAILED');
})->with(['committed_principal', 'occupied_units']);
