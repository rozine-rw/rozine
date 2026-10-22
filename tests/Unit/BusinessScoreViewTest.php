<?php

declare(strict_types=1);

use App\Domain\Underwriting\BusinessScoreView;
use App\Domain\Underwriting\LoanCapacity;
use Brick\Math\BigInteger;

$ratio = fn (string $numerator, string $denominator = '1'): array => ['numerator' => $numerator, 'denominator' => $denominator];
$money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];

it('groups the engine components into four weighted factors out of 100', function () use ($ratio): void {
    $factors = BusinessScoreView::factors(['coverage' => $ratio('80'), 'cfads_margin' => $ratio('55'), 'conduct' => $ratio('50'),
        'nocf_stability' => $ratio('181', '2'), 'positive_months' => $ratio('100'), 'history_depth' => $ratio('100', '3')]);

    // (80×30 + 55×20) ÷ 50 = 70; (90.5×20 + 100×10) ÷ 30 = 93.67; 33.33 rounds to 33.
    expect($factors)->toBe([['key' => 'financial_health', 'score' => 70], ['key' => 'repayment_history', 'score' => 50],
        ['key' => 'statement_consistency', 'score' => 94], ['key' => 'track_record', 'score' => 33]]);
});

it('sizes capacity on the engine offer at the lowest limit, less funded notes', function () use ($ratio, $money): void {
    $sizing = (new BusinessScoreView(new LoanCapacity))->sizing(['cfads' => $ratio('1000000'), 'exposure_limit' => $ratio('10000000'),
        'committed_exposure' => $ratio('2000000'), 'remaining_room' => $ratio('8000000'), 'repeat_projections' => []],
        ['percent' => '13.0', 'fraction' => $ratio('13', '100')], $ratio('400000000', '113'), 6, BigInteger::of('1000000'));

    expect($sizing)->toBe(['cash_per_month' => $money('1000000'), 'target_cover' => '1.50', 'term_months' => 6, 'rate_percent' => '13.0',
        'limits' => [['key' => 'cash_flow', 'value' => $money('3539823'), 'binding' => true],
            ['key' => 'exposure', 'value' => $money('8000000'), 'binding' => false, 'limit' => $money('10000000'), 'committed' => $money('2000000')],
            ['key' => 'policy_max', 'value' => $money('100000000'), 'binding' => false]],
        'approved' => $money('3535000'), 'headroom' => $money('2535000')]);
});

it('offers nothing below the minimum principal, and never a negative limit', function () use ($ratio, $money): void {
    $scores = new BusinessScoreView(new LoanCapacity);
    $room = $scores->sizing(['cfads' => $ratio('1000000'), 'exposure_limit' => $ratio('5000000'), 'committed_exposure' => $ratio('2500000'),
        'remaining_room' => $ratio('2500000'), 'repeat_projections' => []], ['percent' => '13.0', 'fraction' => $ratio('13', '100')],
        $ratio('400000000', '113'), 6, BigInteger::zero());
    $cash = $scores->sizing(['cfads' => $ratio('-1000'), 'exposure_limit' => $ratio('5000000'), 'committed_exposure' => $ratio('0'),
        'remaining_room' => $ratio('5000000'), 'repeat_projections' => []], ['percent' => '13.0', 'fraction' => $ratio('13', '100')],
        $ratio('-4000', '1'), 6, BigInteger::zero());

    expect(array_column($room['limits'], 'binding'))->toBe([false, true, false])
        ->and($room['approved'])->toBe($money('0'))->and($room['headroom'])->toBe($money('0'))
        ->and($cash['limits'][0])->toBe(['key' => 'cash_flow', 'value' => $money('0'), 'binding' => true])
        ->and($cash['cash_per_month'])->toBe($money('-1000'))->and($cash['approved'])->toBe($money('0'));
});
