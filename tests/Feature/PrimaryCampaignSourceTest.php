<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\UnitOrdinals;
use App\Domain\Primary\UnitRights;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessCampaign;
use App\Models\BusinessExposureReservation;
use App\Models\CommandOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessCreditFactsFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($this->fixture);
    AuditSealingFixture::cosign($this->fixture);
    $this->campaigns = app(BusinessCampaignStore::class);
    $this->campaigns->release($this->fixture['audit']['staff']->id, $this->fixture['application']->id, 0, 'Verified release.', (string) Str::uuid());
    $this->campaigns->publish($this->fixture['audit']['authority']['users'][0]->id, 1, $this->fixture['audit']['business'],
        $this->fixture['application']->id, $this->fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $this->campaign = BusinessCampaign::query()->sole();
    $this->source = app(PrimaryCampaignSource::class);
});

/** @param array<string, mixed> $payload */
function corruptPrimarySource(BusinessCampaign|BusinessApplicationRelease|BusinessApplicationQuote|BusinessExposureReservation $record, array $payload, bool $rehash = true): void
{
    $table = $record->getTable();
    DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER USER');
    try {
        $record->forceFill(['payload' => $payload, 'sha256' => $rehash ? hash('sha256', app(CanonicalJson::class)->encode($payload)) : $record->sha256])->save();
    } finally {
        DB::statement('ALTER TABLE '.$table.' ENABLE TRIGGER USER');
    }
}

it('loads fixed published rights and the original exposure without writing or repricing', function (): void {
    $operations = CommandOperation::query()->count();
    $input = $this->source->lock($this->campaign->id);
    $published = $this->campaign->payload;
    expect($input)->toMatchArray(['id' => $this->campaign->id, 'business_id' => $this->campaign->business_id,
        'application_id' => $this->campaign->business_application_id, 'exposure_reservation_id' => $this->campaign->exposure_reservation_id,
        'publication_sha256' => $this->campaign->sha256, 'principal' => '10800000', 'units' => '2160',
        'term_months' => $published['quote']['term_months'], 'rate_pct' => $published['quote']['rate_pct'], 'policy_version' => $published['quote']['policy_version']])
        ->and($input['payments'])->toBe(array_column(array_column($published['quote']['schedule'], 'amount'), 'amount'))
        ->and($input['live_at']->format(DATE_ATOM))->toBe($published['recorded_at'])
        ->and($input['expires_at']->format(DATE_ATOM))->toBe($published['expires_at'])
        ->and(CommandOperation::query()->count())->toBe($operations);
    $rights = UnitRights::allocate($input['principal'], $input['payments'], UnitOrdinals::reserve($input['units'], [], '1'));
    expect((string) $rights->principal)->toBe('5000');
    $facts = BusinessCreditFactsFixture::facts();
    $facts['restriction_active'] = true;
    BusinessCreditFactsFixture::record($this->fixture['audit']['staff'], $this->fixture['audit']['business'], 1, facts: $facts);
    expect($this->source->lock($this->campaign->id))->toEqual($input);
});

it('normalizes campaign identifiers and refuses unknown publications', function (): void {
    expect($this->source->lock(strtoupper($this->campaign->id))['id'])->toBe($this->campaign->id);
    expect(fn () => $this->source->lock((string) Str::ulid()))->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FOUND');
});

it('uses the half-open published clock even before an expiry sweep', function (string $instant): void {
    $this->travelTo(match ($instant) {
        'early' => $this->campaign->live_at->subMicrosecond(),
        'deadline' => $this->campaign->expires_at,
        'late' => $this->campaign->expires_at->addSecond(),
        default => throw new InvalidArgumentException('Unknown clock boundary.'),
    });
    expect(fn () => $this->source->lock($this->campaign->id))->toThrow(CommandRejection::class, 'CAMPAIGN_CLOSED');
})->with(['early', 'deadline', 'late']);

it('accepts the instant immediately before the deadline', function (): void {
    $this->travelTo($this->campaign->expires_at->subMicrosecond());
    expect($this->source->lock($this->campaign->id)['id'])->toBe($this->campaign->id);
});

it('refuses a retained cancellation inside the original publication window', function (): void {
    $this->campaigns->cancel($this->fixture['audit']['authority']['users'][0]->id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, 'Cancelled.', (string) Str::uuid());
    expect(fn () => $this->source->lock($this->campaign->id))->toThrow(CommandRejection::class, 'CAMPAIGN_CLOSED');
});

it('rejects publication corruption through the same verifier as Business reads', function (bool $rehash): void {
    corruptPrimarySource($this->campaign, [...$this->campaign->payload, 'principal' => '3000000'], $rehash);
    expect(fn () => $this->source->lock($this->campaign->id))->toThrow(RuntimeException::class, 'CAMPAIGN_INTEGRITY_FAILED');
})->with([true, false]);

it('rejects damaged release evidence and parent bindings', function (string $field): void {
    $release = BusinessApplicationRelease::query()->sole();
    $payload = $release->payload;
    if ($field === 'binding') {
        $payload['binding']['principal'] = '3000000';
    } elseif ($field === 'digest') {
        $payload['reason'] = 'Modified without a new digest';
    } else {
        $payload[$field] = $field === 'actor_user_id' ? 0 : (string) Str::ulid();
    }
    corruptPrimarySource($release, $payload, $field !== 'digest');
    expect(fn () => $this->source->lock($this->campaign->id))->toThrow(RuntimeException::class, 'APPLICATION_RELEASE_INTEGRITY_FAILED');
})->with(['release_id', 'business_id', 'application_id', 'exposure_reservation_id', 'actor_user_id', 'binding', 'digest']);

it('rejects an exposure that is absent or disagrees with the published principal', function (bool $absent): void {
    $values = $absent ? [] : [['id' => $this->campaign->exposure_reservation_id, 'principal' => '3000000']];
    app()->instance(BusinessExposureStore::class, new class($values) implements BusinessExposureStore
    {
        /** @param list<array{id: string, principal: string}> $values */
        public function __construct(private array $values) {}

        /** @return list<array{id: string, principal: string}> */
        public function current(string $businessId): array
        {
            return $this->values;
        }

        public function reserve(string $businessId, string $submissionId): void
        {
            throw new LogicException('Unexpected exposure write.');
        }
    });
    expect(fn () => app(PrimaryCampaignSource::class)->lock($this->campaign->id))->toThrow(RuntimeException::class, 'BUSINESS_EXPOSURE_INTEGRITY_FAILED');
})->with([true, false]);

it('rejects rehashed publication pricing that disagrees with the retained quote', function (string $field): void {
    $payload = $this->campaign->payload;
    $payload['quote'][$field] = match ($field) {
        'quote_id' => (string) Str::ulid(), 'quote_revision', 'term_months' => 99,
        'principal', 'interest', 'total', 'unit_price' => ['currency' => 'RWF', 'amount' => '1'],
        'schedule' => [['instalment' => 1, 'amount' => ['currency' => 'RWF', 'amount' => '1']]],
        default => 'changed',
    };
    corruptPrimarySource($this->campaign, $payload);
    expect(fn () => $this->source->lock($this->campaign->id))->toThrow(RuntimeException::class, 'APPLICATION_QUOTE_INTEGRITY_FAILED');
})->with(['quote_id', 'quote_revision', 'status', 'policy_version', 'principal', 'term_months', 'rate_pct', 'interest', 'total', 'units', 'unit_price', 'schedule']);

it('rejects altered accepted quote data even when the campaign digest remains valid', function (bool $rehash): void {
    $quote = BusinessApplicationQuote::query()->whereKey($this->campaign->payload['quote']['quote_id'])->firstOrFail();
    $payload = $quote->payload;
    $payload['result']['pricing']['percent'] = '15.0';
    corruptPrimarySource($quote, $payload, $rehash);
    expect(fn () => $this->source->lock($this->campaign->id))->toThrow(RuntimeException::class, 'APPLICATION_QUOTE_INTEGRITY_FAILED');
})->with([true, false]);

it('does not return release reasons mandates evidence or operation receipts to Primary', function (): void {
    expect(array_keys($this->source->lock($this->campaign->id)))->toBe([
        'id', 'business_id', 'application_id', 'exposure_reservation_id', 'publication_sha256', 'principal', 'units',
        'rate_pct', 'term_months', 'policy_version', 'payments', 'live_at', 'expires_at',
    ]);
});

it('takes the Business lock before the campaign lock', function (): void {
    DB::enableQueryLog();
    try {
        $this->source->lock($this->campaign->id);
        $locks = array_values(array_filter(array_column(DB::getQueryLog(), 'query'), fn (string $query): bool => str_contains($query, 'for update')));
        expect($locks[0])->toContain('"business_profiles"')->and($locks[1])->toContain('"business_campaigns"');
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
});

it('rejects exposure evidence bound to a different quote even when its digest is valid', function (string $field): void {
    $reservation = BusinessExposureReservation::query()->whereKey($this->campaign->exposure_reservation_id)->firstOrFail();
    $payload = $reservation->payload;
    $payload[$field] = $field === 'quote_id' ? (string) Str::ulid() : str_repeat('0', 64);
    corruptPrimarySource($reservation, $payload);
    expect(fn () => $this->source->lock($this->campaign->id))->toThrow(RuntimeException::class, 'BUSINESS_EXPOSURE_INTEGRITY_FAILED');
})->with(['quote_id', 'quote_sha256']);
