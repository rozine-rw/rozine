<?php

declare(strict_types=1);

use App\Domain\Underwriting\ApplicationUnderwriting;
use App\Domain\Underwriting\CashFlowEvidence;
use App\Domain\Underwriting\EngineScorecard;
use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\LoanCapacity;
use App\Domain\Underwriting\UnderwritingViolation;
use Tests\Support\UnderwritingEvidenceFixture;

function applicationCalculator(): ApplicationUnderwriting
{
    return new ApplicationUnderwriting(new CashFlowEvidence, new EngineScorecard, new FlatReturnPricing, new LoanCapacity);
}

it('composes a deterministic exact-money replay record from the verified baseline', function (): void {
    $inputs = UnderwritingEvidenceFixture::application();
    $result = applicationCalculator()->evaluate($inputs);
    expect($result['eligible'])->toBeTrue()->and($result['code'])->toBe('UNDERWRITING_ELIGIBLE')
        ->and($result['calculation_version'])->toBe(ApplicationUnderwriting::VERSION)
        ->and($result['policy_version'])->toBe(FlatReturnPricing::POLICY_VERSION)
        ->and($result['inputs'])->toBe($inputs)
        ->and($result['cash_flow']['cfads'])->toBe(['numerator' => '3000000', 'denominator' => '1'])
        ->and($result['scorecard']['rating'])->toBe('4.6')
        ->and($result['pricing']['percent'])->toBe('11.1')
        ->and($result['capacity']['offer']['principal'])->toBe(['currency' => 'RWF', 'amount' => '10800000'])
        ->and($result['capacity']['offer']['contractual_return']['amount'])->toBe('1198800')
        ->and($result['capacity']['offer']['total']['amount'])->toBe('11998800')
        ->and($result['capacity']['offer']['units'])->toBe('2160')
        ->and($result['maximum_capacity'])->toBe($result['capacity'])
        ->and(applicationCalculator()->evaluate($result['inputs']))->toBe($result)
        ->and(json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR))->toBe($result);
});

it('retains the maximum offer while recalculating a lower accepted principal', function (): void {
    $inputs = [...UnderwritingEvidenceFixture::application(), 'accepted_principal' => '5000000'];
    $result = applicationCalculator()->evaluate($inputs);
    expect($result['capacity']['offer']['principal']['amount'])->toBe('5000000')
        ->and($result['maximum_capacity']['offer']['principal']['amount'])->toBe('10800000')
        ->and($result['capacity']['offer']['contractual_return']['amount'])->toBe('555000')
        ->and($result['capacity']['offer']['instalments'])->toHaveCount(6)
        ->and($result['capacity']['offer']['instalments'][5]['amount'])->toBe('925835')
        ->and($result['inputs']['requested_principal'])->toBe('12000000');
});

it('does not use a lower accepted amount to bypass the original-request rejection', function (): void {
    $result = applicationCalculator()->evaluate([...UnderwritingEvidenceFixture::application(), 'requested_principal' => '20000000', 'accepted_principal' => '3000000']);
    expect($result['eligible'])->toBeFalse()->and($result['code'])->toBe('DSCR_BELOW_CUTOFF')
        ->and($result['capacity']['offer'])->toBeNull()->and($result['maximum_capacity']['offer'])->toBeNull();
});

it('uses the audited existing exposure when limiting the maximum offer', function (): void {
    $result = applicationCalculator()->evaluate([...UnderwritingEvidenceFixture::application(),
        'obligations' => [['id' => 'existing-obligation', 'principal' => '10000000', 'service_by_month' => UnderwritingEvidenceFixture::debt('0')]]]);
    expect($result['cash_flow']['committed_exposure'])->toBe(['numerator' => '10000000', 'denominator' => '1'])
        ->and($result['maximum_capacity']['offer']['principal']['amount'])->toBe('6800000')
        ->and($result['maximum_capacity']['reason_codes'])->toContain('EXPOSURE_CAPPED');
});

it('does not fabricate a history, a rating or an offer when required facts are unavailable', function (string $missing): void {
    $inputs = UnderwritingEvidenceFixture::application();
    if ($missing === 'history') {
        $inputs['history'] = null;
    } elseif ($missing === 'conduct') {
        $inputs['history']['has_rozine_history'] = true;
    } elseif ($missing === 'denominators') {
        $inputs['months'] = UnderwritingEvidenceFixture::months(36, '0', '0');
    } else {
        array_pop($inputs['months']);
    }
    $result = applicationCalculator()->evaluate($inputs);
    expect($result['eligible'])->toBeFalse()
        ->and($result['code'])->toBe($missing === 'history' ? 'POLICY_INPUT_REQUIRED' : 'UNDERWRITING_EVIDENCE_REQUIRED')
        ->and($result['pricing'])->toBeNull()->and($result['capacity'])->toBeNull();
})->with(['history', 'conduct', 'denominators', 'month']);

it('applies the additional repeat window and monthly affordability rules to recorded history', function (): void {
    $inputs = UnderwritingEvidenceFixture::application();
    $inputs['months'] = UnderwritingEvidenceFixture::months(12);
    $inputs['history']['has_rozine_history'] = true;
    $inputs['history']['repeat_eligibility'] = UnderwritingEvidenceFixture::repeat();
    $inputs['history']['instalment_conduct'] = ['on_time' => 6, 'total' => 6];
    $inputs['history']['report_conduct'] = ['on_time' => 6, 'total' => 6];
    $result = applicationCalculator()->evaluate($inputs);
    expect($result['eligible'])->toBeTrue()->and($result['cash_flow']['verified_months'])->toBe(12)
        ->and($result['cash_flow']['repeat_projections'])->toHaveCount(6)
        ->and($result['cash_flow']['repeat_projections'][0]['cfads'])->toBe(['numerator' => '3000000', 'denominator' => '1'])
        ->and($result['capacity']['repeat_monthly_dscr'])->toHaveCount(6);
    $inputs['history']['repeat_eligibility']['late_payments'] = 1;
    $refused = applicationCalculator()->evaluate($inputs);
    expect($refused['eligible'])->toBeFalse()->and($refused['evidence_reason'])->toBe('REPEAT_TRACK_INELIGIBLE')
        ->and($refused['capacity'])->toBeNull();
});

it('does not grant the shorter repeat window without actual platform history', function (): void {
    $inputs = UnderwritingEvidenceFixture::application();
    $inputs['months'] = UnderwritingEvidenceFixture::months(12);
    $inputs['history']['repeat_eligibility'] = UnderwritingEvidenceFixture::repeat();
    $result = applicationCalculator()->evaluate($inputs);
    expect($result['eligible'])->toBeFalse()->and($result['evidence_reason'])->toBe('REPEAT_TRACK_INELIGIBLE');
});

it('records a restriction refusal without publishing arithmetic offer facts', function (): void {
    $result = applicationCalculator()->evaluate([...UnderwritingEvidenceFixture::application(), 'restriction_active' => true]);
    expect($result['eligible'])->toBeFalse()->and($result['code'])->toBe('RESTRICTION_ACTIVE')
        ->and($result['capacity'])->toBeNull()->and($result['cash_flow'])->toBeNull();
});

it('rejects invalid original or accepted monetary terms', function (string $field, string $amount, string $code): void {
    $inputs = $field === 'requested_principal' ? [...UnderwritingEvidenceFixture::application(), 'requested_principal' => $amount]
        : [...UnderwritingEvidenceFixture::application(), 'accepted_principal' => $amount];
    expect(fn () => applicationCalculator()->evaluate($inputs))->toThrow(UnderwritingViolation::class, $code);
})->with([
    ['requested_principal', '2999999', 'INVALID_LOAN_TERMS'],
    ['accepted_principal', '5000001', 'INVALID_ACCEPTED_PRINCIPAL'],
    ['accepted_principal', '12000000', 'INVALID_ACCEPTED_PRINCIPAL'],
]);
