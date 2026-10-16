<?php

declare(strict_types=1);

use App\Domain\Underwriting\ExactFinancialValue;
use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\LoanSchedule;
use App\Domain\Underwriting\UnderwritingViolation;
use Brick\Math\BigRational;

it('prices the current approved tenor vectors with one half-up rounding', function (array $example): void {
    $hundredths = $example['rating_hundredths'];
    $rating = intdiv($hundredths, 100).'.'.intdiv($hundredths % 100, 10);
    $tenths = $example['expected_return_tenths_percent'];
    $result = (new FlatReturnPricing)->calculate($rating, $example['tenor_months']);
    expect($result['percent'])->toBe(intdiv($tenths, 10).'.'.($tenths % 10))
        ->and($result['policy_version'])->toBe('engineering-2026-09-23.4');
})->with(function (): array {
    $contents = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-fixtures-2026-09-20.json');
    if ($contents === false) {
        throw new UnexpectedValueException('Missing approved engineering fixtures.');
    }
    $pack = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return array_map(fn (array $example): array => [$example], $pack['default_pricing_examples']);
});

it('retains fractional four and five month premiums until the final percent is rounded', function (): void {
    $four = (new FlatReturnPricing)->calculate('4.9', 4);
    $five = (new FlatReturnPricing)->calculate('4.8', 5);
    expect($four['percent'])->toBe('10.3')
        ->and($four['premium_percentage_points'])->toBe(['numerator' => '1', 'denominator' => '6'])
        ->and($five['percent'])->toBe('10.7')
        ->and($five['premium_percentage_points'])->toBe(['numerator' => '1', 'denominator' => '3'])
        ->and((new FlatReturnPricing)->calculate('0.0', 6)['percent'])->toBe('15.0');
});

it('rejects unsupported tenors and unpublished rating precision', function (): void {
    foreach ([0, 2, 7, 9, 12] as $tenor) {
        expect(fn () => (new FlatReturnPricing)->calculate('4.0', $tenor))->toThrow(UnderwritingViolation::class, 'INVALID_TENOR');
    }
    foreach (['4', '4.00', '4.05', '-1.0', '5.1', 'NaN'] as $rating) {
        expect(fn () => (new FlatReturnPricing)->calculate($rating, 3))->toThrow(UnderwritingViolation::class, 'PUBLISHED_RATING_REQUIRED');
    }
});

it('conserves whole francs with a final-instalment residual and exact DSCR', function (): void {
    $schedule = LoanSchedule::build('8000000', 6, BigRational::of('121/1000'));
    expect($schedule->toArray())->toBe([
        'principal' => ['currency' => 'RWF', 'amount' => '8000000'],
        'contractual_return' => ['currency' => 'RWF', 'amount' => '968000'],
        'total' => ['currency' => 'RWF', 'amount' => '8968000'],
        'instalments' => array_map(fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount], ['1494667', '1494667', '1494667', '1494667', '1494667', '1494665']),
    ])->and((string) $schedule->dscr(BigRational::of(3000000)))->toBe('2250/1121');
    expect((string) LoanSchedule::build('3000005', 3, BigRational::of('1/10'))->contractualReturn)->toBe('300001');
});

it('never loses money precision above native safe integer limits', function (): void {
    $schedule = LoanSchedule::build('9007199254740993', 3, BigRational::of('1/10'));
    expect((string) $schedule->total)->toBe('9907919180215092')
        ->and((string) $schedule->contractualReturn)->toBe('900719925474099');
});

it('rejects malformed money and out of policy schedule terms', function (): void {
    foreach (['-1', '01', '1.0', '1e6', '', '1,000', str_repeat('9', 65)] as $amount) {
        expect(fn () => ExactFinancialValue::amount($amount))->toThrow(UnderwritingViolation::class, 'INVALID_MONEY');
    }
    expect((string) ExactFinancialValue::amount('0'))->toBe('0')
        ->and((string) ExactFinancialValue::floor(BigRational::of('-7/2')))->toBe('-4');
    foreach (['9/100', '151/1000', '1001/10000'] as $rate) {
        expect(fn () => LoanSchedule::build('3000000', 3, BigRational::of($rate)))->toThrow(UnderwritingViolation::class, 'INVALID_LOAN_TERMS');
    }
    expect(fn () => LoanSchedule::build('2999999', 3, BigRational::of('1/10')))->toThrow(UnderwritingViolation::class, 'INVALID_LOAN_TERMS');
});
