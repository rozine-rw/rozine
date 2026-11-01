<?php

declare(strict_types=1);

use App\Domain\Primary\EarningsFeeBand;
use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\RiskStatements;
use Brick\Math\BigInteger;

it('selects the INV-R04 band from active capital at each boundary', function (string $capital, string $tier, int $rate): void {
    expect(EarningsFeeBand::forActiveCapital(BigInteger::of($capital)))
        ->toBe(['tier' => $tier, 'rate_bps' => $rate, 'basis' => 'return_only', 'policy_version' => 'brs-2.0-inv-r04']);
})->with([
    ['0', 'standard', 1500], ['999999', 'standard', 1500], ['1000000', 'bronze', 1200], ['4999999', 'bronze', 1200],
    ['5000000', 'silver', 1000], ['24999999', 'silver', 1000], ['25000000', 'gold', 900], ['99999999', 'gold', 900],
    ['100000000', 'platinum', 800], ['900000000000', 'platinum', 800],
]);

it('names where the next, cheaper band starts, and none from the top band (#383)', function (string $capital, ?string $next): void {
    expect(EarningsFeeBand::nextBandAt(BigInteger::of($capital)))->toBe($next);
})->with([
    ['0', '1000000'], ['999999', '1000000'], ['1000000', '5000000'], ['24999999', '25000000'], ['99999999', '100000000'], ['100000000', null],
]);

it('writes a basis-point rate as a percentage with two decimals (#383)', function (): void {
    expect(array_map(EarningsFeeBand::percent(...), array_values(EarningsFeeBand::RATES)))->toBe(['15.00', '12.00', '10.00', '9.00', '8.00'])
        ->and(EarningsFeeBand::percent(1205))->toBe('12.05');
});

it('refuses negative active capital', function (): void {
    expect(fn () => EarningsFeeBand::forActiveCapital(BigInteger::of('-1')))->toThrow(PrimaryViolation::class, 'INVALID_MONEY');
});

it('pins the five KYC-R05 statements to their version and digest', function (): void {
    expect(RiskStatements::STATEMENTS)->toHaveCount(5)
        ->and(RiskStatements::VERSION)->toBe('brs-2.0-kyc-r05')
        ->and(RiskStatements::sha256())->toMatch('/^[0-9a-f]{64}$/')->toBe(RiskStatements::sha256());
});
