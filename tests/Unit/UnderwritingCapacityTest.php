<?php

declare(strict_types=1);

use App\Domain\Underwriting\LoanCapacity;
use App\Domain\Underwriting\LoanSchedule;
use App\Domain\Underwriting\UnderwritingViolation;
use Brick\Math\BigRational;

it('preserves approved sizing vectors before applying the current unit grid', function (array $example): void {
    $input = $example['inputs'];
    $expected = $example['expected'];
    $result = (new LoanCapacity)->evaluate((string) $input['requested_rwf'], $input['tenor_months'], BigRational::ofFraction($input['rate_tenths_percent'], 1000), BigRational::of($input['cfads_rwf']), BigRational::of($input['remaining_room_rwf']));
    if ($expected['principal_rwf'] === null) {
        expect($result['eligible'])->toBeFalse()->and($result['offer'])->toBeNull()
            ->and($result['code'])->toBe($expected['status'] === 'REJECT_ORIGINAL' ? 'DSCR_BELOW_CUTOFF' : 'CAPACITY_BELOW_MINIMUM');

        return;
    }
    expect($result['eligible'])->toBeTrue()
        ->and($result['guarded_principal']['amount'])->toBe((string) $expected['principal_rwf'])
        ->and($result['rounding_guard']['amount'])->toBe((string) $expected['rounding_guard_rwf'])
        ->and($result['offer']['principal']['amount'])->toBe((string) (intdiv($expected['principal_rwf'], 5000) * 5000));
    expect(BigRational::ofFraction($result['final_dscr']['numerator'], $result['final_dscr']['denominator'])->isGreaterThanOrEqualTo('3/2'))->toBeTrue();
})->with(function (): array {
    $contents = file_get_contents(__DIR__.'/../../docs/phase-0/engineering-contract-fixtures-2026-09-20.json');
    if ($contents === false) {
        throw new UnexpectedValueException('Missing approved engineering fixtures.');
    }
    $pack = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return array_map(fn (array $example): array => [$example], array_values(array_filter($pack['numeric_cases'], fn (array $example): bool => $example['kind'] === 'sizing')));
});

it('builds the exact current EC-010 offer and discloses quantization', function (): void {
    $result = (new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000));
    expect($result['offer']['principal']['amount'])->toBe('10700000')->and($result['offer']['units'])->toBe('2140')
        ->and($result['offer']['contractual_return']['amount'])->toBe('1294700')->and($result['offer']['total']['amount'])->toBe('11994700')
        ->and($result['reason_codes'])->toBe(['DSCR_SCALED', 'ISSUANCE_UNIT_QUANTIZED']);
});

it('does not resize a rejected original request even for a smaller acceptance', function (): void {
    $result = (new LoanCapacity)->evaluate('10000000', 6, BigRational::of('121/1000'), BigRational::of(2000000), BigRational::of(100000000), acceptedPrincipal: '3000000');
    expect($result['code'])->toBe('DSCR_BELOW_CUTOFF')->and($result['raw_capacity'])->toBeNull();
});

it('compares the original cutoff exactly on each side of equality', function (string $cfads, bool $passes): void {
    $result = (new LoanCapacity)->evaluate('3000000', 3, BigRational::of('1/10'), BigRational::of($cfads), BigRational::of(100000000));
    expect($result['code'] !== 'DSCR_BELOW_CUTOFF')->toBe($passes);
})->with([['1374999', false], ['1375000', true], ['1375001', true], ['-1', false]]);

it('preserves fractional and negative exposure without rounding into eligibility', function (string $room): void {
    $result = (new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of($room));
    expect($result['code'])->toBe('CAPACITY_BELOW_MINIMUM')->and($result['offer'])->toBeNull()
        ->and($result['remaining_room']['numerator'])->toBe((string) BigRational::of($room)->getNumerator());
})->with(['2999999.65', '-500000', '0']);

it('floors fractional exposure and caps high capacity at the principal maximum', function (): void {
    $limited = (new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of('9000000.75'));
    expect($limited['rounded_candidate']['amount'])->toBe('9000000')->and($limited['reason_codes'])->toContain('EXPOSURE_CAPPED');
    $capped = (new LoanCapacity)->evaluate('150000000', 6, BigRational::of('1/10'), BigRational::of(100000000), BigRational::of(1000000000));
    expect($capped['offer']['principal']['amount'])->toBe('100000000')->and($capped['reason_codes'])->toContain('PRINCIPAL_MAX_CAPPED');
});

it('rebuilds a lower accepted schedule without bypassing caps', function (): void {
    $result = (new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), acceptedPrincipal: '8000000');
    expect($result['offer']['principal']['amount'])->toBe('8000000')->and($result['offer']['total']['amount'])->toBe('8968000')
        ->and($result['offer']['instalments'][5]['amount'])->toBe('1494665');
    foreach (['2995000', '8000001', '10705000'] as $accepted) {
        expect(fn () => (new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), acceptedPrincipal: $accepted))->toThrow(UnderwritingViolation::class, 'INVALID_ACCEPTED_PRINCIPAL');
    }
});

it('finds the largest whole-unit repeat offer satisfying every actual instalment', function (): void {
    $monthly = array_map(BigRational::of(...), [2000000, 2100000, 2000000, 2200000, 2000000, 2000000]);
    $result = (new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), $monthly);
    expect($result['eligible'])->toBeTrue()->and($result['reason_codes'])->toContain('REPEAT_MONTHLY_DSCR_SCALED');
    foreach ($result['repeat_monthly_dscr'] as $ratio) {
        expect(BigRational::ofFraction($ratio['numerator'], $ratio['denominator'])->isGreaterThanOrEqualTo('27/20'))->toBeTrue();
    }
    $next = LoanSchedule::build((string) ((int) $result['offer']['principal']['amount'] + 5000), 6, BigRational::of('121/1000'));
    expect($monthly[0]->dividedBy($next->instalments[0])->isLessThan('27/20'))->toBeTrue();
});

it('denies repeat offers with a weak or missing month and preserves a strong-month offer', function (): void {
    $positive = array_fill(0, 6, BigRational::of(3000000));
    expect((new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), $positive)['offer']['principal']['amount'])->toBe('10700000');
    $positive[5] = BigRational::of(-1);
    expect((new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), $positive)['code'])->toBe('CAPACITY_BELOW_MINIMUM');
    expect(fn () => (new LoanCapacity)->evaluate('12000000', 6, BigRational::of('121/1000'), BigRational::of(3000000), BigRational::of(100000000), [BigRational::of(3000000)]))->toThrow(UnderwritingViolation::class, 'UNDERWRITING_EVIDENCE_REQUIRED');
});
