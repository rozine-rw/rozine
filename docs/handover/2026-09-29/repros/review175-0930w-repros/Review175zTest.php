<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * Review repros for #175 range d701d879..33ba9040 (funding reader after the publication deadline).
 */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $fixture = AuditSealingFixture::ready(signatories: 2, requiredSignatories: 1);
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $campaigns = app(BusinessCampaignStore::class);
    $campaigns->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Verified release.', (string) Str::uuid());
    $campaigns->publish($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
        $fixture['application']->id, $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $this->fixture = $fixture;
    $this->campaign = BusinessCampaign::query()->sole();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = function (array $investor, string $units = '1080'): PrimaryReservationRecord {
        $result = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->confirm = function (array $investor, PrimaryReservationRecord $root): string {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();

        return $this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, $version->revision,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
    };
    $this->fund = fn () => DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id));
});

/** Rewrites a real confirmation's evidence to a new instant, recomputing the payload and digest exactly as the writer would. */
function review175zRestamp(PrimaryReservationRecord $root, DateTimeImmutable $at): void
{
    $service = app(PrimaryReservations::class);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::transaction(function () use ($service, $root, $at): void {
        $campaign = app(PrimaryCampaignSource::class)->lockRetained($root->business_campaign_id);
        $versions = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderBy('revision')->get();
        [$held, $confirmed] = [$versions->first(), $versions->last()];
        [$reservation] = (fn (): array => $this->retainedReservation($root, $campaign))->call($service);
        $payload = (fn (): array => $this->versionPayload($root, $reservation, $confirmed->operation_id, $held, $at))->call($service);
        DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER primary_reservation_versions_immutable');
        DB::statement('ALTER TABLE primary_commitments DISABLE TRIGGER primary_commitments_immutable');
        $confirmed->forceFill(['created_at' => $at, 'payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
        PrimaryCommitment::query()->where('primary_reservation_version_id', $confirmed->id)->sole()->forceFill(['confirmed_at' => $at, 'created_at' => $at])->save();
        // Triggers stay disabled only inside RefreshDatabase's rolled-back transaction.
    });
}

it('Z-R1: a raise fully committed before its deadline is fundable after it, and the expiry sweep keeps deferring it', function (string $when): void {
    foreach ([1, 2] as $index) {
        $investor = PrimaryReservationFixture::investor();
        expect(($this->confirm)($investor, ($this->reserve)($investor)))->toBe('RESERVATION_CONFIRMED');
    }
    $this->travelTo($when === 'deadline' ? $this->campaign->expires_at : $this->campaign->expires_at->addDays(60));
    $candidate = ($this->fund)();
    expect($candidate->purchases)->toHaveCount(2)
        ->and(app(BusinessCampaignStore::class)->expireDue(10))->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(($this->fund)())->toEqual($candidate);
})->with(['deadline', 'sixty days later']);

it('Z-R2: a hold created before the deadline can confirm only strictly before it', function (string $when): void {
    $this->travelTo($this->campaign->expires_at->subSeconds(100));
    $first = PrimaryReservationFixture::investor();
    expect(($this->confirm)($first, ($this->reserve)($first)))->toBe('RESERVATION_CONFIRMED');
    $second = PrimaryReservationFixture::investor();
    $root = ($this->reserve)($second);
    expect($root->expires_at->equalTo($this->campaign->expires_at))->toBeTrue(); // the 300s hold is clipped to the deadline
    $this->travelTo(match ($when) {
        'last microsecond' => $this->campaign->expires_at->subMicrosecond(),
        'deadline' => $this->campaign->expires_at,
        'a day late' => $this->campaign->expires_at->addDay(),
    });
    $code = ($this->confirm)($second, $root);
    $this->travelTo($this->campaign->expires_at->addDays(2));
    if ($when === 'last microsecond') {
        expect($code)->toBe('RESERVATION_CONFIRMED')
            ->and(PrimaryCommitment::query()->where('primary_reservation_id', $root->id)->sole()->confirmed_at->equalTo($this->campaign->expires_at->subMicrosecond()))->toBeTrue()
            ->and(($this->fund)()->purchases)->toHaveCount(2);
    } else {
        expect($code)->toBe('RESERVATION_EXPIRED')
            ->and(PrimaryCommitment::query()->count())->toBe(1)
            ->and(fn () => ($this->fund)())->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FULLY_COMMITTED');
    }
})->with(['last microsecond', 'deadline', 'a day late']);

it('Z-R3: a new reservation at or after the deadline is refused, so no late purchase can start', function (string $when): void {
    $first = PrimaryReservationFixture::investor();
    expect(($this->confirm)($first, ($this->reserve)($first)))->toBe('RESERVATION_CONFIRMED');
    $late = PrimaryReservationFixture::investor();
    $this->travelTo($when === 'deadline' ? $this->campaign->expires_at : $this->campaign->expires_at->addDay());
    $result = $this->checkout->reserve($late['user']->id, 1, $this->campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect($result['code'])->toBe('CAMPAIGN_CLOSED')
        ->and(PrimaryReservationRecord::query()->count())->toBe(1)
        ->and(fn () => ($this->fund)())->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FULLY_COMMITTED');
})->with(['deadline', 'a day late']);

it('Z-R4: the reader checks the retained confirmation instant against the half-open window, even for digest-consistent evidence', function (string $stamp): void {
    $this->travelTo($this->campaign->expires_at->subSeconds(100));
    $roots = [];
    foreach ([1, 2] as $index) {
        $investor = PrimaryReservationFixture::investor();
        $roots[] = $root = ($this->reserve)($investor);
        expect(($this->confirm)($investor, $root))->toBe('RESERVATION_CONFIRMED');
    }
    $deadline = $this->campaign->expires_at->toDateTimeImmutable();
    review175zRestamp($roots[1], $stamp === 'deadline' ? $deadline : $deadline->modify('-1 microsecond'));
    $this->travelTo($this->campaign->expires_at->addDay());
    if ($stamp === 'deadline') {
        expect(fn () => ($this->fund)())->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
    } else {
        expect(($this->fund)()->purchases)->toHaveCount(2);
    }
})->with(['deadline', 'last microsecond']);

it('Z-R5 (carried P3): a mandate that lapses after the deadline blocks funding, and the committed raise still cannot close', function (): void {
    foreach ([1, 2] as $index) {
        $investor = PrimaryReservationFixture::investor();
        ($this->confirm)($investor, ($this->reserve)($investor));
    }
    $authority = $this->fixture['audit']['authority'];
    $authority['terms']['expires_at'] = $this->campaign->expires_at->addDay()->utc()->format('Y-m-d\TH:i:s\Z');
    BusinessAuthorityFixture::configure($authority, 1);
    $this->travelTo($this->campaign->expires_at->addHour());
    expect(($this->fund)()->purchases)->toHaveCount(2);
    $this->travelTo($this->campaign->expires_at->addDays(2));
    expect(fn () => ($this->fund)())->toThrow(CommandRejection::class, 'MANDATE_REQUIRED')
        ->and(app(BusinessCampaignStore::class)->expireDue(10))->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0);
});

it('Z-R6 (pre-existing): one released hold keeps its units, so the raise can never be fully committed; after the deadline it can neither fund nor close', function (): void {
    $abandoned = PrimaryReservationFixture::investor();
    $root = ($this->reserve)($abandoned);
    expect($this->checkout->release($abandoned['user']->id, 1, $this->campaign->id, $root->id, 1, (string) Str::uuid())['code'])->toBe('RESERVATION_RELEASED');
    $buyer = PrimaryReservationFixture::investor();
    expect(($this->confirm)($buyer, ($this->reserve)($buyer)))->toBe('RESERVATION_CONFIRMED');
    $late = PrimaryReservationFixture::investor();
    expect($this->checkout->reserve($late['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])
        ->toBe('UNITS_UNAVAILABLE');
    $this->travelTo($this->campaign->expires_at->addDay());
    expect(fn () => ($this->fund)())->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FULLY_COMMITTED')
        ->and(app(BusinessCampaignStore::class)->expireDue(10))->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0);
});
