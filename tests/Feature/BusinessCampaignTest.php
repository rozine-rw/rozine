<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessCampaign;
use App\Models\BusinessExposureReservation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessCreditFactsFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($this->fixture);
    AuditSealingFixture::cosign($this->fixture);
    $this->store = app(BusinessCampaignStore::class);
    $this->release = fn (?string $request = null): array => $this->store->release($this->fixture['audit']['staff']->id,
        $this->fixture['application']->id, 0, 'Verified current release gates.', $request ?? (string) Str::uuid());
    $this->publish = fn (?string $request = null, string $fee = 'listing-fee-waiver-1'): array => $this->store->publish($this->fixture['audit']['authority']['users'][0]->id,
        1, $this->fixture['audit']['business'], $this->fixture['application']->id, $this->fixture['application']->refresh()->revision, $fee, $request ?? (string) Str::uuid());
});

it('retains one release and publishes one zero-fee campaign against the same exposure', function (): void {
    $releaseRequest = (string) Str::uuid();
    $publishRequest = (string) Str::uuid();
    $released = ($this->release)($releaseRequest);
    expect($released['code'])->toBe('APPLICATION_RELEASED')->and(($this->release)($releaseRequest))->toBe($released);
    $published = ($this->publish)($publishRequest);
    expect($published['code'])->toBe('LISTING_PUBLISHED')->and(($this->publish)($publishRequest))->toBe($published)
        ->and($published['data']['receipt']['amount'])->toEqual(['currency' => 'RWF', 'amount' => '0']);
    $campaign = BusinessCampaign::query()->sole();
    expect(BusinessExposureReservation::query()->count())->toBe(1)->and(BusinessApplicationRelease::query()->count())->toBe(1)
        ->and($campaign->exposure_reservation_id)->toBe(BusinessExposureReservation::query()->sole()->id)
        ->and($campaign->expires_at->diffInSeconds($campaign->live_at, true))->toBe(30 * 86400.0)
        ->and(($this->publish)()['code'])->toBe('LISTING_ALREADY_PUBLISHED')
        ->and($this->store->findRelease($this->fixture['audit']['staff']->id, $releaseRequest))->toBe($released)
        ->and($this->store->findPublication($this->fixture['audit']['authority']['users'][0]->id, 1, $publishRequest))->toBe($published)
        ->and($this->store->campaign($this->fixture['audit']['authority']['users'][0]->id, 1, $this->fixture['audit']['business'], $campaign->id)['principal'])->toBe('10800000');
});

it('records missing release and changed disclosure refusals without publishing', function (): void {
    $request = (string) Str::uuid();
    $refused = ($this->publish)($request);
    expect($refused['code'])->toBe('STAFF_RELEASE_REQUIRED');
    ($this->release)();
    expect(($this->publish)($request))->toBe($refused)
        ->and(($this->publish)(fee: 'stale')['code'])->toBe('FEE_DISCLOSURE_CHANGED')
        ->and(BusinessCampaign::query()->count())->toBe(0);
});

it('rechecks current credit after staff release and retains the release receipt', function (): void {
    $request = (string) Str::uuid();
    $released = ($this->release)($request);
    $facts = BusinessCreditFactsFixture::facts();
    $facts['restriction_active'] = true;
    BusinessCreditFactsFixture::record($this->fixture['audit']['staff'], $this->fixture['audit']['business'], 1, facts: $facts);
    expect(($this->publish)()['code'])->toBe('QUOTE_STALE')->and(($this->release)($request))->toBe($released)
        ->and($this->store->staffPage($this->fixture['audit']['staff']->id, $this->fixture['application']->id)['cause'])->toBe('QUOTE_STALE')
        ->and(BusinessCampaign::query()->count())->toBe(0);
});

it('rolls back publication on an insertion failure and permits retrying the same request', function (): void {
    ($this->release)();
    $request = (string) Str::uuid();
    $event = 'eloquent.created: '.BusinessCampaign::class;
    Event::listen($event, fn () => throw new RuntimeException('synthetic publication failure'));
    try {
        expect(fn () => ($this->publish)($request))->toThrow(RuntimeException::class, 'synthetic publication failure');
    } finally {
        Event::forget($event);
    }
    expect(BusinessCampaign::query()->count())->toBe(0)->and(($this->publish)($request)['code'])->toBe('LISTING_PUBLISHED');
});
