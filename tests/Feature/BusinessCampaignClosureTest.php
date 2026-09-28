<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\BusinessExposureReservation;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\AuditSealingFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($this->fixture);
    AuditSealingFixture::cosign($this->fixture);
    $this->store = app(BusinessCampaignStore::class);
    $this->user = $this->fixture['audit']['authority']['users'][0];
    $this->business = $this->fixture['audit']['business'];
    $this->store->release($this->fixture['audit']['staff']->id, $this->fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    $this->store->publish($this->user->id, 1, $this->business, $this->fixture['application']->id,
        $this->fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $this->campaign = BusinessCampaign::query()->sole();
    $this->cancel = fn (int $revision = 1, ?string $reason = null, ?string $request = null): array => $this->store->cancel(
        $this->user->id, 1, $this->business, $this->campaign->id, $revision, $reason, $request ?? (string) Str::uuid());
});

it('cancels once retaining the original acceptance and a replayable zero-refund receipt', function (): void {
    $reservation = BusinessExposureReservation::query()->sole()->getAttributes();
    expect(app(BusinessExposureStore::class)->current($this->business))->toBe([['id' => $this->campaign->exposure_reservation_id, 'principal' => '10800000']]);
    $request = (string) Str::uuid();
    $result = ($this->cancel)(reason: 'Changed plans.', request: $request);
    expect($result['code'])->toBe('CAMPAIGN_CANCELLED')->and($result['revision'])->toBe(2)
        ->and($result['data']['receipt']['amount'])->toEqual(['currency' => 'RWF', 'amount' => '0'])
        ->and($result['data']['receipt']['exposure_released']['amount'])->toBe('10800000');
    $this->travel(1)->day();
    expect(($this->cancel)(reason: 'Changed plans.', request: $request))->toBe($result)
        ->and($this->store->findCancellation($this->user->id, 1, $request))->toBe($result)
        ->and(fn () => ($this->cancel)(reason: 'Other reason.', request: $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(($this->cancel)()['code'])->toBe('VERSION_CONFLICT')
        ->and(($this->cancel)(revision: 2)['code'])->toBe('CAMPAIGN_CLOSED')
        ->and(BusinessCampaignClosure::query()->count())->toBe(1)
        ->and(app(BusinessExposureStore::class)->current($this->business))->toBe([])
        ->and(BusinessExposureReservation::query()->sole()->getAttributes())->toBe($reservation);
    $page = $this->store->campaign($this->user->id, 1, $this->business, $this->campaign->id);
    expect($page['lifecycle'])->toBe('cancelled')->and($page['can_cancel'])->toBeFalse()
        ->and($page['progress'])->toEqual(['phase' => 'cancelled', 'committed_refunded' => ['currency' => 'RWF', 'amount' => '0'],
            'investors' => 0, 'closed_at' => $result['data']['receipt']['recorded_at']]);
});

it('retains refusal outcomes without releasing exposure', function (int $revision, ?string $reason, string $code): void {
    $request = (string) Str::uuid();
    $result = ($this->cancel)($revision, $reason, $request);
    expect($result['code'])->toBe($code)->and(($this->cancel)($revision, $reason, $request))->toBe($result)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($this->business))->toHaveCount(1);
})->with([[0, null, 'VERSION_CONFLICT'], [1, str_repeat('x', 1001), 'VALIDATION_FAILED'], [1, "Hidden\u{2028}reason", 'VALIDATION_FAILED']]);

it('expires exactly at the retained cutoff and never backdates processing time', function (): void {
    $deadline = $this->campaign->expires_at;
    $this->travelTo($deadline->subMicrosecond());
    expect($this->store->expireDue(100))->toBe(0)
        ->and($this->store->campaign($this->user->id, 1, $this->business, $this->campaign->id)['can_cancel'])->toBeTrue();
    $this->travelTo($deadline);
    $request = (string) Str::uuid();
    $refused = ($this->cancel)(request: $request);
    expect($refused['code'])->toBe('CAMPAIGN_CLOSED')
        ->and($this->store->campaign($this->user->id, 1, $this->business, $this->campaign->id)['can_cancel'])->toBeFalse()
        ->and(BusinessCampaignClosure::query()->count())->toBe(0);
    $this->travel(2)->minutes();
    expect(Artisan::call('campaigns:expire', ['--limit' => 1]))->toBe(0);
    expect(Artisan::output())->toContain('Expired 1 campaigns.');
    $closed = BusinessCampaignClosure::query()->sole();
    expect($closed->phase)->toBe('expired')->and($closed->actor_user_id)->toBeNull()
        ->and($closed->closed_at->equalTo($deadline))->toBeTrue()
        ->and($closed->payload['recorded_at'])->toBe(now()->toIso8601String())
        ->and($this->store->expireDue(100))->toBe(0)
        ->and(($this->cancel)(request: $request))->toBe($refused)
        ->and(app(BusinessExposureStore::class)->current($this->business))->toBe([])
        ->and($this->store->campaign($this->user->id, 1, $this->business, $this->campaign->id)['progress']['phase'])->toBe('expired');
});

it('rolls back closure and exposure release together if persistence fails', function (bool $expiry): void {
    $request = (string) Str::uuid();
    if ($expiry) {
        $this->travelTo($this->campaign->expires_at);
    }
    $event = 'eloquent.created: '.BusinessCampaignClosure::class;
    Event::listen($event, fn () => throw new RuntimeException('synthetic closure failure'));
    try {
        expect(fn () => $expiry ? $this->store->expireDue(100) : ($this->cancel)(request: $request))->toThrow(RuntimeException::class, 'synthetic closure failure');
    } finally {
        Event::forget($event);
    }
    expect(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'campaign.cancel')->count())->toBe(0)
        ->and(app(BusinessExposureStore::class)->current($this->business))->toHaveCount(1);
    expect($expiry ? $this->store->expireDue(100) : ($this->cancel)(request: $request)['code'])->toBe($expiry ? 1 : 'CAMPAIGN_CANCELLED');
})->with([false, true]);

it('rechecks closure after the expiry candidate is selected', function (): void {
    $this->travelTo($this->campaign->expires_at);
    $event = 'eloquent.retrieved: '.BusinessCampaign::class;
    $advanced = false;
    Event::listen($event, function (BusinessCampaign $campaign) use (&$advanced): void {
        if (! $advanced && ! array_key_exists('expires_at', $campaign->getAttributes())) {
            $advanced = true;
            expect($this->store->expireDue(100))->toBe(1);
        }
    });
    try {
        expect($this->store->expireDue(100))->toBe(0)->and(BusinessCampaignClosure::query()->count())->toBe(1);
    } finally {
        Event::forget($event);
    }
});

it('fails closed on damaged closure evidence even if its payload is rehashed', function (bool $rehash): void {
    ($this->cancel)();
    $closure = BusinessCampaignClosure::query()->sole();
    $payload = $closure->payload;
    $payload['principal_released'] = '3000000';
    DB::statement('ALTER TABLE business_campaign_closures DISABLE TRIGGER business_campaign_closures_protected');
    try {
        $closure->forceFill(['payload' => $payload, 'sha256' => $rehash ? hash('sha256', app(CanonicalJson::class)->encode($payload)) : $closure->sha256])->save();
    } finally {
        DB::statement('ALTER TABLE business_campaign_closures ENABLE TRIGGER business_campaign_closures_protected');
    }
    expect(fn () => app(BusinessExposureStore::class)->current($this->business))->toThrow(RuntimeException::class, 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED')
        ->and(fn () => $this->store->campaign($this->user->id, 1, $this->business, $this->campaign->id))->toThrow(RuntimeException::class, 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
})->with([false, true]);

it('rejects closure mutations deletion duplicate release and occupied rollback', function (string $operation): void {
    ($this->cancel)();
    expect(fn () => DB::transaction(function () use ($operation): void {
        $closure = BusinessCampaignClosure::query()->sole();
        match ($operation) {
            'update' => $closure->forceFill(['phase' => 'expired'])->save(),
            'delete' => $closure->delete(),
            'duplicate' => $closure->replicate()->save(),
            'rollback' => (require database_path('migrations/2026_09_27_230946_create_business_campaign_closures_table.php'))->down(),
            default => throw new InvalidArgumentException('Unknown mutation.'),
        };
    }))->toThrow(QueryException::class);
})->with(['update', 'delete', 'duplicate', 'rollback']);

it('rejects mismatched closure parents and invalid cutoff dates at the database boundary', function (string $case): void {
    expect(fn () => DB::transaction(function () use ($case): void {
        BusinessCampaignClosure::factory()->create(['business_campaign_id' => $this->campaign->id,
            ...match ($case) {
                'reservation' => ['exposure_reservation_id' => (string) Str::ulid()],
                'principal' => ['principal' => '3000000'],
                'phase' => ['phase' => 'failed_closing'],
                'expired_early' => ['phase' => 'expired', 'actor_user_id' => null, 'closed_at' => now()],
                'cancelled_late' => ['closed_at' => $this->campaign->expires_at, 'created_at' => $this->campaign->expires_at],
                default => throw new InvalidArgumentException('Unknown parent violation.'),
            }]);
    }))->toThrow(QueryException::class);
})->with(['reservation', 'principal', 'phase', 'expired_early', 'cancelled_late']);

it('rejects invalid sweep limits', function (): void {
    expect(fn () => $this->store->expireDue(0))->toThrow(CommandRejection::class, 'INVALID_SWEEP_LIMIT');
    expect(Artisan::call('campaigns:expire', ['--limit' => '1001']))->toBe(2);
    expect(Artisan::output())->toContain('The limit must be an integer from 1 to 1000.');
});

it('does not release exposure when the retained principal no longer matches its original reservation', function (): void {
    ($this->cancel)();
    $reservation = BusinessExposureReservation::query()->sole();
    $payload = [...$reservation->payload, 'principal' => '3000000'];
    DB::statement('ALTER TABLE business_exposure_reservations DISABLE TRIGGER business_exposure_reservations_protected');
    try {
        $reservation->forceFill(['principal' => '3000000', 'payload' => $payload,
            'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE business_exposure_reservations ENABLE TRIGGER business_exposure_reservations_protected');
    }
    expect(fn () => app(BusinessExposureStore::class)->current($this->business))->toThrow(RuntimeException::class, 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
});

it('stops at the requested expiry batch size', function (): void {
    $other = AuditSealingFixture::ready();
    $totp = new Google2FA;
    $secret = $totp->generateSecretKey();
    $other['user']->forceFill(['two_factor_secret' => encrypt($secret)])->save();
    $other['code'] = $totp->getCurrentOtp($secret);
    AuditSealingFixture::seal($other);
    AuditSealingFixture::cosign($other);
    $this->store->release($other['audit']['staff']->id, $other['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    $this->store->publish($other['audit']['authority']['users'][0]->id, 1, $other['audit']['business'], $other['application']->id,
        $other['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $this->travelTo($this->campaign->expires_at);
    expect($this->store->expireDue(1))->toBe(1)->and(BusinessCampaignClosure::query()->count())->toBe(1)
        ->and($this->store->expireDue(1))->toBe(1)->and(BusinessCampaignClosure::query()->count())->toBe(2)
        ->and($this->store->expireDue(1))->toBe(0);
});

it('refuses to record a closure receipt if its original exposure no longer matches', function (): void {
    $reservation = BusinessExposureReservation::query()->sole();
    $payload = [...$reservation->payload, 'principal' => '3000000'];
    DB::statement('ALTER TABLE business_exposure_reservations DISABLE TRIGGER business_exposure_reservations_protected');
    try {
        $reservation->forceFill(['principal' => '3000000', 'payload' => $payload,
            'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE business_exposure_reservations ENABLE TRIGGER business_exposure_reservations_protected');
    }
    expect(fn () => ($this->cancel)())->toThrow(RuntimeException::class, 'CAMPAIGN_EXPOSURE_INTEGRITY_FAILED')
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'campaign.cancel')->count())->toBe(0);
});
