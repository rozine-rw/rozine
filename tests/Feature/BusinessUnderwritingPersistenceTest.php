<?php

declare(strict_types=1);

use App\Application\Business\FindBusinessOperation;
use App\Application\Business\RecordIsolatedBusinessCreditFacts;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Underwriting\ApplicationUnderwriting;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessCreditSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessCreditFactsFixture;
use Tests\Support\BusinessQuoteFixture;
use Tests\Support\ConsentFixture;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 00:00:00', 'UTC'));
});

it('persists the qualified repeat window and signs the exact replayable whole-unit offer', function (): void {
    $fixture = BusinessQuoteFixture::make(credit: false, historyMonths: 12);
    $facts = BusinessCreditFactsFixture::repeat();
    app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 0,
        $facts, 'synthetic:qualified-repeat', 'Synthetic settled-note and baseline evidence.', (string) Str::uuid());
    ConsentFixture::record($fixture['audit']['staff']);
    $request = (string) Str::uuid();
    $receipt = BusinessQuoteFixture::evaluate($fixture, request: $request);
    $offer = $receipt['data']['quote'];
    expect($offer['status'])->toBe('ready')->and($offer['principal']['amount'])->toBe('10840000')
        ->and($offer['rate_pct'])->toBe('10.7')->and($offer['interest']['amount'])->toBe('1159880')
        ->and($offer['total']['amount'])->toBe('11999880')->and($offer['schedule'])->toHaveCount(6);
    $quote = BusinessApplicationQuote::query()->firstOrFail();
    $payload = $quote->payload;
    $result = $payload['result'];
    expect($result['inputs']['history'])->toBe($facts['history'])
        ->and($result['inputs']['last_complete_month'])->toBe('2026-08')
        ->and($result['inputs']['first_repayment_month'])->toBe('2026-09')
        ->and($result['inputs']['months'])->toHaveCount(12)
        ->and($result['cash_flow']['verified_months'])->toBe(12)
        ->and($result['cash_flow']['ttm_revenue'])->toBe(['numerator' => '48000000', 'denominator' => '1'])
        ->and($result['scorecard']['rating'])->toBe('4.9')
        ->and($result['capacity']['offer']['units'])->toBe('2168')
        ->and($result['capacity']['repeat_monthly_dscr'])->toHaveCount(6)
        ->and($payload['credit']['sha256'])->toBe(BusinessCreditSnapshot::query()->firstOrFail()->sha256)
        ->and($quote->sha256)->toBe(hash('sha256', app(CanonicalJson::class)->encode($payload)))
        ->and(app(ApplicationUnderwriting::class)->evaluate($result['inputs']))->toBe($result);
    foreach ($result['cash_flow']['repeat_projections'] as $index => $projection) {
        $instalment = $result['capacity']['offer']['instalments'][$index]['amount'];
        expect($projection['month'])->toBe(CarbonImmutable::parse('2026-09-01')->addMonths($index)->format('Y-m'))
            ->and($projection['source_month'])->toBe(CarbonImmutable::parse('2025-09-01')->addMonths($index)->format('Y-m'))
            ->and($projection['cfads'])->toBe(['numerator' => '3000000', 'denominator' => '1'])
            ->and($instalment)->toBe('1999980')
            ->and((int) $projection['cfads']['numerator'] * 20)->toBeGreaterThanOrEqual((int) $instalment * 27);
    }
    $nextUnitTotal = intdiv(((int) $offer['principal']['amount'] + 5000) * 1107, 1000);
    expect(18000000 * 2)->toBeGreaterThanOrEqual((int) $offer['total']['amount'] * 3)
        ->and(18000000 * 2)->toBeLessThan($nextUnitTotal * 3);
    app(SaveBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
        $fixture['application']->id, 3, BusinessApplicationFixture::fields('12000000'), 'review', (string) Str::uuid());
    $submitted = BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    expect($submitted['code'])->toBe('APPLICATION_SUBMITTED')->and($submitted['data']['quote'])->toEqual($offer);
    $submission = BusinessApplicationSubmission::query()->firstOrFail();
    $signature = BusinessApplicationSignature::query()->firstOrFail();
    expect($submission->business_application_quote_id)->toBe($quote->id)
        ->and($submission->payload['agreement']['quote_sha256'])->toBe($quote->sha256)
        ->and($signature->payload['agreement']['quote_sha256'])->toBe($quote->sha256)
        ->and(BusinessQuoteFixture::evaluate($fixture, request: $request))->toBe($receipt)
        ->and(app(FindBusinessOperation::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, 'evaluate', $request))->toBe($receipt);
});

it('refuses incomplete windows and unqualified repeat history without publishing acceptance authority', function (int $months, string $case, string $reason): void {
    $fixture = BusinessQuoteFixture::make(credit: false, historyMonths: $months);
    $facts = $case === 'first-time' ? BusinessCreditFactsFixture::facts() : BusinessCreditFactsFixture::repeat();
    match ($case) {
        'baseline' => $facts['history']['repeat_eligibility']['baseline_passed'] = false,
        'settled note' => $facts['history']['repeat_eligibility']['settled_notes'] = 0,
        'late payment' => $facts['history']['repeat_eligibility']['late_payments'] = 1,
        'audit gap' => $facts['history']['repeat_eligibility']['gap_audits_complete'] = false,
        'collection' => $facts['history']['repeat_eligibility']['automated_collection'] = false,
        'first-time', 'repeat' => null,
        default => throw new LogicException('Unknown persisted history case.'),
    };
    app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 0,
        $facts, 'synthetic:history-refusal', 'Incomplete synthetic history scenario.', (string) Str::uuid());
    ConsentFixture::record($fixture['audit']['staff']);
    $request = (string) Str::uuid();
    $receipt = BusinessQuoteFixture::evaluate($fixture, request: $request);
    expect($receipt['data']['quote'])->toHaveKeys(['status', 'code', 'message'])
        ->and($receipt['data']['quote']['status'])->toBe('refused')
        ->and($receipt['data']['quote']['code'])->toBe('UNDERWRITING_EVIDENCE_REQUIRED')
        ->and($receipt['data']['quote'])->not->toHaveKey('principal')
        ->and($receipt['data']['submission'])->toBeNull()
        ->and(BusinessQuoteFixture::evaluate($fixture, request: $request))->toBe($receipt);
    $stored = BusinessApplicationQuote::query()->firstOrFail()->payload['result'];
    expect($stored['evidence_reason'])->toBe($reason)->and($stored['cash_flow'])->toBeNull()
        ->and($stored['scorecard'])->toBeNull()->and($stored['capacity'])->toBeNull()
        ->and(app(ApplicationUnderwriting::class)->evaluate($stored['inputs']))->toBe($stored);
    $advance = app(SaveBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
        $fixture['application']->id, 3, BusinessApplicationFixture::fields('12000000'), 'review', (string) Str::uuid());
    expect($advance['code'])->toBe('QUOTE_STALE')->and($fixture['application']->refresh()->step)->toBe('raise');
    $this->assertDatabaseCount('business_application_signatures', 0);
    $this->assertDatabaseCount('business_application_submissions', 0);
})->with([
    'first time cannot use one audited year' => [12, 'first-time', 'UNDERWRITING_EVIDENCE_REQUIRED'],
    'one missing baseline month' => [35, 'first-time', 'UNDERWRITING_EVIDENCE_REQUIRED'],
    'one missing repeat month' => [11, 'repeat', 'UNDERWRITING_EVIDENCE_REQUIRED'],
    'no passed original baseline' => [12, 'baseline', 'REPEAT_TRACK_INELIGIBLE'],
    'no fully settled note' => [12, 'settled note', 'REPEAT_TRACK_INELIGIBLE'],
    'one late payment' => [12, 'late payment', 'REPEAT_TRACK_INELIGIBLE'],
    'unaudited gap' => [12, 'audit gap', 'REPEAT_TRACK_INELIGIBLE'],
    'no automated collection' => [12, 'collection', 'REPEAT_TRACK_INELIGIBLE'],
]);

it('deduplicates the same evidenced debt across sources and isolates other businesses exposure', function (): void {
    $schedule = array_fill_keys(['2026-09', '2026-10', '2026-11', '2026-12', '2027-01', '2027-02'], '50000');
    $schedule['2026-11'] = '100000';
    $debt = ['id' => 'synthetic:existing-note', 'principal' => '10000000', 'service_by_month' => $schedule];
    $fixture = BusinessQuoteFixture::make(credit: false, auditedObligations: [$debt]);
    $facts = [...BusinessCreditFactsFixture::facts(), 'obligations' => [$debt]];
    app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 0,
        $facts, 'synthetic:existing-note', 'Synthetic complete exposure calendar.', (string) Str::uuid());
    $other = BusinessApplicationFixture::make();
    BusinessCreditFactsFixture::record($other['authority']['staff'], $other['business']->id,
        facts: [...BusinessCreditFactsFixture::facts(), 'obligations' => [[...$debt, 'principal' => '19000000']]]);
    $receipt = BusinessQuoteFixture::evaluate($fixture);
    expect($receipt['data']['quote']['status'])->toBe('ready')->and($receipt['data']['quote']['principal']['amount'])->toBe('6800000');
    $result = BusinessApplicationQuote::query()->firstOrFail()->payload['result'];
    expect($result['inputs']['obligations'])->toHaveCount(2)
        ->and($result['cash_flow']['ttm_revenue'])->toBe(['numerator' => '48000000', 'denominator' => '1'])
        ->and($result['cash_flow']['exposure_limit'])->toBe(['numerator' => '16800000', 'denominator' => '1'])
        ->and($result['cash_flow']['committed_exposure'])->toBe(['numerator' => '10000000', 'denominator' => '1'])
        ->and($result['cash_flow']['remaining_room'])->toBe(['numerator' => '6800000', 'denominator' => '1'])
        ->and($result['cash_flow']['existing_debt_service'])->toBe(['numerator' => '100000', 'denominator' => '1'])
        ->and($result['cash_flow']['cfads'])->toBe(['numerator' => '2900000', 'denominator' => '1'])
        ->and($result['capacity']['reason_codes'])->toContain('EXPOSURE_CAPPED')
        ->and(app(ApplicationUnderwriting::class)->evaluate($result['inputs']))->toBe($result);
});

it('refuses contradictory or incomplete persisted debt calendars instead of assuming zero debt', function (string $case): void {
    $schedule = array_fill_keys(['2026-09', '2026-10', '2026-11', '2026-12', '2027-01', '2027-02'], '50000');
    $debt = ['id' => 'synthetic:existing-note', 'principal' => '10000000', 'service_by_month' => $schedule];
    $fixture = BusinessQuoteFixture::make(credit: false, auditedObligations: $case === 'missing month' ? [] : [$debt]);
    if ($case === 'principal') {
        $debt['principal'] = '9999999';
    } elseif ($case === 'service') {
        $debt['service_by_month']['2026-11'] = '50001';
    } else {
        unset($debt['service_by_month']['2027-02']);
    }
    app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 0,
        [...BusinessCreditFactsFixture::facts(), 'obligations' => [$debt]], 'synthetic:debt-refusal', 'Synthetic incomplete or conflicting source.', (string) Str::uuid());
    $request = (string) Str::uuid();
    $receipt = BusinessQuoteFixture::evaluate($fixture, request: $request);
    expect($receipt['data']['quote']['status'])->toBe('refused')->and($receipt['data']['quote']['code'])->toBe('UNDERWRITING_EVIDENCE_REQUIRED')
        ->and($receipt['data']['quote'])->not->toHaveKey('principal')
        ->and(BusinessQuoteFixture::evaluate($fixture, request: $request))->toBe($receipt);
    $result = BusinessApplicationQuote::query()->firstOrFail()->payload['result'];
    expect($result['evidence_reason'])->toBe($case === 'missing month' ? 'UNDERWRITING_EVIDENCE_REQUIRED' : 'OBLIGATION_EVIDENCE_CONFLICT')
        ->and($result['capacity'])->toBeNull()->and(app(ApplicationUnderwriting::class)->evaluate($result['inputs']))->toBe($result);
})->with(['principal', 'service', 'missing month']);

it('invalidates a repeat offer after corrected conduct without rewriting its historical receipt', function (): void {
    $fixture = BusinessQuoteFixture::make(credit: false, historyMonths: 12);
    $facts = BusinessCreditFactsFixture::repeat();
    $source = app(RecordIsolatedBusinessCreditFacts::class);
    $source->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 0, $facts,
        'synthetic:qualified-repeat', 'Synthetic baseline and settled note.', (string) Str::uuid());
    $request = (string) Str::uuid();
    $original = BusinessQuoteFixture::evaluate($fixture, request: $request);
    $quote = BusinessApplicationQuote::query()->firstOrFail();
    $payload = $quote->payload;
    $facts['history']['repeat_eligibility']['late_payments'] = 1;
    $source->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 1, $facts,
        'synthetic:corrected-conduct', 'Correct the synthetic late-payment history.', (string) Str::uuid());
    expect(BusinessQuoteFixture::quote($fixture))->toBeNull()
        ->and(BusinessQuoteFixture::evaluate($fixture, request: $request))->toBe($original)
        ->and($quote->refresh()->payload)->toBe($payload);
    $current = BusinessQuoteFixture::evaluate($fixture, 3);
    expect($current['data']['quote']['status'])->toBe('refused')
        ->and(BusinessApplicationQuote::query()->where('revision', 2)->firstOrFail()->payload['result']['evidence_reason'])->toBe('REPEAT_TRACK_INELIGIBLE');
    $this->assertDatabaseCount('business_application_quotes', 2);
});
