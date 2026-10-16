<?php

declare(strict_types=1);

use App\Domain\Business\ApplicationEvidence;
use App\Domain\Underwriting\CashFlowEvidence;
use Tests\Support\UnderwritingEvidenceFixture as Fixture;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function applicationEvidenceProjection(array $overrides = [], bool $current = true): array
{
    $input = [...Fixture::application(), ...$overrides];

    return (new ApplicationEvidence(new CashFlowEvidence))->project($current, $input['months'], $input['obligations'], $input['history'],
        $input['restriction_active'], $input['last_complete_month'], $input['first_repayment_month'], $input['tenor_months'], $input['recurring_owner_draw']);
}

it('reports the actual rolling period and partial calendar years using exact integer totals', function (): void {
    $result = applicationEvidenceProjection(['months' => array_reverse(Fixture::months())]);
    expect($result['period'])->toBe(['from_month' => '2023-09', 'through_month' => '2026-08', 'months' => 36])
        ->and(array_column($result['years'], 'months'))->toBe([4, 12, 12, 8])
        ->and($result['totals'])->toBe(['revenue' => ['currency' => 'RWF', 'amount' => '144000000'],
            'costs' => ['currency' => 'RWF', 'amount' => '36000000'], 'net_profit' => ['currency' => 'RWF', 'amount' => '108000000']])
        ->and($result['years'][0]['net_profit']['amount'])->toBe('12000000')
        ->and($result['eligibility'])->toBe(['status' => 'eligible'])->and($result['debt_verified'])->toBeFalse();
    $large = applicationEvidenceProjection(['months' => Fixture::months(36, '999999999999999999999999', '1000000000000000000000000')]);
    expect($large['totals']['revenue']['amount'])->toBe('35999999999999999999999964')
        ->and($large['totals']['net_profit']['amount'])->toBe('-36');
});

it('keeps absent or withdrawn evidence unknown instead of fabricating zero balances', function (bool $current, bool $empty): void {
    $result = applicationEvidenceProjection(['months' => $empty ? [] : Fixture::months()], $current);
    expect($result['period'])->toBeNull()->and($result['totals']['revenue'])->toBeNull()->and($result['years'])->toBe([])
        ->and($result['existing_debt'])->toBeNull()->and($result['eligibility']['code'])->toBe('UNDERWRITING_EVIDENCE_REQUIRED');
})->with([[false, false], [true, true]]);

it('retains factual totals while refusing missing history restrictions or invalid repeat eligibility', function (string $case): void {
    $inputs = Fixture::application();
    if ($case === 'history') {
        $inputs['history'] = null;
    } elseif ($case === 'restriction') {
        $inputs['restriction_active'] = true;
    } else {
        $inputs['history']['repeat_eligibility'] = Fixture::repeat();
        $inputs['months'] = Fixture::months(12);
    }
    $result = applicationEvidenceProjection($inputs);
    expect($result['totals']['revenue'])->not->toBeNull()->and($result['eligibility']['status'])->toBe('ineligible')
        ->and($result['eligibility']['code'])->toBe(match ($case) {
            'history' => 'POLICY_INPUT_REQUIRED', 'restriction' => 'RESTRICTION_ACTIVE', default => 'REPEAT_TRACK_INELIGIBLE',
        });
})->with(['history', 'restriction', 'unearned_repeat']);

it('accepts only the qualified repeat period and deduplicates evidenced obligations without claiming CRB proof', function (): void {
    $history = [...Fixture::application()['history'], 'has_rozine_history' => true, 'repeat_eligibility' => Fixture::repeat()];
    $obligation = ['id' => 'loan-1', 'principal' => '9000000', 'service_by_month' => Fixture::debt('100000')];
    $result = applicationEvidenceProjection(['history' => $history, 'months' => Fixture::months(12), 'obligations' => [$obligation, $obligation]]);
    expect($result['period']['months'])->toBe(12)->and($result['years'][0]['months'])->toBe(4)
        ->and($result['existing_debt'])->toBe(['currency' => 'RWF', 'amount' => '9000000'])
        ->and($result['debt_verified'])->toBeFalse()->and($result['eligibility']['status'])->toBe('eligible');
});

it('does not label malformed or incomplete observations eligible', function (string $case): void {
    $months = Fixture::months();
    if ($case === 'unverified') {
        $months[0]['verified'] = false;
    } elseif ($case === 'invalid') {
        $months[0]['month'] = '2023-13';
    } elseif ($case === 'duplicate') {
        $months[0]['month'] = $months[1]['month'];
    } elseif ($case === 'missing') {
        array_pop($months);
    } else {
        $months[0]['operating_inflow'] = '1e6';
    }
    $result = applicationEvidenceProjection(['months' => $months]);
    expect($result['eligibility']['status'])->toBe('ineligible')->and($result['existing_debt'])->toBeNull();
    if ($case !== 'missing') {
        expect($result['period'])->toBeNull()->and($result['years'])->toBe([]);
    }
})->with(['unverified', 'invalid', 'duplicate', 'missing', 'money']);
