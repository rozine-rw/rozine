<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;

final readonly class LoanSchedule
{
    /** @param list<BigInteger> $instalments */
    private function __construct(
        public BigInteger $principal,
        public BigInteger $contractualReturn,
        public BigInteger $total,
        public array $instalments,
    ) {}

    /**
     * Arithmetic for requested and offered schedules. LoanCapacity enforces the
     * 5,000-franc grid before an offered or accepted schedule may be published.
     */
    public static function build(string $principal, int $tenor, BigRational $rate): self
    {
        FlatReturnPricing::validateTenor($tenor);
        $amount = ExactFinancialValue::amount($principal);
        if ($amount->isLessThan(3000000) || $rate->isLessThan('1/10') || $rate->isGreaterThan('3/20')
            || ! $rate->multipliedBy(1000)->getFractionalPart()->isZero()) {
            throw new UnderwritingViolation('INVALID_LOAN_TERMS');
        }
        $return = ExactFinancialValue::halfUp($rate->multipliedBy($amount));
        $total = $amount->plus($return);
        $regular = ExactFinancialValue::halfUp(BigRational::of($total)->dividedBy($tenor));
        $instalments = array_fill(0, $tenor - 1, $regular);
        $instalments[] = $total->minus($regular->multipliedBy($tenor - 1));

        return new self($amount, $return, $total, $instalments);
    }

    public function dscr(BigRational $cfads): BigRational
    {
        return $cfads->multipliedBy(count($this->instalments))->dividedBy($this->total);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'principal' => ExactFinancialValue::money($this->principal),
            'contractual_return' => ExactFinancialValue::money($this->contractualReturn),
            'total' => ExactFinancialValue::money($this->total),
            'instalments' => array_map(ExactFinancialValue::money(...), $this->instalments),
        ];
    }
}
