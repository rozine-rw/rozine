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

it('refuses negative active capital', function (): void {
    expect(fn () => EarningsFeeBand::forActiveCapital(BigInteger::of('-1')))->toThrow(PrimaryViolation::class, 'INVALID_MONEY');
});

it('pins the five KYC-R05 statements to their version and digest', function (): void {
    expect(RiskStatements::STATEMENTS)->toHaveCount(5)
        ->and(RiskStatements::VERSION)->toBe('brs-2.0-kyc-r05')
        ->and(RiskStatements::sha256())->toMatch('/^[0-9a-f]{64}$/')->toBe(RiskStatements::sha256());
});
