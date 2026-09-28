<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\GetInvestorWallet;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->reservations = app(PrimaryReservations::class);
});

it('atomically retains exact rights and disclosed terms with a matching source-bound cash hold', function (): void {
    $result = PrimaryReservationFixture::reserve($this->campaign, $this->investor, '3');
    $root = PrimaryReservationRecord::query()->sole();
    $version = PrimaryReservationVersion::query()->sole();
    $entry = LedgerEntry::query()->where('kind', 'primary_hold')->sole();
    expect($result['code'])->toBe('RESERVATION_HELD')
        ->and($root->payload)->toMatchArray(['reservation_id' => $root->id, 'campaign_id' => $this->campaign->id,
            'publication_sha256' => $this->campaign->sha256, 'units' => '3', 'principal' => '15000', 'ordinals' => [['first' => '1', 'last' => '3']]])
        ->and($root->expires_at->diffInSeconds($root->created_at, true))->toEqual(300)
        ->and($version->payload)->toMatchArray(['reservation_sha256' => $root->sha256, 'revision' => 1, 'state' => 'held',
            'disclosure_sha256' => $version->payload['disclosure_sha256']])
        ->and($version->payload['terms']['earnings_fee']['policy_version'])->toBe('synthetic-primary-fees-1')
        ->and($entry->source_id)->toBe($root->id)->and($entry->origin_operation_id)->toBe($result['operation_id'])
        ->and(app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown'])->toMatchArray([
            'held' => ['currency' => 'RWF', 'amount' => '15000'], 'available' => ['currency' => 'RWF', 'amount' => '9985000']]);
    expect(DB::table('primary_reservations')->value('payload'))->not->toContain('primary-reservation-1')
        ->and(DB::table('primary_reservation_versions')->value('payload'))->not->toContain('synthetic-primary-fees-1');
});

it('serially allocates lowest free ordinals across Investors without changing retained rights', function (): void {
    PrimaryReservationFixture::reserve($this->campaign, $this->investor, '3');
    $other = PrimaryReservationFixture::investor();
    PrimaryReservationFixture::reserve($this->campaign, $other, '2');
    expect(PrimaryReservationRecord::query()->orderBy('id')->get()->pluck('payload.ordinals')->all())
        ->toBe([[['first' => '1', 'last' => '3']], [['first' => '4', 'last' => '5']]]);
});

it('enforces the cumulative 50 percent Investor cap across separate operation keys', function (): void {
    expect(PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1000')['code'])->toBe('RESERVATION_HELD')
        ->and(PrimaryReservationFixture::reserve($this->campaign, $this->investor, '80')['code'])->toBe('RESERVATION_HELD')
        ->and(PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1')['code'])->toBe('INVESTOR_CAMPAIGN_CAP_EXCEEDED')
        ->and(PrimaryReservationRecord::query()->sum('units'))->toEqual(1080);
});

it('never recycles a timed-out hold merely because the clock advanced', function (): void {
    PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1080');
    $this->travel(5)->minutes();
    expect(PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1')['code'])->toBe('INVESTOR_CAMPAIGN_CAP_EXCEEDED');
    PrimaryReservationFixture::reserve($this->campaign, PrimaryReservationFixture::investor(), '1080');
    expect(PrimaryReservationFixture::reserve($this->campaign, PrimaryReservationFixture::investor(), '1')['code'])->toBe('UNITS_UNAVAILABLE')
        ->and(PrimaryReservationRecord::query()->sum('units'))->toEqual(2160);
});

it('rolls back reservation evidence when cash is insufficient even when its caller catches the refusal', function (): void {
    $operation = CommandOperation::factory()->create();
    $poor = PrimaryReservationFixture::investor('5000');
    expect(fn () => $this->reservations->reserve($this->campaign->id, $poor['party']->id, $operation->id, '2', PrimaryReservationFixture::terms(...)))
        ->toThrow(CommandRejection::class, 'INSUFFICIENT_AVAILABLE_FUNDS')
        ->and(PrimaryReservationRecord::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0)
        ->and(DB::transactionLevel())->toBe(1);
    expect(PrimaryReservationFixture::reserve($this->campaign, $poor, '1')['code'])->toBe('RESERVATION_HELD')
        ->and(PrimaryReservationRecord::query()->sole()->payload['ordinals'])->toBe([['first' => '1', 'last' => '1']]);
});

it('requires an explicit policy decision and writes nothing when that decision refuses', function (): void {
    expect(fn () => $this->reservations->reserve($this->campaign->id, $this->investor['party']->id, strtolower((string) Str::ulid()), '1',
        function (): never {
            throw new CommandRejection('POLICY_INPUT_REQUIRED');
        }))->toThrow(CommandRejection::class, 'POLICY_INPUT_REQUIRED')
        ->and(PrimaryReservationRecord::query()->count())->toBe(0)->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0);
});

it('checks the publication policy binding and domain terms before moving funds', function (string $field): void {
    expect(fn () => $this->reservations->reserve($this->campaign->id, $this->investor['party']->id, strtolower((string) Str::ulid()), '1',
        function (UnitRights $rights, array $campaign) use ($field): PrimaryTerms {
            if ($field === 'policy_version') {
                $campaign['policy_version'] = 'unrelated-policy';
            }
            if ($field === 'rate_pct') {
                $campaign['rate_pct'] = $campaign['rate_pct'] === '10.0' ? '11.0' : '10.0';
            }
            if ($field === 'term_months') {
                $campaign['term_months'] = $campaign['term_months'] === 3 ? 4 : 3;
            }
            $terms = PrimaryReservationFixture::terms($rights, $campaign);
            if ($field === 'fee') {
                return PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion, $terms->disclosureVersion, $terms->earningsFee, '999999', $rights);
            }

            return $terms;
        }))->toThrow(CommandRejection::class, 'INVALID_PRIMARY_TERMS')
        ->and(PrimaryReservationRecord::query()->count())->toBe(0);
})->with(['policy_version', 'rate_pct', 'term_months', 'fee']);

it('rechecks the half-open campaign clock after admission completes', function (): void {
    expect(fn () => $this->reservations->reserve($this->campaign->id, $this->investor['party']->id, strtolower((string) Str::ulid()), '1',
        function (UnitRights $rights, array $campaign): PrimaryTerms {
            $terms = PrimaryReservationFixture::terms($rights, $campaign);
            $this->travelTo($campaign['expires_at']);

            return $terms;
        }))->toThrow(CommandRejection::class, 'CAMPAIGN_CLOSED')->and(PrimaryReservationRecord::query()->count())->toBe(0);
});

it('clips the reservation clock to the final microsecond of the publication window', function (): void {
    $this->travelTo($this->campaign->expires_at->subMicrosecond());
    expect(PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1')['code'])->toBe('RESERVATION_HELD');
    $root = PrimaryReservationRecord::query()->sole();
    expect($root->created_at->format('u'))->toBe('999999')->and($root->expires_at->equalTo($this->campaign->expires_at))->toBeTrue();
});

it('uses the journal to return the original reservation and posting without a second hold', function (): void {
    $request = (string) Str::uuid();
    $first = PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1', $request);
    $this->travel(6)->minutes();
    expect(PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1', $request))->toBe($first)
        ->and(PrimaryReservationRecord::query()->count())->toBe(1)->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(1)
        ->and(fn () => PrimaryReservationFixture::reserve($this->campaign, $this->investor, '2', $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('rejects noncanonical quantities before creating a reservation', function (string $units): void {
    expect(PrimaryReservationFixture::reserve($this->campaign, $this->investor, $units)['code'])->toBe('INVALID_UNITS')
        ->and(PrimaryReservationRecord::query()->count())->toBe(0);
})->with(['0', '01', '-1', '1.0', '1e2']);

it('refuses corrupt retained allocations even if the damaged payload has a fresh digest', function (string $damage): void {
    PrimaryReservationFixture::reserve($this->campaign, $this->investor, '2');
    $root = PrimaryReservationRecord::query()->sole();
    $payload = $root->payload;
    match ($damage) {
        'missing' => $payload['ordinals'] = null,
        'shape' => $payload['ordinals'] = [['first' => 1, 'last' => '2']],
        'invalid' => $payload['ordinals'] = [['first' => '2', 'last' => '1']],
        'count' => $payload['ordinals'] = [['first' => '1', 'last' => '3']],
        'rights' => $payload['rights']['total_return']['amount'] = '1',
        'digest' => $payload['party_id'] = 'damaged',
        'projection' => $root->forceFill(['ordinal_ranges' => '{[3,5)}']),
        default => throw new InvalidArgumentException('Unknown damage fixture.'),
    };
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER USER');
    try {
        $root->forceFill(['payload' => $payload, 'sha256' => $damage === 'digest' ? $root->sha256 : hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        DB::statement('ALTER TABLE primary_reservations ENABLE TRIGGER USER');
    }
    expect(fn () => PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1'))->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED')
        ->and(PrimaryReservationRecord::query()->count())->toBe(1);
})->with(['missing', 'shape', 'invalid', 'count', 'rights', 'digest', 'projection']);
