<?php

declare(strict_types=1);

use App\Application\Business\Contracts\NoteServicing;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\IssuedHoldingEvidence;
use App\Application\Primary\Contracts\PrimaryAdmission;
use App\Application\Primary\GetRetainedNoteSchedule;
use App\Domain\Disbursement\DisbursementViolation;
use App\Infrastructure\Business\UnavailableNoteServicing;
use App\Infrastructure\Disbursement\UnavailableFundedCampaigns;
use App\Infrastructure\Primary\CurrentPrimaryAdmission;
use App\Models\BusinessCampaign;
use App\Models\DepositPolicy;
use App\Models\PrimaryHolding;
use Brick\Math\BigInteger;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;

beforeEach(fn () => $this->freezeSecond());

/** A deliberately broken adapter response, never a substitute for authenticated fixture evidence.
 * @param  array<string, mixed>  $bundle
 */
function noteScheduleBrokenSource(array $bundle): GetRetainedNoteSchedule
{
    return new GetRetainedNoteSchedule(new class($bundle) implements IssuedHoldingEvidence
    {
        /** @param array<string, mixed> $bundle */
        public function __construct(private array $bundle) {}

        public function find(string $campaignId): array
        {
            return $this->bundle;
        }
    });
}

it('projects all original bindings and conserves gross components including fixed ordinal residuals', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(units: ['1', '1079', '1080']);
    $bundle = DB::transaction(fn () => app(IssuedHoldingEvidence::class)->find($campaign->campaignId));
    $snapshot = DB::transaction(fn () => app(GetRetainedNoteSchedule::class)->find($campaign->campaignId));
    $quote = BusinessCampaign::query()->findOrFail($campaign->campaignId)->payload['quote'];
    expect($snapshot->campaignId)->toBe($campaign->campaignId)->and($snapshot->businessId)->toBe($campaign->businessId)
        ->and($snapshot->fundingId)->toBe($bundle['funding_id'])->and(get_object_vars($snapshot->closing))->toBe($bundle['closing'])
        ->and($snapshot->issuedAt)->toBe($instruction->issuedAt)->and($snapshot->principal)->toBe($campaign->principal)
        ->and($snapshot->contractualReturn)->toBe($quote['interest']['amount'])->and($snapshot->gross)->toBe($quote['total']['amount'])
        ->and($snapshot->holdings)->toHaveCount(3)->and(array_column($snapshot->instalments, 'dueDate'))->toBe($instruction->dueDates)
        ->and(array_column($snapshot->instalments, 'gross'))->toBe(array_column(array_column($quote['schedule'], 'amount'), 'amount'));
    foreach ($snapshot->holdings as $offset => $holding) {
        $original = $bundle['issued_holdings'][$offset];
        $certificate = array_column($bundle['holdings'], null, 'id')[$holding->holdingId];
        expect([$holding->holdingId, $holding->holdingSha256, $holding->commitmentId, $holding->reservationId, $holding->partyId,
            $holding->units, $holding->ordinals, $holding->reservationSha256, $holding->confirmationVersionId,
            $holding->confirmationRevision, $holding->confirmationSha256, $holding->rights, $holding->terms])
            ->toBe([$original['holding_id'], $certificate['sha256'], $original['commitment_id'], $original['primary_reservation_id'],
                $original['party_id'], $original['units'], $original['ordinals'], $original['reservation_sha256'], $original['confirmation_version_id'],
                $original['confirmation_revision'], $original['confirmation_sha256'], $original['rights'], $original['terms']])
            ->and($holding->principal)->toBe($original['principal'])->and($holding->contractualReturn)->toBe($original['rights']['total_return']['amount'])
            ->and((string) BigInteger::sum(...array_column($holding->instalments, 'principal')))->toBe($holding->principal)
            ->and((string) BigInteger::sum(...array_column($holding->instalments, 'contractualReturn')))->toBe($holding->contractualReturn)
            ->and((string) BigInteger::sum(...array_column($holding->instalments, 'gross')))->toBe($holding->gross);
        foreach ($holding->instalments as $index => $row) {
            expect([$row->index, $row->dueDate, $row->principal, $row->contractualReturn])->toBe([$index + 1, $instruction->dueDates[$index],
                $original['rights']['instalments'][$index]['principal']['amount'], $original['rights']['instalments'][$index]['return']['amount']]);
        }
        if ($holding->units === 1) {
            expect(array_column($holding->instalments, 'principal'))->toBe(['834', '833', '833', '834', '833', '833'])
                ->and(array_column($holding->instalments, 'contractualReturn'))->toBe(['93', '92', '93', '92', '93', '92']);
        }
    }
    foreach ($snapshot->instalments as $offset => $row) {
        expect((string) BigInteger::sum(...array_map(fn ($holding) => $holding->instalments[$offset]->principal, $snapshot->holdings)))->toBe($row->principal)
            ->and((string) BigInteger::sum(...array_map(fn ($holding) => $holding->instalments[$offset]->contractualReturn, $snapshot->holdings)))->toBe($row->contractualReturn)
            ->and((string) BigInteger::sum($row->principal, $row->contractualReturn))->toBe($row->gross);
    }
    foreach ([[$snapshot, 'principal', '1'], [$snapshot->holdings[0], 'terms', []], [$snapshot->instalments[0], 'principal', '1']] as [$object, $property, $value]) {
        $reflection = new ReflectionProperty($object, $property);
        expect(fn () => $reflection->setValue($object, $value))->toThrow(Error::class);
    }
});

it('replays historical terms after policy withdrawal and time changes with only unlocked SELECTs', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close(units: ['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    $reader = app(GetRetainedNoteSchedule::class);
    $original = DB::transaction(fn () => $reader->find($campaign->campaignId));
    $this->travel(3)->years();
    DepositPolicy::factory()->withdrawn()->create(['effective_at' => now()->subMinute()]);
    app()->bind(PrimaryAdmission::class, fn () => throw new LogicException('Current admission must not be read.'));
    app()->bind(NoteServicing::class, fn () => throw new LogicException('Servicing must not be read.'));
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });
    $level = DB::transactionLevel();
    $replayed = DB::transaction(fn () => $reader->find($campaign->campaignId));
    expect($replayed)->toEqual($original)->and(DB::transactionLevel())->toBe($level)->and($queries)->not->toBeEmpty();
    foreach ($queries as $query) {
        expect($query)->toStartWith('select ')->not->toMatch('/for\s+(update|share|no\s+key\s+update|key\s+share)|advisory|deposit_policies/');
    }
});

it('requires the caller transaction and distinguishes no issuance from an uncertified schema bundle', function (): void {
    $reader = app(GetRetainedNoteSchedule::class);
    expect(fn () => $reader->find(PrimaryHoldingFixture::id()))->toThrow(DisbursementViolation::class, 'PRIMARY_ISSUANCE_TRANSACTION_REQUIRED')
        ->and(DB::transaction(fn () => $reader->find(PrimaryHoldingFixture::id())))->toBeNull();
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing);
        PrimaryHoldingFixture::issue($commitment->id, $closing);
    }
    PrimaryHoldingFixture::flushDeferredChecks();
    expect(fn () => DB::transaction(fn () => $reader->find($campaign->id)))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_SOURCE_UNAVAILABLE');
});

it('returns no issuance before the actual successful Holding conversion', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close(settle: false);
    expect(DB::transaction(fn () => app(GetRetainedNoteSchedule::class)->find($campaign->campaignId)))->toBeNull();
});

it('leaves the admission, funded settlement and servicing bindings as they are', function (): void {
    app()->instance('env', 'production');
    expect(get_class(app(PrimaryAdmission::class)))->toBe(CurrentPrimaryAdmission::class)
        ->and(get_class(app(FundedCampaigns::class)))->toBe(UnavailableFundedCampaigns::class)
        ->and(get_class(app(NoteServicing::class)))->toBe(UnavailableNoteServicing::class);
    app(GetRetainedNoteSchedule::class);
    expect(get_class(app(PrimaryAdmission::class)))->toBe(CurrentPrimaryAdmission::class)
        ->and(get_class(app(NoteServicing::class)))->toBe(UnavailableNoteServicing::class);
});

it('refuses changed authenticated Holding evidence on every read without caching a previous snapshot', function (string $field): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close();
    $reader = app(GetRetainedNoteSchedule::class);
    expect(DB::transaction(fn () => $reader->find($campaign->campaignId)))->not->toBeNull();
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::statement('ALTER TABLE primary_holdings DISABLE TRIGGER primary_holdings_protected');
    try {
        $holding = PrimaryHolding::query()->orderBy('commitment_id')->firstOrFail();
        $payload = $holding->payload;
        match ($field) {
            'closing' => $payload['disbursement_closing_id'] = PrimaryHoldingFixture::id(),
            'funding' => $payload['funding_id'] = PrimaryHoldingFixture::id(),
            'commitment' => $payload['commitment_id'] = PrimaryHoldingFixture::id(),
            'date' => $payload['schedule'][0]['due_date'] = '2026-03-01',
            'principal' => $payload['schedule'][0]['principal']['amount'] = '1',
            'return' => $payload['rights']['total_return']['amount'] = '1',
            'terms' => $payload['terms']['policy_version'] = 'forged-policy',
            default => throw new LogicException('Unknown forgery.'),
        };
        $holding->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE primary_holdings ENABLE TRIGGER primary_holdings_protected');
    }
    expect(fn () => DB::transaction(fn () => $reader->find($campaign->campaignId)))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
})->with(['closing', 'funding', 'commitment', 'date', 'principal', 'return', 'terms']);

it('refuses a partial authentic issued membership instead of returning a partial snapshot', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::statement('ALTER TABLE primary_holdings DISABLE TRIGGER primary_holdings_protected');
    try {
        DB::table('primary_holdings')->where('id', PrimaryHolding::query()->firstOrFail()->id)->delete();
    } finally {
        DB::statement('ALTER TABLE primary_holdings ENABLE TRIGGER primary_holdings_protected');
    }
    expect(fn () => DB::transaction(fn () => app(GetRetainedNoteSchedule::class)->find($campaign->campaignId)))
        ->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
});

it('defensively rejects inconsistent adapter bindings and nonconserved components', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close();
    $original = DB::transaction(fn () => app(IssuedHoldingEvidence::class)->find($campaign->campaignId));
    foreach (['campaign', 'business', 'closing_campaign', 'unissued', 'currency', 'missing_dates', 'date_count', 'empty', 'partial',
        'duplicate_certificate', 'unknown_holding', 'wrong_commitment', 'duplicate_commitment', 'holding_campaign', 'holding_funding', 'cross_closing',
        'closing_hash', 'issued_at', 'effective_at', 'effective_date', 'term', 'row_count', 'index', 'date', 'rights', 'money_currency',
        'money_decimal', 'principal_sum', 'return_sum', 'note_sum'] as $change) {
        $bundle = $original;
        match ($change) {
            'campaign' => $bundle['campaign_id'] = PrimaryHoldingFixture::id(),
            'business' => $bundle['business_id'] = PrimaryHoldingFixture::id(),
            'closing_campaign' => $bundle['closing']['campaignId'] = PrimaryHoldingFixture::id(),
            'unissued' => $bundle['closing']['kind'] = 'failed_closing',
            'currency' => $bundle['closing']['currency'] = 'USD',
            'missing_dates' => $bundle['closing']['dueDates'] = null,
            'date_count' => $bundle['closing']['dueDates'] = [],
            'empty' => $bundle['closing']['commitmentCount'] = 0,
            'partial' => array_pop($bundle['issued_holdings']),
            'duplicate_certificate' => $bundle['holdings'][1]['id'] = $bundle['holdings'][0]['id'],
            'unknown_holding' => $bundle['issued_holdings'][0]['holding_id'] = PrimaryHoldingFixture::id(),
            'wrong_commitment' => $bundle['issued_holdings'][0]['commitment_id'] = PrimaryHoldingFixture::id(),
            'duplicate_commitment' => $bundle['issued_holdings'][1] = $bundle['issued_holdings'][0],
            'holding_campaign' => $bundle['issued_holdings'][0]['business_campaign_id'] = PrimaryHoldingFixture::id(),
            'holding_funding' => $bundle['issued_holdings'][0]['funding_id'] = PrimaryHoldingFixture::id(),
            'cross_closing' => $bundle['issued_holdings'][0]['disbursement_closing_id'] = PrimaryHoldingFixture::id(),
            'closing_hash' => $bundle['issued_holdings'][0]['closing_sha256'] = str_repeat('0', 64),
            'issued_at' => $bundle['issued_holdings'][0]['issued_at'] = now()->addSecond()->toIso8601String(),
            'effective_at' => $bundle['issued_holdings'][0]['disbursement_effective_at'] = now()->toIso8601String(),
            'effective_date' => $bundle['issued_holdings'][0]['effective_date'] = '2026-02-01',
            'term' => $bundle['issued_holdings'][0]['terms']['term_months'] = 5,
            'row_count' => array_pop($bundle['issued_holdings'][0]['schedule']),
            'index' => $bundle['issued_holdings'][0]['schedule'][0]['index'] = 2,
            'date' => $bundle['issued_holdings'][0]['schedule'][0]['due_date'] = '2026-03-01',
            'rights' => $bundle['issued_holdings'][0]['rights']['instalments'][0]['principal']['amount'] = '1',
            'money_currency' => $bundle['issued_holdings'][0]['schedule'][0]['principal']['currency'] = $bundle['issued_holdings'][0]['rights']['instalments'][0]['principal']['currency'] = 'USD',
            'money_decimal' => $bundle['issued_holdings'][0]['schedule'][0]['principal']['amount'] = $bundle['issued_holdings'][0]['rights']['instalments'][0]['principal']['amount'] = '1.5',
            'principal_sum' => $bundle['issued_holdings'][0]['principal'] = '1',
            'return_sum' => $bundle['issued_holdings'][0]['rights']['total_return']['amount'] = '1',
            default => $bundle['closing']['amount'] = '1',
        };
        expect(fn () => noteScheduleBrokenSource($bundle)->find($campaign->campaignId))->toThrow(RuntimeException::class, 'NOTE_SCHEDULE_INTEGRITY_FAILED');
    }
});

it('retains authenticated leap year and month end dates without shifting the original anchor', function (string $effectiveAt, array $firstDates): void {
    $this->travelTo('2028-02-01T12:00:00+00:00');
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(effectiveAt: $effectiveAt);
    $snapshot = DB::transaction(fn () => app(GetRetainedNoteSchedule::class)->find($campaign->campaignId));
    expect(array_slice(array_column($snapshot->instalments, 'dueDate'), 0, 2))->toBe($firstDates)
        ->and(array_column($snapshot->instalments, 'dueDate'))->toBe($instruction->dueDates)
        ->and($snapshot->closing->effectiveAt)->toBe($instruction->effectiveAt);
})->with([
    'leap January anchor' => ['2028-01-30T22:30:00+00:00', ['2028-02-29', '2028-03-31']],
    'August month end' => ['2026-08-30T22:30:00+00:00', ['2026-09-30', '2026-10-31']],
]);
