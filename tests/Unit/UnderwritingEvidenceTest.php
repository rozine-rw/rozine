<?php

declare(strict_types=1);

use App\Domain\Underwriting\CashFlowEvidence;
use App\Domain\Underwriting\UnderwritingViolation;
use Brick\Math\BigRational;
use Tests\Support\UnderwritingEvidenceFixture;

it('reproduces the approved complete-month mean and max-deduction vectors', function (array $example): void {
    $months = UnderwritingEvidenceFixture::months($example['months']);
    $flows = [];
    foreach ($example['nocf_runs'] as $run) {
        for ($offset = 0; $offset < $run['months']; $offset++) {
            $flows[] = $run['nocf_rwf'];
        }
    }
    foreach ($months as $index => $month) {
        $months[$index] = [
            ...$month,
            'operating_inflow' => (string) (10000000 + $flows[$index]),
            'operating_outflow' => '10000000',
            'owner_draw' => (string) intdiv($example['draw_total_rwf'], $example['months']),
            'debt_service' => (string) intdiv($example['debt_total_rwf'], $example['months']),
        ];
    }
    $result = (new CashFlowEvidence)->analyze($months, '2026-08', '2026-09', 6, (string) $example['draw_commitment_rwf'], [
        ['id' => 'verified-loan', 'principal' => '1000000', 'service_by_month' => UnderwritingEvidenceFixture::debt((string) $example['peak_monthly_debt_rwf'])],
    ], $example['months'] === 12 ? UnderwritingEvidenceFixture::repeat() : null);
    expect($result['cfads']->isEqualTo(BigRational::ofFraction($example['expected_cfads']['numerator'], $example['expected_cfads']['denominator'])))->toBeTrue();
})->with(function (): array {
    $contents = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-fixtures-2026-09-20.json');
    if ($contents === false) {
        throw new UnexpectedValueException('Missing approved engineering fixtures.');
    }
    $pack = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return array_map(fn (array $example): array => [$example], $pack['completion_examples']['cash_flow']);
});

it('deduplicates economic obligations and uses peak summed monthly debt service', function (): void {
    $one = ['id' => 'loan-one', 'principal' => '30000000', 'service_by_month' => UnderwritingEvidenceFixture::debt('400000')];
    $two = ['id' => 'loan-two', 'principal' => '20000000', 'service_by_month' => UnderwritingEvidenceFixture::debt('500000')];
    $duplicate = $one;
    $duplicate['service_by_month'] = array_reverse($one['service_by_month'], true);
    $result = (new CashFlowEvidence)->analyze(UnderwritingEvidenceFixture::months(36, '10000000', '7000000'), '2026-08', '2026-09', 6, '0', [$one, $two, $duplicate]);
    expect((string) $result['cfads'])->toBe('2100000')->and((string) $result['committed_exposure'])->toBe('50000000')
        ->and((string) $result['ttm_revenue'])->toBe('120000000')->and((string) $result['remaining_room'])->toBe('-8000000');
    $duplicate['principal'] = '29999999';
    expect(fn () => (new CashFlowEvidence)->analyze(UnderwritingEvidenceFixture::months(), '2026-08', '2026-09', 6, '0', [$one, $duplicate]))->toThrow(UnderwritingViolation::class, 'OBLIGATION_EVIDENCE_CONFLICT');
    unset($one['service_by_month']['2026-12']);
    expect(fn () => (new CashFlowEvidence)->analyze(UnderwritingEvidenceFixture::months(), '2026-08', '2026-09', 6, '0', [$one]))->toThrow(UnderwritingViolation::class, 'UNDERWRITING_EVIDENCE_REQUIRED');
});

it('requires consecutive complete verified months and an explicit future repayment calendar', function (): void {
    $months = UnderwritingEvidenceFixture::months();
    expect(fn () => (new CashFlowEvidence)->analyze(array_slice($months, 1), '2026-08', '2026-09', 6, '0', []))->toThrow(UnderwritingViolation::class, 'UNDERWRITING_EVIDENCE_REQUIRED');
    $unverified = $months;
    $unverified[0] = [...$months[0], 'verified' => false];
    $duplicate = $months;
    $duplicate[0] = [...$months[0], 'month' => '2023-10'];
    foreach ([$unverified, $duplicate] as $changed) {
        expect(fn () => (new CashFlowEvidence)->analyze($changed, '2026-08', '2026-09', 6, '0', []))->toThrow(UnderwritingViolation::class, 'UNDERWRITING_EVIDENCE_REQUIRED');
    }
    expect(fn () => (new CashFlowEvidence)->analyze($months, '2026-08', '2026-08', 6, '0', []))->toThrow(UnderwritingViolation::class, 'UNDERWRITING_EVIDENCE_REQUIRED')
        ->and(fn () => (new CashFlowEvidence)->analyze($months, '2026-13', '2027-01', 6, '0', []))->toThrow(UnderwritingViolation::class, 'INVALID_OBSERVATION_MONTH');
});

it('requires every repeat-track prerequisite before admitting a twelve-month window', function (string $field): void {
    $repeat = UnderwritingEvidenceFixture::repeat();
    $repeat['baseline_passed'] = $field !== 'baseline_passed';
    $repeat['settled_notes'] = $field === 'settled_notes' ? 0 : 1;
    $repeat['late_payments'] = $field === 'late_payments' ? 1 : 0;
    $repeat['gap_audits_complete'] = $field !== 'gap_audits_complete';
    $repeat['automated_collection'] = $field !== 'automated_collection';
    expect(fn () => (new CashFlowEvidence)->analyze(UnderwritingEvidenceFixture::months(12), '2026-08', '2026-09', 6, '0', [], $repeat))->toThrow(UnderwritingViolation::class, 'REPEAT_TRACK_INELIGIBLE');
})->with(['baseline_passed', 'settled_notes', 'late_payments', 'gap_audits_complete', 'automated_collection']);

it('preserves negative NOCF and distinguishes audited zero from unavailable ratios', function (): void {
    $negative = (new CashFlowEvidence)->analyze(UnderwritingEvidenceFixture::months(36, '1000000', '4000000'), '2026-08', '2026-09', 6, '0', []);
    expect((string) $negative['cfads'])->toBe('-3000000')->and($negative['positive_months'])->toBe(0)
        ->and((string) $negative['relative_mad'])->toBe('0');
    $zero = (new CashFlowEvidence)->analyze(UnderwritingEvidenceFixture::months(36, '0', '0'), '2026-08', '2026-09', 6, '0', []);
    expect($zero['coverage'])->toBeNull()->and($zero['cfads_margin'])->toBeNull()->and((string) $zero['cfads'])->toBe('0');
});

it('projects corresponding calendar months with the median cap and exactly one draw deduction', function (): void {
    $months = UnderwritingEvidenceFixture::months(12);
    foreach ($months as $index => $month) {
        $months[$index] = [
            ...$month,
            'owner_draw' => '100000',
            'debt_service' => '300000',
            'operating_inflow' => $index === 0 ? '10000000' : $month['operating_inflow'],
            'operating_outflow' => $index === 1 ? '4500000' : $month['operating_outflow'],
        ];
    }
    $debt = UnderwritingEvidenceFixture::debt('100000');
    $debt['2026-09'] = '900000';
    $result = (new CashFlowEvidence)->analyze($months, '2026-08', '2026-09', 6, '200000', [['id' => 'loan', 'principal' => '1000000', 'service_by_month' => $debt]], UnderwritingEvidenceFixture::repeat());
    $projection = $result['repeat_projections'];
    expect($projection[0]['source_month'])->toBe('2025-09')->and((string) $projection[0]['nocf'])->toBe('4800000')
        ->and((string) $projection[0]['cfads'])->toBe('3700000')->and((string) $projection[1]['cfads'])->toBe('-1000000')
        ->and($projection[4]['source_month'])->toBe('2026-01')->and((string) $projection[4]['existing_debt_service'])->toBe('300000');
});
