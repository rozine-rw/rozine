<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/** Undated rights derived from the published total-payment schedule (CFG-02). */
final readonly class UnitRights
{
    public const UNIT_PRINCIPAL = '5000';

    /**
     * @param  list<array{index: int, principal: string, return: string}>  $instalments
     * @param  list<string>  $campaignPayments
     */
    private function __construct(public UnitOrdinals $ordinals, public BigInteger $principal,
        public BigInteger $contractualReturn, public array $instalments, public BigInteger $campaignPrincipal, public BigInteger $campaignReturn, public array $campaignPayments) {}

    /**
     * No pricing, eligibility or due-date calculation occurs here. The caller
     * supplies the retained campaign schedule, never current repricing inputs.
     *
     * @param  array<array-key, string>  $payments
     */
    public static function allocate(string $campaignPrincipal, array $payments, UnitOrdinals $ordinals): self
    {
        $principal = self::amount($campaignPrincipal);
        $tenor = count($payments);
        if (! in_array($tenor, [3, 4, 5, 6], true) || ! array_is_list($payments)
            || ! $principal->isEqualTo($ordinals->totalUnits->multipliedBy(self::UNIT_PRINCIPAL)) || $ordinals->count->isZero()) {
            throw new PrimaryViolation('INVALID_UNIT_SCHEDULE');
        }
        $regularPrincipal = $principal->dividedBy($tenor, RoundingMode::HalfUp);
        $principalCursor = BigInteger::one();
        $returnCursor = BigInteger::one();
        $totalReturn = BigInteger::zero();
        $campaignReturn = BigInteger::zero();
        $instalments = [];
        foreach ($payments as $index => $payment) {
            $principalComponent = $index === $tenor - 1 ? $principal->minus($regularPrincipal->multipliedBy($tenor - 1)) : $regularPrincipal;
            $returnComponent = self::amount($payment)->minus($principalComponent);
            if ($returnComponent->isNegative()) {
                throw new PrimaryViolation('INVALID_UNIT_SCHEDULE');
            }
            $campaignReturn = $campaignReturn->plus($returnComponent);
            [$unitPrincipal, $principalCursor] = self::component($principalComponent, $ordinals, $principalCursor);
            [$unitReturn, $returnCursor] = self::component($returnComponent, $ordinals, $returnCursor);
            $totalReturn = $totalReturn->plus($unitReturn);
            $instalments[] = ['index' => $index + 1, 'principal' => (string) $unitPrincipal, 'return' => (string) $unitReturn];
        }

        return new self($ordinals, $ordinals->count->multipliedBy(self::UNIT_PRINCIPAL), $totalReturn, $instalments, $principal, $campaignReturn, $payments);
    }

    /** @return array{0: BigInteger, 1: BigInteger} */
    private static function component(BigInteger $amount, UnitOrdinals $ordinals, BigInteger $cursor): array
    {
        [$base, $remainder] = $amount->quotientAndRemainder($ordinals->totalUnits);
        $share = $base->multipliedBy($ordinals->count);
        $last = $cursor->plus($remainder)->minus(1);
        if (! $remainder->isZero()) {
            $share = $share->plus($ordinals->countBetween($cursor, BigInteger::min($last, $ordinals->totalUnits)));
            if ($last->isGreaterThan($ordinals->totalUnits)) {
                $share = $share->plus($ordinals->countBetween(BigInteger::one(), $last->minus($ordinals->totalUnits)));
            }
        }

        return [$share, $last->mod($ordinals->totalUnits)->plus(1)];
    }

    private static function amount(string $value): BigInteger
    {
        if (! preg_match('/^(0|[1-9][0-9]{0,63})$/D', $value)) {
            throw new PrimaryViolation('INVALID_MONEY');
        }

        return BigInteger::of($value);
    }

    /** @return array{total_return: array{currency: string, amount: string}, instalments: list<array{index: int, principal: array{currency: string, amount: string}, return: array{currency: string, amount: string}}>} */
    public function toArray(): array
    {
        return ['total_return' => ['currency' => 'RWF', 'amount' => (string) $this->contractualReturn],
            'instalments' => array_map(fn (array $instalment): array => ['index' => $instalment['index'],
                'principal' => ['currency' => 'RWF', 'amount' => $instalment['principal']],
                'return' => ['currency' => 'RWF', 'amount' => $instalment['return']]], $this->instalments)];
    }
}
